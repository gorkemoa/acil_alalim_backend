<?php
// models/Favorite.php

class Favorite {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function add($user_id, $need_id) {
        $sql = "INSERT INTO favorites (user_id, need_id) VALUES (:user_id, :need_id)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['user_id' => $user_id, 'need_id' => $need_id]);
    }

    public function getByUser($user_id) {
        $sql = "SELECT f.*, n.title, n.description FROM favorites f 
                JOIN needs n ON f.need_id = n.id 
                WHERE f.user_id = :user_id
                  AND NOT EXISTS (SELECT 1 FROM blocked_users bu WHERE bu.blocker_id = :user_id AND bu.blocked_id = n.user_id)
                  AND NOT EXISTS (SELECT 1 FROM blocked_users bu2 WHERE bu2.blocked_id = :user_id AND bu2.blocker_id = n.user_id)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }
}
