<?php
/**
 * backup.php — Database backup (mysqldump) and rollback (restore) — Admin only
 */
require 'config.php';
require 'layout.php';
requireAdmin();

// Ensure backup directory exists and is protected
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0750, true);
    // Write .htaccess to block direct HTTP access
    file_put_contents(BACKUP_DIR . '.htaccess', "Deny from all\n");
}

$pdo    = getPDO();
$errors = [];
$info   = '';

/* ── Helper: list existing backup files ─────────────────── */
function listBackups(): array {
    $files = glob(BACKUP_DIR . '*.sql');
    if (!$files) return [];
    usort($files, fn($a,$b) => filemtime($b) - filemtime($a)); // newest first
    return $files;
}

/* ── Action: CREATE backup ───────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'backup') {
    verifyCsrf();

    $ts       = date('Y-m-d_H-i-s');
    $user     = $_SESSION['username'];
    $filename = "backup_{$ts}_{$user}.sql";
    $filepath = BACKUP_DIR . $filename;

    // Build mysqldump command — credentials via env to avoid shell history exposure
    $cmd = sprintf(
        'MYSQL_PWD=%s mysqldump --host=%s --user=%s --single-transaction --quick --routines --triggers %s > %s 2>&1',
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_NAME),
        escapeshellarg($filepath)
    );
    exec($cmd, $output, $exitCode);

    if ($exitCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        // Log in DB
        $pdo->prepare('INSERT INTO backup_log (filename, created_by) VALUES (?,?)')
            ->execute([$filename, $user]);
        flash('success', '💾 Backup created: ' . h($filename));
    } else {
        @unlink($filepath);
        flash('danger', '❌ Backup failed. Check server mysqldump access. Exit code: ' . $exitCode);
    }
    header('Location: backup.php');
    exit;
}

/* ── Action: RESTORE / ROLLBACK ──────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restore') {
    verifyCsrf();

    $filename = basename($_POST['filename'] ?? '');
    $filepath = BACKUP_DIR . $filename;

    // Security: only allow .sql files that actually live in BACKUP_DIR
    if (!preg_match('/^backup_[\w\-]+\.sql$/', $filename) || !file_exists($filepath)) {
        flash('danger', '❌ Invalid or missing backup file.');
        header('Location: backup.php');
        exit;
    }

    $cmd = sprintf(
        'MYSQL_PWD=%s mysql --host=%s --user=%s %s < %s 2>&1',
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_NAME),
        escapeshellarg($filepath)
    );
    exec($cmd, $output, $exitCode);

    if ($exitCode === 0) {
        flash('success', '🔄 Rollback to <strong>' . h($filename) . '</strong> completed successfully!');
    } else {
        flash('danger', '❌ Restore failed. Exit code: ' . $exitCode . '. Output: ' . implode(' ', $output));
    }
    header('Location: backup.php');
    exit;
}

/* ── Action: DELETE backup file ──────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_backup') {
    verifyCsrf();

    $filename = basename($_POST['filename'] ?? '');
    $filepath = BACKUP_DIR . $filename;

    if (!preg_match('/^backup_[\w\-]+\.sql$/', $filename) || !file_exists($filepath)) {
        flash('danger', '❌ Invalid or missing backup file.');
        header('Location: backup.php');
        exit;
    }

    unlink($filepath);
    $pdo->prepare('DELETE FROM backup_log WHERE filename = ?')->execute([$filename]);
    flash('success', '🗑️ Backup file deleted: ' . h($filename));
    header('Location: backup.php');
    exit;
}

/* ── Fetch log from DB ───────────────────────────────────── */
$logEntries = $pdo->query('SELECT * FROM backup_log ORDER BY created_at DESC LIMIT 30')->fetchAll();
$backupFiles = listBackups();

$token = csrfToken();
renderHeader('Backup & Restore');
?>

<!-- Confirm dialog (shared) -->
<div class="dialog-overlay" id="confirmOverlay">
  <div class="dialog-box">
    <h3 id="confirmTitle">⚠️ Confirm Action</h3>
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
      💾 Create Backup Now
    </button>
  </form>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">

  <!-- ── Backup files ── -->
  <div class="card">
    <div class="card-title">📂 Backup Files <span style="color:var(--text-dim);font-size:.8rem">(<?= count($backupFiles) ?> files)</span></div>

    <?php if ($backupFiles): ?>
    <div class="backup-list">
      <?php foreach ($backupFiles as $fpath): ?>
        <?php
          $fname   = basename($fpath);
          $size    = round(filesize($fpath) / 1024, 1) . ' KB';
          $mtime   = date('d M Y, H:i', filemtime($fpath));
        ?>
        <div class="backup-item">
          <div>
            <div class="backup-filename">📄 <?= h($fname) ?></div>
            <div class="backup-date"><?= $mtime ?> &bull; <?= $size ?></div>
          </div>
          <div style="display:flex;gap:.4rem;flex-shrink:0">
            <!-- Restore -->
            <form method="post" action="backup.php" id="restore_<?= md5($fname) ?>">
              <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
              <input type="hidden" name="action" value="restore">
              <input type="hidden" name="filename" value="<?= h($fname) ?>">
            </form>
            <button type="button" class="btn btn-sm btn-info"
                    onclick="openConfirm('⚠️ Restore from <?= h(addslashes($fname)) ?>? This will OVERWRITE current data!','restore_<?= md5($fname) ?>')">
              🔄 Restore
            </button>

            <!-- Delete backup file -->
            <form method="post" action="backup.php" id="delbk_<?= md5($fname) ?>">
              <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
              <input type="hidden" name="action" value="delete_backup">
              <input type="hidden" name="filename" value="<?= h($fname) ?>">
            </form>
            <button type="button" class="btn btn-sm btn-danger"
                    onclick="openConfirm('Permanently delete backup file <?= h(addslashes($fname)) ?>?','delbk_<?= md5($fname) ?>')">
              🗑️
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
    <div class="card-title">📋 Backup History Log</div>
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
  ℹ️ Backups are stored at <code><?= h(BACKUP_DIR) ?></code> and are protected from direct HTTP access via <code>.htaccess</code>.
  Restore will <strong>overwrite current database data</strong> — use with caution.
</div>

<?php renderFooter(); ?>
