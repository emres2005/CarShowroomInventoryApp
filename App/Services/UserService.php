<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Validators\UserValidator;

class UserService {
    private UserRepository $repo;

    public function __construct() {
        $this->repo = new UserRepository();
    }

    public function listUsers(): array {
        return $this->repo->getAll();
    }

    public function getUser(int $id): ?array {
        return $this->repo->getById($id);
    }

    public function createUser(array $input): array {
        $errors = UserValidator::validateCreate($input);
        if ($errors) return ['success' => false, 'errors' => $errors];

        $hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        try {
            $this->repo->create($input['username'], $hash, $input['role']);
            return ['success' => true, 'errors' => []];
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                // Check if it's a case-insensitive false positive from the DB collation
                if ($this->repo->getByUsername($input['username']) === null) {
                    $this->repo->migrateUsernameCollation();
                    // Retry now that the DB column is case-sensitive
                    $this->repo->create($input['username'], $hash, $input['role']);
                    return ['success' => true, 'errors' => []];
                }
                return ['success' => false, 'errors' => ['Username already taken.']];
            }
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }

    public function updateUser(int $id, array $input, int $sessionUserId): array {
        $user = $this->repo->getById($id);
        if (!$user) return ['success' => false, 'errors' => ['User not found.']];

        $isSelf = ($id === $sessionUserId);
        $newRole = trim($input['role'] ?? '');
        
        if ($isSelf && $newRole !== 'admin') {
            return ['success' => false, 'errors' => ['You cannot demote yourself.']];
        }

        $passwordRequired = false;
        $errors = UserValidator::validateUpdate($input, $passwordRequired);
        if ($errors) return ['success' => false, 'errors' => $errors];

        $hash = null;
        if (!empty($input['password'])) {
            $hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        try {
            $this->repo->update($id, $input['username'], $newRole, $hash);
            return ['success' => true, 'errors' => []];
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                // Check if it's a case-insensitive false positive from the DB collation
                $existing = $this->repo->getByUsername($input['username']);
                if ($existing === null || (int)$existing['id'] === (int)$id) {
                    $this->repo->migrateUsernameCollation();
                    // Retry now that the DB column is case-sensitive
                    $this->repo->update($id, $input['username'], $newRole, $hash);
                    return ['success' => true, 'errors' => []];
                }
                return ['success' => false, 'errors' => ['Username already taken by another user.']];
            }
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }

    public function deleteUser(int $id, int $sessionUserId): array {
        if ($id === $sessionUserId) {
            return ['success' => false, 'errors' => ['You cannot delete your own account.']];
        }

        try {
            $user = $this->repo->delete($id);
            if (!$user) return ['success' => false, 'errors' => ['User not found.']];
            return ['success' => true, 'data' => $user, 'errors' => []];
        } catch (\PDOException $e) {
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
}
