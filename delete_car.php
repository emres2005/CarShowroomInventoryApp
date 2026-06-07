<?php
/**
 * delete_car.php — POST-only: delete a car by plate number (admin only)
 */
require 'config.php';
requireLogin();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cars.php');
    exit;
}

verifyCsrf();

$plate = trim($_POST['plate_number'] ?? '');
if ($plate === '') {
    flash('danger', 'Invalid plate number.');
    header('Location: cars.php');
    exit;
}

try {
    $pdo  = getPDO();
    // Fetch name for the flash message before deleting
    $info = $pdo->prepare('SELECT brand, car_model FROM car_data WHERE plate_number = ?');
    $info->execute([$plate]);
    $car  = $info->fetch();

    if (!$car) {
        flash('danger', 'Car not found.');
        header('Location: cars.php');
        exit;
    }

    // Delete from cars — CASCADE removes car_data row automatically
    $stmt = $pdo->prepare('DELETE FROM cars WHERE plate_number = ?');
    $stmt->execute([$plate]);

    flash('success', '🗑️ Car "' . h($car['brand']) . ' ' . h($car['car_model']) .
                     ' (' . h($plate) . ')" deleted.');
} catch (PDOException $e) {
    flash('danger', 'Database error: ' . $e->getMessage());
}

header('Location: cars.php');
exit;
