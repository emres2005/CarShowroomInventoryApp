<?php
/**
 * add_user.php — Create new user account (Admin only)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';
requireAdmin();

$errors = [];
$vals   = ['username' => '', 'role' => 'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $vals['username'] = postStr('username', 64);
    $vals['role']     = postStr('role', 10);
    $vals['password'] = $_POST['password'] ?? '';
    $passwordConfirm  = $_POST['password_confirm'] ?? '';

    if ($vals['password'] !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        $userService = new \App\Services\UserService();
        $result = $userService->createUser($vals);

        if ($result['success']) {
            flash('success', 'User "' . h($vals['username']) . '" created successfully!');
            header('Location: users.php');
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}

$token = csrfToken();
renderHeader('Add User');
?>

<div class="page-header">
  <div>
    <h1 class="page-title">Add User</h1>
    <p class="page-subtitle">Create a new login account.</p>
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
  <form method="post" action="add_user.php" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= h($token) ?>">

    <div class="form-group">
      <label class="form-label" for="username">Username *</label>
      <input id="username" type="text" name="username" class="form-control"
             placeholder="e.g. john_doe" value="<?= h($vals['username']) ?>"
             required maxlength="64" autocomplete="off">
    </div>

    <div class="form-group">
      <label class="form-label" for="role">Role</label>
      <select id="role" name="role" class="form-control">
        <option value="user"  <?= $vals['role']==='user' ?'selected':'' ?>>User — can view &amp; edit cars</option>
        <option value="admin" <?= $vals['role']==='admin'?'selected':'' ?>>Admin — full access (backup, users)</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password *</label>
      <div style="position:relative">
        <input id="password" type="password" name="password" class="form-control"
               placeholder="Min 8 characters" autocomplete="new-password" required>
        <button type="button" id="togglePw"
                style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);">Show</button>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="password_confirm">Confirm Password *</label>
      <div style="position:relative">
        <input id="password_confirm" type="password" name="password_confirm" class="form-control"
               placeholder="Repeat password" autocomplete="new-password" required>
        <button type="button" id="togglePw2"
                style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);">Show</button>
      </div>
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
      <a href="users.php" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">+ Create User</button>
    </div>
  </form>
</div>

<?php renderFooter(); ?>
