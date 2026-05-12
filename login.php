<?php
/**
 * login.php — Secure login page (bcrypt, CSRF, session fixation prevention)
 */
require 'config.php';
require 'layout.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = postStr('username', 64);
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            flash('success', 'Welcome back, ' . h($user['username']) . '!');
            header('Location: index.php');
            exit;
        } else {
            // Generic error to avoid username enumeration
            $error = 'Invalid username or password.';
        }
    }
}

$token = csrfToken();
renderHeader('Login');
?>

<div class="login-wrap">
  <div class="login-card">
    <div class="logo" style="margin-bottom:1.5rem; justify-content:center;">
      <span class="logo-icon">🚗</span>
      <span class="logo-text" style="font-size:1.5rem;">AutoVault</span>
    </div>
    <h1>Sign in</h1>
    <p class="subtitle">Enter your credentials to access the inventory system.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" autocomplete="off" id="loginForm">
      <input type="hidden" name="csrf_token" value="<?= h($token) ?>">

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input id="username" type="text" name="username" class="form-control"
               placeholder="your username" autocomplete="username"
               value="<?= h($_POST['username'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div style="position:relative;">
          <input id="password" type="password" name="password" class="form-control"
                 placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" id="togglePw"
                  style="position:absolute;right:.7rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;color:var(--text-muted);">👁</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.5rem;">
        Sign In →
      </button>
    </form>
  </div>
</div>

<?php renderFooter(); ?>
