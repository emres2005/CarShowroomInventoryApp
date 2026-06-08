<?php
namespace App\Repositories;

use App\Database;
use PDO;

class UserRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getByUsername(string $username): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE BINARY username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getAll(): array {
        return $this->pdo->query('SELECT id, username, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
    }

    public function create(string $username, string $hash, string $role): void {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$username, $hash, $role]);
    }

    public function update(int $id, string $username, string $role, ?string $hash): void {
        if ($hash) {
            $stmt = $this->pdo->prepare('UPDATE users SET username=?, role=?, password_hash=? WHERE id=?');
            $stmt->execute([$username, $role, $hash, $id]);
        } else {
            $stmt = $this->pdo->prepare('UPDATE users SET username=?, role=? WHERE id=?');
            $stmt->execute([$username, $role, $id]);
        }
    }

    public function delete(int $id): ?array {
        $user = $this->getById($id);
        if ($user) {
            $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
        }
        return $user;
    }

    public function migrateUsernameCollation(): void {
        $this->pdo->exec('ALTER TABLE users MODIFY username VARCHAR(64) COLLATE utf8mb4_bin NOT NULL');
    }
}
