<?php
namespace App\Services;

use App\Repositories\UserRepository;

class AuthService {
    private UserRepository $userRepo;

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    public function loginWithSession(string $username, string $password): array {
        $user = $this->userRepo->getByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return ['success' => true, 'user' => $user, 'errors' => []];
        }
        return ['success' => false, 'errors' => ['Invalid username or password.']];
    }
}
