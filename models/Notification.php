<?php
// models/Notification.php

class Notification {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($user_id, $title, $body) {
        $sql = "INSERT INTO notifications (user_id, title, body) VALUES (:user_id, :title, :body)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['user_id' => $user_id, 'title' => $title, 'body' => $body]);
    }

    public function getByUser($user_id) {
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    public function markAsRead($id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
