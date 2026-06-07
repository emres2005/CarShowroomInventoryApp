<?php
namespace App\Repositories;

use App\Database;
use PDO;

class CarRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getByPlate(string $plate): ?array {
        $stmt = $this->pdo->prepare('
            SELECT c.plate_number, cd.brand, cd.car_model, cd.color, cd.year, cd.mileage,
                   cd.price, cd.fuel_type, cd.status, cd.description, c.created_at, c.updated_at
            FROM cars c
            JOIN car_data cd ON c.plate_number = cd.plate_number
            WHERE c.plate_number = ?
        ');
        $stmt->execute([$plate]);
        $car = $stmt->fetch();
        return $car ?: null;
    }

    public function getAll(array $filters = [], string $sortCol = 'c.created_at', string $sortDir = 'DESC'): array {
        $where  = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[]       = '(cd.brand LIKE :q OR cd.car_model LIKE :q OR c.plate_number LIKE :q)';
            $params[':q']  = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[]          = 'cd.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['fuel'])) {
            $where[]        = 'cd.fuel_type = :fuel';
            $params[':fuel'] = $filters['fuel'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->pdo->prepare("
            SELECT c.plate_number, cd.brand, cd.car_model, cd.color, cd.year, cd.mileage,
                   cd.price, cd.fuel_type, cd.status, c.created_at
            FROM cars c
            JOIN car_data cd ON c.plate_number = cd.plate_number
            {$whereSql}
            ORDER BY {$sortCol} {$sortDir}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getStats(): array {
        return $this->pdo->query("
            SELECT
                COUNT(*) AS total,
                SUM(cd.status='available')  AS available,
                SUM(cd.status='sold')       AS sold,
                SUM(cd.status='reserved')   AS reserved,
                SUM(cd.price)               AS total_value,
                AVG(cd.price)               AS avg_price
            FROM cars c
            JOIN car_data cd ON c.plate_number = cd.plate_number
        ")->fetch();
    }

    public function getRecent(int $limit = 5): array {
        $stmt = $this->pdo->prepare("
            SELECT c.plate_number, cd.brand, cd.car_model, cd.color, cd.status, cd.price, c.created_at
            FROM cars c
            JOIN car_data cd ON c.plate_number = cd.plate_number
            ORDER BY c.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByFuelType(): array {
        return $this->pdo->query("
            SELECT cd.fuel_type, COUNT(*) AS cnt 
            FROM car_data cd 
            GROUP BY cd.fuel_type 
            ORDER BY cnt DESC
        ")->fetchAll();
    }

    public function create(array $data): void {
        $this->pdo->beginTransaction();
        try {
            // Insert into cars (plate_number + timestamps)
            $this->pdo->prepare('INSERT INTO cars (plate_number) VALUES (:plate_number)')
                ->execute([':plate_number' => $data['plate_number']]);

            // Insert into car_data (all vehicle attributes)
            $this->pdo->prepare("
                INSERT INTO car_data
                    (plate_number, brand, car_model, color, year, mileage, price, fuel_type, status, description)
                VALUES
                    (:plate_number,:brand,:car_model,:color,:year,:mileage,:price,:fuel_type,:status,:description)
            ")->execute([
                ':plate_number' => $data['plate_number'],
                ':brand'        => ucwords(strtolower($data['brand'])),
                ':car_model'    => ucwords(strtolower($data['car_model'])),
                ':color'        => $data['color'],
                ':year'         => $data['year']    !== '' && $data['year'] !== null ? (int)$data['year']    : null,
                ':mileage'      => $data['mileage'] !== '' && $data['mileage'] !== null ? (int)$data['mileage'] : null,
                ':price'        => $data['price']   !== '' && $data['price'] !== null ? (float)$data['price'] : null,
                ':fuel_type'    => $data['fuel_type'],
                ':status'       => $data['status'],
                ':description'  => $data['description'] !== '' && $data['description'] !== null ? $data['description'] : null,
            ]);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateFull(string $oldPlate, array $data): void {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("
                UPDATE car_data SET
                    brand=:brand, car_model=:car_model, color=:color,
                    year=:year, mileage=:mileage, price=:price,
                    fuel_type=:fuel_type, status=:status, description=:description
                WHERE plate_number=:old_plate
            ")->execute([
                ':brand'        => ucwords(strtolower($data['brand'])),
                ':car_model'    => ucwords(strtolower($data['car_model'])),
                ':color'        => $data['color'],
                ':year'         => $data['year']    !== '' && $data['year'] !== null ? (int)$data['year']    : null,
                ':mileage'      => $data['mileage'] !== '' && $data['mileage'] !== null ? (int)$data['mileage'] : null,
                ':price'        => $data['price']   !== '' && $data['price'] !== null ? (float)$data['price'] : null,
                ':fuel_type'    => $data['fuel_type'],
                ':status'       => $data['status'],
                ':description'  => $data['description'] !== '' && $data['description'] !== null ? $data['description'] : null,
                ':old_plate'    => $oldPlate,
            ]);

            if ($data['plate_number'] !== $oldPlate) {
                $this->pdo->prepare("UPDATE cars SET plate_number=:new_plate WHERE plate_number=:old_plate")
                    ->execute([':new_plate' => $data['plate_number'], ':old_plate' => $oldPlate]);
            }

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateStatus(string $plate, string $status, ?string $description): void {
        $this->pdo->prepare("
            UPDATE car_data SET status=:status, description=:description WHERE plate_number=:plate
        ")->execute([
            ':status'      => $status,
            ':description' => $description !== '' && $description !== null ? $description : null,
            ':plate'       => $plate,
        ]);
    }

    public function delete(string $plate): ?array {
        $info = $this->pdo->prepare('SELECT brand, car_model FROM car_data WHERE plate_number = ?');
        $info->execute([$plate]);
        $car  = $info->fetch();

        if ($car) {
            $stmt = $this->pdo->prepare('DELETE FROM cars WHERE plate_number = ?');
            $stmt->execute([$plate]);
        }
        
        return $car ?: null;
    }
}
