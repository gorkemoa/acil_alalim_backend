<?php
// controllers/NotificationController.php

class NotificationController {
    private $notificationModel;

    public function __construct($pdo) {
        $this->notificationModel = new Notification($pdo);
    }

    public function getAll() {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        $notifications = $this->notificationModel->getByUser($userId);
        echo json_encode($notifications);
    }

    public function markAsRead($id) {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        if ($this->notificationModel->markAsRead($id)) {
            echo json_encode(["message" => "Marked as read."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update notification."]);
        }
    }
}
