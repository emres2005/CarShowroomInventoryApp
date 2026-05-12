<?php
/**
 * users.php — User management (Admin only)
 */
require 'config.php';
require 'layout.php';
requireAdmin();

$pdo   = getPDO();
$users = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY id ASC')->fetchAll();

$token = csrfToken();
renderHeader('User Management');
?>

<!-- Confirm delete dialog -->
<div class="dialog-overlay" id="confirmOverlay">
  <div class="dialog-box">
    <h3>⚠️ Confirm Delete</h3>
    <p id="confirmMsg"></p>
    <div class="dialog-actions">
      <button class="btn btn-ghost" onclick="closeConfirm()">Cancel</button>
      <button class="btn btn-danger" id="confirmOk">Delete</button>
    </div>
  </div>
</div>

<div class="page-header">
  <div>
    <h1 class="page-title">User Management</h1>
    <p class="page-subtitle"><?= count($users) ?> registered user<?= count($users)!=1?'s':'' ?></p>
  </div>
  <a href="add_user.php" class="btn btn-primary">＋ Add User</a>
</div>

<div class="search-bar">
  <input type="text" id="userSearch" class="form-control"
         placeholder="🔍 Search by username or role…">
</div>

<div class="table-wrapper">
  <table class="data-table" id="usersTable">
    <thead>
      <tr><th>#</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td style="color:var(--text-dim);font-size:.8rem"><?= (int)$u['id'] ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:.6rem">
            <span class="user-avatar" style="width:32px;height:32px;font-size:.85rem">
              <?= strtoupper(mb_substr($u['username'],0,1)) ?>
            </span>
            <strong><?= h($u['username']) ?></strong>
          </div>
        </td>
        <td><span class="role-tag role-<?= h($u['role']) ?>"><?= h($u['role']) ?></span></td>
        <td style="color:var(--text-muted);font-size:.85rem"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
        <td>
          <div class="actions">
            <a href="edit_user.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-ghost" title="Edit">✏️ Edit</a>

            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
              <form method="post" action="delete_user.php" id="delu<?= (int)$u['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              </form>
              <button type="button" class="btn btn-sm btn-danger"
                      onclick="openConfirm('Delete user \"<?= h(addslashes($u['username'])) ?>\"? This cannot be undone.','delu<?= (int)$u['id'] ?>')">
                🗑️ Delete
              </button>
            <?php else: ?>
              <span class="btn btn-sm btn-ghost" style="opacity:.4;cursor:default" title="Cannot delete yourself">🔒 You</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php renderFooter(); ?>
