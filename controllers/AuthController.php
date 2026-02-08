<?php
// controllers/AuthController.php

class AuthController {
    private $userModel;

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    public function register() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $validation = ValidationService::validateRegister($data);
        if ($validation !== true) {
            http_response_code(400);
            echo json_encode(["error" => $validation]);
            return;
        }

        if ($this->userModel->findByEmail($data['email'])) {
            http_response_code(400);
            echo json_encode(["error" => "Email already exists."]);
            return;
        }

        if ($this->userModel->create($data)) {
            $user = $this->userModel->findByEmail($data['email']);
            $token = TokenService::create(['id' => $user['id'], 'email' => $user['email']], 86400);
            http_response_code(201);
            echo json_encode([
                "message" => "User registered successfully.",
                "token" => $token,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Registration failed."]);
        }
    }

    public function login() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(["error" => "Email and password are required."]);
            return;
        }

        $user = $this->userModel->findByEmail($data['email']);
        if ($user && password_verify($data['password'], $user['password'])) {
            $token = TokenService::create(['id' => $user['id'], 'email' => $user['email']], 86400);
            echo json_encode([
                "message" => "Login successful.",
                "token" => $token,
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "email" => $user['email']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Invalid email or password."]);
        }
    }

    public function forgotPassword() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $email = $data['email'] ?? '';

        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(16));
            $this->userModel->deleteResetTokens($email);
            $this->userModel->storeResetToken($email, $token);
            // In reality, send email here
            echo json_encode(["message" => "Reset token generated.", "token" => $token]);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "User not found."]);
        }
    }

    public function resetPassword() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $email = $data['email'] ?? '';
        $token = $data['token'] ?? '';
        $newPassword = $data['password'] ?? '';

        $reset = $this->userModel->verifyResetToken($email, $token);
        if ($reset) {
            $user = $this->userModel->findByEmail($email);
            $this->userModel->updatePassword($user['id'], $newPassword);
            $this->userModel->deleteResetTokens($email);
            echo json_encode(["message" => "Password updated successfully."]);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid or expired token."]);
        }
    }

    public function getProfile($id = null) {
        $userId = $id ?: self::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        $user = $this->userModel->findById($userId);
        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "User not found."]);
        }
    }

    public function updateProfile() {
        $userId = self::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        // Handle both JSON and Multipart
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (!$data) $data = $_POST;

        // 1. Handle Multipart Upload
        $uploadDir = dirname(__DIR__) . '/uploads/profiles/';
        if (!empty($_FILES['profile_photo'])) {
            $upload = FileService::upload($_FILES['profile_photo'], $uploadDir);
            if (isset($upload['path'])) {
                $data['profile_photo'] = $upload['path'];
            }
        }

        // 2. Handle Base64 Upload (JSON)
        if (!empty($data['profile_photo']) && strpos($data['profile_photo'], 'data:image') === 0) {
            $upload = FileService::uploadBase64($data['profile_photo'], $uploadDir);
            if (isset($upload['path'])) {
                $data['profile_photo'] = $upload['path'];
            }
        }

        if ($this->userModel->updateProfile($userId, $data)) {
            $user = $this->userModel->findById($userId);
            $token = TokenService::create(['id' => $user['id'], 'email' => $user['email']], 86400);
            echo json_encode([
                "message" => "Profile updated successfully.",
                "token" => $token,
                "user" => $user
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update profile."]);
        }
    }

    public function block() {
        $userId = self::getAuthenticatedUser();
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $targetId = $data['user_id'] ?? null;

        if ($this->userModel->blockUser($userId, $targetId)) {
            echo json_encode(["message" => "User blocked."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to block user."]);
        }
    }

    public function unblock() {
        $userId = self::getAuthenticatedUser();
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $targetId = $data['user_id'] ?? null;

        if ($this->userModel->unblockUser($userId, $targetId)) {
            echo json_encode(["message" => "User unblocked."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to unblock user."]);
        }
    }

    public function getBlockedList() {
        $userId = self::getAuthenticatedUser();
        $list = $this->userModel->getBlockedUsers($userId);
        echo json_encode($list);
    }

    public function deleteAccount() {
        $userId = self::getAuthenticatedUser();
        if ($this->userModel->deleteUser($userId)) {
            echo json_encode(["message" => "Account deleted."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to delete account."]);
        }
    }

    // Utility to get authenticated user from token
    public static function getAuthenticatedUser() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $payload = TokenService::verify($matches[1]);
            if ($payload && isset($payload['id'])) {
                return $payload['id'];
            }
        }
        return null;
    }
}
