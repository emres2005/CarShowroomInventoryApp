<?php
namespace App\Validators;

class UserValidator {
    public static function validateCreate(array $data): array {
        $errors = [];
        if (empty($data['username']) || !preg_match('/^[a-zA-Z0-9_]{3,64}$/', $data['username'])) {
            $errors[] = 'Username must be 3-64 chars (letters, numbers, underscore).';
        }
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (empty($data['role']) || !in_array($data['role'], ['admin', 'user'], true)) {
            $errors[] = 'Role must be admin or user.';
        }
        return $errors;
    }

    public static function validateUpdate(array $data, bool $passwordRequired = false): array {
        $errors = [];
        if (empty($data['username']) || !preg_match('/^[a-zA-Z0-9_]{3,64}$/', $data['username'])) {
            $errors[] = 'Username must be 3-64 chars (letters, numbers, underscore).';
        }
        if ($passwordRequired && empty($data['password'])) {
            $errors[] = 'Password is required.';
        } elseif (!empty($data['password']) && strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (empty($data['role']) || !in_array($data['role'], ['admin', 'user'], true)) {
            $errors[] = 'Role must be admin or user.';
        }
        return $errors;
    }
}
