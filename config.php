<?php
/**
 * config.php
 * Central configuration — DB credentials, session settings, helpers.
 * Include this file at the top of every page.
 */

// ── Database credentials ──────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'prestashop_s067969');
define('DB_USER', 's067969');
define('DB_PASS', 'Pg7PuRspp5iZiyBb');
define('DB_CHARSET', 'utf8mb4');

// ── Application settings ──────────────────────────────────────────────────────
define('APP_NAME',    'AutoVault Showroom');
define('APP_VERSION', '1.0.0');

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true when using HTTPS
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── Autoloader ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/App/Autoloader.php';
// ── Auth helpers ──────────────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        header("Location: {$redirect}");
        exit;
    }
}

function requireAdmin(string $redirect = 'index.php'): void {
    requireLogin();
    if (!isAdmin()) {
        header("Location: {$redirect}?error=access_denied");
        exit;
    }
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

// ── Flash messages ────────────────────────────────────────────────────────────
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function renderFlash(): string {
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $type = htmlspecialchars($f['type']);
        $msg  = htmlspecialchars($f['msg']);
        $html .= "<div class=\"alert alert-{$type}\" role=\"alert\">{$msg}</div>";
    }
    unset($_SESSION['flash']);
    return $html;
}

// ── Sanitize helpers ──────────────────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function postStr(string $key, int $max = 255): string {
    return substr(trim($_POST[$key] ?? ''), 0, $max);
}
