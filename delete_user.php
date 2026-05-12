<?php
/**
 * delete_user.php — POST-only: delete a user (Admin only, cannot delete self)
 */
require 'config.php';
requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php'); exit;
}

verifyCsrf();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('danger', 'Invalid user ID.'); header('Location: users.php'); exit;
}

if ($id === (int)$_SESSION['user_id']) {
    flash('danger', '❌ You cannot delete your own account.');
    header('Location: users.php'); exit;
}

try {
    $pdo  = getPDO();
    $info = $pdo->prepare('SELECT username FROM users WHERE id = ?');
    $info->execute([$id]);
    $user = $info->fetch();

    if (!$user) {
        flash('danger', 'User not found.'); header('Location: users.php'); exit;
    }

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    flash('success', '🗑️ User "' . h($user['username']) . '" deleted.');
} catch (PDOException $e) {
    flash('danger', 'Database error: ' . $e->getMessage());
}

header('Location: users.php');
exit;
