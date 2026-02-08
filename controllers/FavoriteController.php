<?php
// controllers/FavoriteController.php

class FavoriteController {
    private $favoriteModel;

    public function __construct($pdo) {
        $this->favoriteModel = new Favorite($pdo);
    }

    public function add() {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $needId = $data['need_id'] ?? null;

        if (!$needId) {
            http_response_code(400);
            echo json_encode(["error" => "Need ID is required."]);
            return;
        }

        $needModel = new Need($this->favoriteModel->getPdo());
        $need = $needModel->getById($needId);
        if (!$need) {
            http_response_code(404);
            echo json_encode(["error" => "Need not found."]);
            return;
        }
        if ($needModel->isBlockedBetween($userId, $need['user_id'])) {
            http_response_code(403);
            echo json_encode(["error" => "Cannot favorite due to blocking."]);
            return;
        }

        if ($this->favoriteModel->add($userId, $needId)) {
            if ($need['user_id'] != $userId) {
                $notificationModel = new Notification($this->favoriteModel->getPdo());
                $notificationModel->create($need['user_id'], "İlanın Favoriye Eklendi", "{$need['title']} favoriye eklendi.");
            }
            http_response_code(201);
            echo json_encode(["message" => "Added to favorites."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to add to favorites."]);
        }
    }

    public function getAll() {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        $favorites = $this->favoriteModel->getByUser($userId);
        echo json_encode($favorites);
    }

    public function remove() {
        $userId = AuthController::getAuthenticatedUser();
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $needId = $data['need_id'] ?? null;

        if (!$needId) {
            http_response_code(400);
            echo json_encode(["error" => "Need ID is required."]);
            return;
        }

        $sql = "DELETE FROM favorites WHERE user_id = :user AND need_id = :need";
        $stmt = $this->favoriteModel->getPdo()->prepare($sql);
        if ($stmt->execute(['user' => $userId, 'need' => $needId])) {
            echo json_encode(["message" => "Removed from favorites."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to remove."]);
        }
    }
}
