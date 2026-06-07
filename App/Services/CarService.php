<?php
namespace App\Services;

use App\Repositories\CarRepository;
use App\Validators\CarValidator;

class CarService {
    private CarRepository $repo;

    public function __construct() {
        $this->repo = new CarRepository();
    }

    public function getDashboardData(): array {
        return [
            'stats'  => $this->repo->getStats(),
            'recent' => $this->repo->getRecent(5),
            'byFuel' => $this->repo->getByFuelType()
        ];
    }

    public function listCars(array $filters = [], string $sortCol = 'c.created_at', string $sortDir = 'DESC'): array {
        return $this->repo->getAll($filters, $sortCol, $sortDir);
    }

    public function getCar(string $plate): ?array {
        return $this->repo->getByPlate($plate);
    }

    public function createCar(array $input): array {
        $errors = CarValidator::validateCar($input);
        if ($errors) return ['success' => false, 'errors' => $errors];

        $input['plate_number'] = strtoupper(trim($input['plate_number'] ?? ''));

        try {
            $this->repo->create($input);
            return ['success' => true, 'errors' => []];
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                return ['success' => false, 'errors' => ['Plate number already exists.']];
            }
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }

    public function updateCar(string $oldPlate, array $input, bool $isAdmin): array {
        if ($isAdmin) {
            $errors = CarValidator::validateCar($input);
            if ($errors) return ['success' => false, 'errors' => $errors];

            $input['plate_number'] = strtoupper(trim($input['plate_number'] ?? ''));
            
            try {
                $this->repo->updateFull($oldPlate, $input);
                return ['success' => true, 'errors' => []];
            } catch (\PDOException $e) {
                if ($e->errorInfo[1] === 1062) {
                    return ['success' => false, 'errors' => ['Plate number already belongs to another car.']];
                }
                return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
            }
        } else {
            // User: status and description only
            $status = trim($input['status'] ?? '');
            $errors = CarValidator::validateStatus($status);
            if ($errors) return ['success' => false, 'errors' => $errors];

            try {
                $this->repo->updateStatus($oldPlate, $status, trim($input['description'] ?? ''));
                return ['success' => true, 'errors' => []];
            } catch (\PDOException $e) {
                return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
            }
        }
    }

    public function deleteCar(string $plate): array {
        try {
            $car = $this->repo->delete($plate);
            if (!$car) {
                return ['success' => false, 'errors' => ['Car not found.']];
            }
            return ['success' => true, 'data' => $car, 'errors' => []];
        } catch (\PDOException $e) {
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
}
