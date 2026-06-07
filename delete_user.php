<?php
/**
 * delete_user.php — POST-only: delete a user (Admin only, cannot delete self)
 */
require_once __DIR__ . '/config.php';
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

$userService = new \App\Services\UserService();
$result = $userService->deleteUser($id, (int)$_SESSION['user_id']);

if ($result['success']) {
    flash('success', 'User "' . h($result['data']['username']) . '" deleted.');
} else {
    flash('danger', $result['errors'][0]);
}

header('Location: users.php');
exit;
