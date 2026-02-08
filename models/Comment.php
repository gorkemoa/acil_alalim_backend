<?php
// models/Comment.php

class Comment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO comments (sender_id, need_id, comment) 
                VALUES (:sender_id, :need_id, :comment)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'sender_id' => $data['sender_id'],
            'need_id' => $data['need_id'],
            'comment' => $data['comment']
        ]);
    }

    public function getByNeed($need_id, $viewerId = null) {
        $where = ["c.need_id = :need_id"];
        $params = ['need_id' => $need_id];

        if ($viewerId) {
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu WHERE bu.blocker_id = :viewer AND bu.blocked_id = c.sender_id)";
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu2 WHERE bu2.blocked_id = :viewer AND bu2.blocker_id = c.sender_id)";
            $params['viewer'] = $viewerId;
        }

        $sql = "SELECT c.*, u.name as user_name, u.profile_photo FROM comments c 
                JOIN users u ON c.sender_id = u.id 
                WHERE " . implode(' AND ', $where) . " ORDER BY c.created_at ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
