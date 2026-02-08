<?php
// controllers/RatingController.php

class RatingController {
    private $ratingModel;

    public function __construct($pdo) {
        $this->ratingModel = new Rating($pdo);
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

        if (empty($data['rated_id']) || empty($data['score'])) {
            http_response_code(400);
            echo json_encode(["error" => "Rated user ID and score are required."]);
            return;
        }

        // Block check
        $stmt = $this->ratingModel->getPdo()->prepare("SELECT 1 FROM blocked_users WHERE (blocker_id = :me AND blocked_id = :other) OR (blocker_id = :other AND blocked_id = :me) LIMIT 1");
        $stmt->execute(['me' => $userId, 'other' => $data['rated_id']]);
        if ($stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(["error" => "Cannot rate due to blocking."]);
            return;
        }

        if ($this->ratingModel->add($userId, $data['rated_id'], $data['score'], $data['comment'] ?? '')) {
            if ($data['rated_id'] != $userId) {
                $notificationModel = new Notification($this->ratingModel->getPdo());
                $notificationModel->create($data['rated_id'], "Yeni Puanlama", "Profiliniz için yeni bir değerlendirme aldınız.");
            }
            echo json_encode(["message" => "Rating added successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to add rating."]);
        }
    }

    public function getByUser($id) {
        $ratings = $this->ratingModel->getByUser($id);
        echo json_encode($ratings);
    }
}
