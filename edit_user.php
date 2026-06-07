<?php
/**
 * edit_user.php — Edit user account (Admin only)
 * Password change is optional — leave blank to keep current.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';
requireAdmin();

$id  = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('danger', 'Invalid user ID.'); header('Location: users.php'); exit;
}

$userService = new \App\Services\UserService();
$user = $userService->getUser($id);

if (!$user) {
    flash('danger', 'User not found.'); header('Location: users.php'); exit;
}

$errors = [];
$vals   = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $vals['username'] = postStr('username', 64);
    $vals['role']     = postStr('role', 10);
    $vals['password'] = $_POST['password'] ?? '';
    $passwordConfirm  = $_POST['password_confirm'] ?? '';

    if ($vals['password'] !== '' && $vals['password'] !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $result = $userService->updateUser($id, $vals, (int)$_SESSION['user_id']);

        if ($result['success']) {
            // Update own session username if editing self
            if ((int)$id === (int)$_SESSION['user_id']) {
                $_SESSION['username'] = $vals['username'];
                $_SESSION['role']     = $vals['role'];
            }
            flash('success', 'User updated successfully!');
            header('Location: users.php');
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}

$token = csrfToken();
renderHeader('Edit User');
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Edit User</h1>
    <p class="page-subtitle">Editing account: <strong><?= h($user['username']) ?></strong></p>
  </div>
  <a href="users.php" class="btn btn-ghost">← Back to Users</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul style="margin:0;padding-left:1.2rem">
      <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card" style="max-width:520px">
  <form method="post" action="edit_user.php?id=<?= $id ?>" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= h($token) ?>">

    <div class="form-group">
      <label class="form-label" for="username">Username *</label>
      <input id="username" type="text" name="username" class="form-control"
             value="<?= h($vals['username']) ?>" required maxlength="64">
    </div>

    <div class="form-group">
      <label class="form-label" for="role">Role</label>
      <select id="role" name="role" class="form-control">
        <option value="user"  <?= $vals['role']==='user' ?'selected':'' ?>>User</option>
        <option value="admin" <?= $vals['role']==='admin'?'selected':'' ?>>Admin</option>
      </select>
    </div>

    <div class="alert alert-info" style="margin-bottom:1rem">
      Leave password fields blank to keep the current password. New passwords must be at least 8 chars, 1 uppercase, 1 digit.
    </div>

    <div class="form-group">
      <label class="form-label" for="password">New Password</label>
      <div style="position:relative">
        <input id="password" type="password" name="password" class="form-control"
               placeholder="Leave blank to keep current" autocomplete="new-password">
        <button type="button" id="togglePw"
                style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);">Show</button>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="password_confirm">Confirm New Password</label>
      <div style="position:relative">
        <input id="password_confirm" type="password" name="password_confirm" class="form-control"
               placeholder="Repeat new password" autocomplete="new-password">
        <button type="button" id="togglePw2"
                style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);">Show</button>
      </div>
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
      <a href="users.php" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>

<?php renderFooter(); ?>
