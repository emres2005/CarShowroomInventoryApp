<?php
/**
 * delete_car.php — POST-only: delete a car by plate number (admin only)
 */
require_once __DIR__ . '/config.php';
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

$carService = new \App\Services\CarService();
$result = $carService->deleteCar($plate);

if ($result['success']) {
    $car = $result['data'];
    flash('success', 'Car "' . h($car['brand']) . ' ' . h($car['car_model']) .
                     ' (' . h($plate) . ')" deleted.');
} else {
    flash('danger', $result['errors'][0]);
}

header('Location: cars.php');
exit;
