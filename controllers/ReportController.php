<?php
// controllers/ReportController.php

class ReportController {
    private $reportModel;

    public function __construct($pdo) {
        $this->reportModel = new Report($pdo);
    }

    public function create() {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $data['reporter_id'] = $userId;

        if (empty($data['reason'])) {
            http_response_code(400);
            echo json_encode(["error" => "Reason is required."]);
            return;
        }

        if ($this->reportModel->create($data)) {
            echo json_encode(["message" => "Report submitted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to submit report."]);
        }
    }

    public function getAll() {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }
        $reports = $this->reportModel->getAll();
        echo json_encode($reports);
    }

    public function updateStatus($id) {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $status = $data['status'] ?? '';
        $allowed = ['pending','reviewed','resolved'];
        if (!in_array($status, $allowed)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid status."]);
            return;
        }
        if ($this->reportModel->updateStatus($id, $status)) {
            echo json_encode(["message" => "Report updated."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to update report."]);
        }
    }
}
