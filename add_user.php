<?php
/**
 * add_user.php — Create new user account (Admin only)
 */
require 'config.php';
require 'layout.php';
requireAdmin();

$errors = [];
$vals   = ['username' => '', 'role' => 'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $vals['username'] = postStr('username', 64);
    $vals['role']     = postStr('role', 10);
    $password         = $_POST['password']         ?? '';
    $passwordConfirm  = $_POST['password_confirm'] ?? '';

    // Validation
    if ($vals['username'] === '')
        $errors[] = 'Username is required.';
    elseif (!preg_match('/^[a-zA-Z0-9_\-\.]{3,64}$/', $vals['username']))
        $errors[] = 'Username may only contain letters, numbers, _, -, . (3–64 chars).';

    if (strlen($password) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $passwordConfirm)
        $errors[] = 'Passwords do not match.';
    if (!in_array($vals['role'], ['admin','user']))
        $errors[] = 'Invalid role.';

    if (!$errors) {
        try {
            $pdo  = getPDO();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?,?,?)')
                ->execute([$vals['username'], $hash, $vals['role']]);
            flash('success', '✅ User "' . h($vals['username']) . '" created successfully!');
            header('Location: users.php');
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062)
                $errors[] = 'Username "' . h($vals['username']) . '" is already taken.';
            else
                $errors[] = 'Database error: ' . $e->getMessage();
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
                style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);">👁</button>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="password_confirm">Confirm Password *</label>
      <div style="position:relative">
        <input id="password_confirm" type="password" name="password_confirm" class="form-control"
               placeholder="Repeat password" autocomplete="new-password" required>
        <button type="button" id="togglePw2"
                style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);">👁</button>
      </div>
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
      <a href="users.php" class="btn btn-ghost">Cancel</a>
      <button type="submit" class="btn btn-primary">＋ Create User</button>
    </div>
  </form>
</div>

<?php renderFooter(); ?>
