<?php
// controllers/SystemController.php

class SystemController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getProvinces() {
        $stmt = $this->pdo->query("SELECT * FROM provinces ORDER BY name ASC");
        echo json_encode($stmt->fetchAll());
    }

    public function getDistricts($provinceId) {
        $stmt = $this->pdo->prepare("SELECT * FROM districts WHERE province_id = :pid ORDER BY name ASC");
        $stmt->execute(['pid' => $provinceId]);
        echo json_encode($stmt->fetchAll());
    }

    public function getCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY name ASC");
        echo json_encode($stmt->fetchAll());
    }
}
