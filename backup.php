<?php
/**
 * backup.php — Database backup (mysqldump) and rollback (restore) — Admin only
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';
requireAdmin();

$backupService = new \App\Services\BackupService();

/* ── Action: CREATE backup ───────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'backup') {
    verifyCsrf();
    $result = $backupService->createBackup($_SESSION['username']);
    
    if ($result['success']) {
        flash('success', 'Backup created: ' . h($result['filename']));
    } else {
        flash('danger', $result['errors'][0]);
    }
    header('Location: backup.php');
    exit;
}

/* ── Action: RESTORE / ROLLBACK ──────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restore') {
    verifyCsrf();
    $filename = basename($_POST['filename'] ?? '');
    
    $result = $backupService->restoreBackup($filename);
    
    if ($result['success']) {
        flash('success', 'Rollback to <strong>' . h($filename) . '</strong> completed successfully!');
    } else {
        flash('danger', $result['errors'][0]);
    }
    header('Location: backup.php');
    exit;
}

/* ── Action: DELETE backup file ──────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_backup') {
    verifyCsrf();
    $filename = basename($_POST['filename'] ?? '');
    
    $result = $backupService->deleteBackup($filename);
    
    if ($result['success']) {
        flash('success', 'Backup file deleted: ' . h($filename));
    } else {
        flash('danger', $result['errors'][0]);
    }
    header('Location: backup.php');
    exit;
}

$logEntries = $backupService->getLog();
$backupFiles = $backupService->listBackups();

$token = csrfToken();
renderHeader('Backup & Restore');
?>

<!-- Confirm dialog (shared) -->
<div class="dialog-overlay" id="confirmOverlay">
  <div class="dialog-box">
    <h3 id="confirmTitle">Confirm Action</h3>
    <p id="confirmMsg"></p>
    <div class="dialog-actions">
      <button class="btn btn-ghost" onclick="closeConfirm()">Cancel</button>
      <button class="btn btn-danger" id="confirmOk">Confirm</button>
    </div>
  </div>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title">Backup &amp; Restore</h1>
    <p class="page-subtitle">Create database backups and roll back to any previous snapshot.</p>
  </div>

  <!-- Create Backup button -->
  <form method="post" action="backup.php" id="backupForm">
    <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
    <input type="hidden" name="action" value="backup">
    <button type="button" class="btn btn-warning"
            onclick="openConfirm('Create a new full database backup now?','backupForm')">
      Create Backup Now
    </button>
  </form>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

  <!-- ── Backup files ── -->
  <div class="card">
    <div class="card-title">Backup Files <span style="color:var(--text-dim);font-size:.8rem">(<?= count($backupFiles) ?> files)</span></div>

    <?php if ($backupFiles): ?>
    <div class="backup-list">
      <?php foreach ($backupFiles as $file): ?>
        <div class="backup-item">
          <div>
            <div class="backup-filename"><?= h($file['filename']) ?></div>
            <div class="backup-date"><?= date('d M Y, H:i', strtotime($file['mtime'])) ?> &bull; <?= $file['size_kb'] ?> KB</div>
          </div>
          <div style="display:flex;gap:.4rem;flex-shrink:0">
            <!-- Restore -->
            <form method="post" action="backup.php" id="restore_<?= md5($file['filename']) ?>">
              <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
              <input type="hidden" name="action" value="restore">
              <input type="hidden" name="filename" value="<?= h($file['filename']) ?>">
            </form>
            <button type="button" class="btn btn-sm btn-info"
                    onclick="openConfirm('Restore from <?= h(addslashes($file['filename'])) ?>? This will OVERWRITE current data!','restore_<?= md5($file['filename']) ?>')">
              Restore
            </button>

            <!-- Delete backup file -->
            <form method="post" action="backup.php" id="delbk_<?= md5($file['filename']) ?>">
              <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
              <input type="hidden" name="action" value="delete_backup">
              <input type="hidden" name="filename" value="<?= h($file['filename']) ?>">
            </form>
            <button type="button" class="btn btn-sm btn-danger"
                    onclick="openConfirm('Permanently delete backup file <?= h(addslashes($file['filename'])) ?>?','delbk_<?= md5($file['filename']) ?>')">
              Delete
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p style="color:var(--text-muted);padding:1rem 0">No backup files yet. Click <strong>Create Backup Now</strong> to generate the first one.</p>
    <?php endif; ?>
  </div>

  <!-- ── Backup log ── -->
  <div class="card">
    <div class="card-title">Backup History Log</div>
    <?php if ($logEntries): ?>
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr><th>File</th><th>Created By</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php foreach ($logEntries as $log): ?>
          <tr>
            <td style="font-size:.8rem;font-family:monospace;color:var(--text-muted)"><?= h($log['filename']) ?></td>
            <td><?= h($log['created_by']) ?></td>
            <td style="color:var(--text-dim);font-size:.8rem"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p style="color:var(--text-muted)">No log entries yet.</p>
    <?php endif; ?>
  </div>

</div>

<!-- Info box -->
<div class="alert alert-info" style="margin-top:1.5rem">
  Backups are stored at <code><?= h(BACKUP_DIR) ?></code> and are protected from direct HTTP access via <code>.htaccess</code>.
  Restore will <strong>overwrite current database data</strong> — use with caution.
</div>

<?php renderFooter(); ?>
