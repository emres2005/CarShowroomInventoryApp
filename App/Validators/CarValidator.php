<?php
namespace App\Validators;

class CarValidator {
    public const ALLOWED_FUELS    = ['petrol','diesel','electric','hybrid','lpg','other'];
    public const ALLOWED_STATUSES = ['available','sold','reserved'];

    public static function validateCar(array $data): array {
        $errors = [];
        
        if (empty($data['brand']))        $errors[] = 'Brand is required.';
        if (empty($data['car_model']))    $errors[] = 'Model is required.';
        if (empty($data['plate_number'])) $errors[] = 'Plate number is required.';
        
        if (!empty($data['color']) && !preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'])) {
            $errors[] = 'Invalid color value.';
        }
        
        if (!empty($data['year']) && ($data['year'] < 1886 || $data['year'] > (int)date('Y') + 1)) {
            $errors[] = 'Invalid year.';
        }

        if (!empty($data['fuel_type']) && !in_array($data['fuel_type'], self::ALLOWED_FUELS, true)) {
            $errors[] = 'Invalid fuel type.';
        }
        
        if (!empty($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES, true)) {
            $errors[] = 'Invalid status.';
        }

        return $errors;
    }

    public static function validateStatus(string $status): array {
        $errors = [];
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            $errors[] = 'Invalid status value.';
        }
        return $errors;
    }
}
