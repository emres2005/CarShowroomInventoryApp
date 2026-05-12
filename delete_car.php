<?php
/**
 * delete_car.php — POST-only: delete a car by ID (admin only)
 */
require 'config.php';
requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cars.php');
    exit;
}

verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    flash('danger', 'Invalid car ID.');
    header('Location: cars.php');
    exit;
}

try {
    $pdo  = getPDO();
    // Fetch name for the flash message before deleting
    $info = $pdo->prepare('SELECT brand, car_model, plate_number FROM cars WHERE id = ?');
    $info->execute([$id]);
    $car  = $info->fetch();

    if (!$car) {
        flash('danger', 'Car not found.');
        header('Location: cars.php');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM cars WHERE id = ?');
    $stmt->execute([$id]);

    flash('success', '🗑️ Car "' . h($car['brand']) . ' ' . h($car['car_model']) .
                     ' (' . h($car['plate_number']) . ')" deleted.');
} catch (PDOException $e) {
    flash('danger', 'Database error: ' . $e->getMessage());
}

header('Location: cars.php');
exit;
