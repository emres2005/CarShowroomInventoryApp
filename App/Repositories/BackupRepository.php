<?php
namespace App\Repositories;

use App\Database;
use PDO;

class BackupRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function logBackup(string $filename, string $createdBy): void {
        $stmt = $this->pdo->prepare('INSERT INTO backup_log (filename, created_by) VALUES (?, ?)');
        $stmt->execute([$filename, $createdBy]);
    }

    public function deleteLog(string $filename): void {
        $stmt = $this->pdo->prepare('DELETE FROM backup_log WHERE filename = ?');
        $stmt->execute([$filename]);
    }

    public function getLog(int $limit = 30): array {
        $stmt = $this->pdo->prepare('SELECT * FROM backup_log ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
