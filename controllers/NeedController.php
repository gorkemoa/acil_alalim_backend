<?php
// controllers/NeedController.php

class NeedController {
    private $needModel;

    public function __construct($pdo) {
        $this->needModel = new Need($pdo);
    }

    public function getAll() {
        $filters = [
            'category_id' => $_GET['category_id'] ?? null,
            'province_id' => $_GET['province_id'] ?? null,
            'district_id' => $_GET['district_id'] ?? null,
            'query' => $_GET['q'] ?? null,
            'sort' => $_GET['sort'] ?? null,
            'lat' => $_GET['lat'] ?? null,
            'lng' => $_GET['lng'] ?? null,
            'viewer_id' => AuthController::getAuthenticatedUser()
        ];

        $pagination = [
            'page' => $_GET['page'] ?? 1,
            'per_page' => $_GET['per_page'] ?? 20
        ];

        $result = $this->needModel->getAll($filters, $pagination);
        echo json_encode($result);
    }

    public function getOne($id) {
        $need = $this->needModel->getById($id);
        if ($need) {
            $viewerId = AuthController::getAuthenticatedUser();
            if ($viewerId && $this->needModel->isBlockedBetween($viewerId, $need['user_id'])) {
                http_response_code(403);
                echo json_encode(["error" => "Access denied due to blocking."]);
                return;
            }
            echo json_encode($need);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Need not found."]);
        }
    }

    public function create() {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        // Handle both JSON and Multipart data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $data['user_id'] = $userId;

        $validation = ValidationService::validateNeed($data);
        if ($validation !== true) {
            http_response_code(400);
            echo json_encode(["error" => $validation]);
            return;
        }

        $needId = $this->needModel->create($data);
        if ($needId) {
            $uploadDir = dirname(__DIR__) . '/uploads/needs/';
            
            // 1. Handle Multipart Images
            if (!empty($_FILES['images'])) {
                $files = $_FILES['images'];
                foreach ($files['name'] as $key => $name) {
                    $file = [
                        'name' => $files['name'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error' => $files['error'][$key],
                        'size' => $files['size'][$key]
                    ];
                    $upload = FileService::upload($file, $uploadDir);
                    if (isset($upload['path'])) {
                        $this->needModel->addImage($needId, $upload['path'], $key === 0 ? 1 : 0);
                    }
                }
            }

            // 2. Handle Base64 Images (from JSON)
            if (!empty($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $key => $base64) {
                    $upload = FileService::uploadBase64($base64, $uploadDir);
                    if (isset($upload['path'])) {
                        $this->needModel->addImage($needId, $upload['path'], $key === 0 ? 1 : 0);
                    }
                }
            }

            // Proximity Notifications
            $nearbyUsers = $this->needModel->findNearbyUsers($data['latitude'], $data['longitude'], 5);
            $notificationModel = new Notification($this->needModel->getPdo());
            foreach ($nearbyUsers as $user) {
                if ($user['id'] != $userId) {
                    $notificationModel->create($user['id'], "Yakınında Yeni İlan!", $data['title'] . " ilanına göz at.");
                }
            }

            http_response_code(201);
            echo json_encode(["message" => "Need created successfully.", "id" => $needId]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to create need."]);
        }
    }

    public function update($id) {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        $existing = $this->needModel->getById($id);
        if (!$existing || $existing['user_id'] != $userId) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden or not found."]);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (!$data) $data = $_POST;

        // Field-level lightweight validation for partial updates
        if (isset($data['title']) && strlen($data['title']) < 5) {
            http_response_code(400);
            echo json_encode(["error" => "Title must be at least 5 characters."]);
            return;
        }
        if (isset($data['description']) && strlen($data['description']) < 10) {
            http_response_code(400);
            echo json_encode(["error" => "Description must be at least 10 characters."]);
            return;
        }

        $updateFields = array_intersect_key($data, array_flip(['title','description','category_id','latitude','longitude','province_id','district_id','status','is_sponsor']));

        $uploadDir = dirname(__DIR__) . '/uploads/needs/';
        $imagesToRemove = $data['remove_image_ids'] ?? [];
        if (!is_array($imagesToRemove)) $imagesToRemove = [];

        $this->needModel->getPdo()->beginTransaction();
        try {
            $this->needModel->update($id, $updateFields);

            // Remove images
            if (!empty($imagesToRemove)) {
                $existing = $this->needModel->getImages($id);
                $toDelete = array_filter($existing, fn($img) => in_array($img['id'], $imagesToRemove));
                $this->needModel->deleteImages($id, $imagesToRemove);
                foreach ($toDelete as $img) {
                    $path = $uploadDir . $img['image_path'];
                    if (is_file($path)) @unlink($path);
                }
            }

            // Add new images (multipart)
            if (!empty($_FILES['images'])) {
                $files = $_FILES['images'];
                foreach ($files['name'] as $key => $name) {
                    $file = [
                        'name' => $files['name'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error' => $files['error'][$key],
                        'size' => $files['size'][$key]
                    ];
                    $upload = FileService::upload($file, $uploadDir);
                    if (isset($upload['path'])) {
                        $this->needModel->addImage($id, $upload['path'], 0);
                    }
                }
            }

            // Add new images (base64)
            if (!empty($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $base64) {
                    $upload = FileService::uploadBase64($base64, $uploadDir);
                    if (isset($upload['path'])) {
                        $this->needModel->addImage($id, $upload['path'], 0);
                    }
                }
            }

            if (!empty($data['main_image_id'])) {
                $this->needModel->setMainImage($id, $data['main_image_id']);
            }

            $this->needModel->getPdo()->commit();
            echo json_encode(["message" => "Need updated successfully."]);
        } catch (Exception $e) {
            $this->needModel->getPdo()->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Update failed."]);
        }
    }

    public function delete($id) {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }

        $existing = $this->needModel->getById($id);
        if (!$existing || $existing['user_id'] != $userId) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden or not found."]);
            return;
        }

        if ($this->needModel->delete($id)) {
            echo json_encode(["message" => "Need deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Deletion failed."]);
        }
    }

    public function getNear() {
        $lat = $_GET['lat'] ?? null;
        $lng = $_GET['lng'] ?? null;
        $radius = $_GET['radius'] ?? 10;

        if (!$lat || !$lng) {
            http_response_code(400);
            echo json_encode(["error" => "Latitude and longitude are required."]);
            return;
        }

        $needs = $this->needModel->getNear(
            $lat,
            $lng,
            $radius,
            AuthController::getAuthenticatedUser(),
            $_GET['page'] ?? 1,
            $_GET['per_page'] ?? 20
        );
        echo json_encode($needs);
    }

    public function liveSearch() {
        $q = $_GET['q'] ?? '';
        $results = $this->needModel->liveSearch($q, AuthController::getAuthenticatedUser());
        echo json_encode($results);
    }

    public function setSponsor($id) {
        $status = $_GET['status'] ?? 1;
        if ($this->needModel->toggleSponsor($id, $status)) {
            echo json_encode(["message" => "Sponsor status updated."]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Update failed."]);
        }
    }

    public function getMyProducts() {
        $userId = AuthController::getAuthenticatedUser();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized"]);
            return;
        }
        $needs = $this->needModel->getByUser($userId);
        echo json_encode($needs);
    }
}
