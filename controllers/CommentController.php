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
        if (!is_array($data)) {
            $data = [];
        }
        $data['sender_id'] = $userId;

        if (empty($data['need_id']) || empty(trim((string)$data['comment']))) {
            http_response_code(400);
            echo json_encode(["error" => "Need ID and comment are required."]);
            return;
        }

        $needModel = new Need($this->commentModel->getPdo());
        $need = $needModel->getById($data['need_id']);
        if (!$need) {
            http_response_code(404);
            echo json_encode(["error" => "Need not found."]);
            return;
        }
        if (isset($need['allow_comments']) && !$need['allow_comments']) {
            http_response_code(403);
            echo json_encode(["error" => "Comments are disabled for this listing."]);
            return;
        }

        $data['comment'] = trim((string)$data['comment']);

        // If replying to another comment, validate it belongs to the same need
        $parentComment = null;
        if (!empty($data['parent_id'])) {
            $parentComment = $this->commentModel->getById((int)$data['parent_id']);
            if (!$parentComment) {
                http_response_code(404);
                echo json_encode(["error" => "Parent comment not found."]);
                return;
            }
            if ((int)$parentComment['need_id'] !== (int)$data['need_id']) {
                http_response_code(400);
                echo json_encode(["error" => "Parent comment belongs to a different listing."]);
                return;
            }
        }

        $newId = $this->commentModel->create($data);
        if ($newId) {
            // İlan sahibine bildirim gönder
            if ($need && $need['user_id'] != $userId) {
                $notificationModel = new Notification($this->commentModel->getPdo());
                $notificationModel->create($need['user_id'], "Yeni Yorum!", "İlanınıza yeni bir yorum yapıldı.");
            }

            // Parent yorum sahibine bildirim gönder (ilan sahibinden farklıysa)
            if ($parentComment && $parentComment['sender_id'] != $userId && $parentComment['sender_id'] != ($need['user_id'] ?? null)) {
                $notificationModel = $notificationModel ?? new Notification($this->commentModel->getPdo());
                $notificationModel->create($parentComment['sender_id'], "Yanıt Aldınız", "Yorumunuza bir yanıt geldi.");
            }

            $created = $this->commentModel->getById($newId);

            http_response_code(201);
            echo json_encode([
                "message" => "Comment added.",
                "id" => $newId,
                "comment" => $created
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to add comment."]);
        }
    }

    public function getByNeed($need_id) {
        $needModel = new Need($this->commentModel->getPdo());
        $need = $needModel->getById($need_id);
        $allow = $need ? (bool)($need['allow_comments'] ?? 1) : true;

        $viewerId = AuthController::getAuthenticatedUser();
        $comments = $allow ? $this->commentModel->getByNeed($need_id, $viewerId) : [];
        $threads = $allow ? $this->commentModel->getThreadByNeed($need_id, $viewerId) : [];
        echo json_encode([
            'allow_comments' => $allow,
            'count' => count($comments),
            'data' => $comments,      // düz liste (geri uyumluluk)
            'threads' => $threads     // iç içe ağaç yapı
        ]);
    }
}
