<?php
// controllers/CommentController.php

class CommentController {
    private $commentModel;

    public function __construct($pdo) {
        $this->commentModel = new Comment($pdo);
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
        $data['sender_id'] = $userId;

        if (empty($data['need_id']) || empty($data['comment'])) {
            http_response_code(400);
            echo json_encode(["error" => "Need ID and comment are required."]);
            return;
        }

        if ($this->commentModel->create($data)) {
            // İlan sahibine bildirim gönder
            $needModel = new Need($this->commentModel->getPdo());
            $need = $needModel->getById($data['need_id']);
            if ($need && $need['user_id'] != $userId) {
                $notificationModel = new Notification($this->commentModel->getPdo());
                $notificationModel->create($need['user_id'], "Yeni Yorum!", "İlanınıza yeni bir yorum yapıldı.");
            }

            http_response_code(201);
            echo json_encode(["message" => "Comment added."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to add comment."]);
        }
    }

    public function getByNeed($need_id) {
        $comments = $this->commentModel->getByNeed($need_id, AuthController::getAuthenticatedUser());
        echo json_encode($comments);
    }
}
