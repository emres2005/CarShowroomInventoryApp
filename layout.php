<?php
/**
 * layout.php
 * Shared HTML shell — header + footer helpers.
 * Call renderHeader() / renderFooter() from every page.
 */

function renderHeader(string $pageTitle = ''): void {
    $title = $pageTitle ? h($pageTitle) . ' · ' . APP_NAME : APP_NAME;
    $flash  = renderFlash();
    $nav    = buildNav();
    $user   = isset($_SESSION['username']) ? h($_SESSION['username']) : '';
    $role   = isset($_SESSION['role'])     ? h($_SESSION['role'])     : '';
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <meta name="description" content="AutoVault — Premium Car Showroom Inventory Management">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a href="index.php" class="logo">
      <span class="logo-icon"></span>
      <span class="logo-text">AutoVault</span>
    </a>
    <nav class="main-nav">
      {$nav}
    </nav>
    <div class="header-user">
HTML;

    if (isLoggedIn()) {
        echo <<<HTML
      <span class="user-badge">
        <span class="user-avatar">{$user[0]}</span>
        <span>{$user}</span>
        <span class="role-tag role-{$role}">{$role}</span>
      </span>
      <a href="logout.php" class="btn btn-sm btn-ghost">Logout</a>
HTML;
    } else {
        echo '<a href="login.php" class="btn btn-sm btn-primary">Login</a>';
    }

    echo <<<HTML
    </div>
  </div>
</header>
<main class="main-content">
{$flash}
HTML;
}

function renderFooter(): void {
    $year    = date('Y');
    $version = APP_VERSION;
    echo <<<HTML
</main>
<footer class="site-footer">
  <div class="footer-inner">
    <span>© {$year} AutoVault Showroom &mdash; v{$version}</span>
  </div>
</footer>
<script src="assets/js/main.js"></script>
</body>
</html>
HTML;
}

function buildNav(): string {
    $links = [['index.php', 'Dashboard', true]];
    if (isLoggedIn()) {
        $links[] = ['cars.php', 'Inventory', true];
        if (isAdmin()) {
            $links[] = ['add_car.php',  'Add Car', true];
            $links[] = ['users.php',    'Users',   true];
        }
    }
    $current = basename($_SERVER['PHP_SELF']);
    $html = '';
    foreach ($links as [$href, $label, $show]) {
        if (!$show) continue;
        $active = ($current === $href) ? ' active' : '';
        $label  = h($label);
        $html  .= "<a href=\"{$href}\" class=\"nav-link{$active}\">{$label}</a>";
    }
    return $html;
}
