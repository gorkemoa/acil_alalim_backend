<?php
// models/Comment.php

class Comment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO comments (sender_id, need_id, parent_id, comment) 
                VALUES (:sender_id, :need_id, :parent_id, :comment)";
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            'sender_id' => $data['sender_id'],
            'need_id' => $data['need_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'comment' => $data['comment']
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    public function getById(int $id) {
        $sql = "SELECT c.*, u.name as user_name, u.profile_photo 
                FROM comments c 
                JOIN users u ON c.sender_id = u.id 
                WHERE c.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $comment = $stmt->fetch();
        if ($comment && !empty($comment['profile_photo'])) {
            $comment['profile_photo_url'] = $this->buildUploadUrl('profiles', $comment['profile_photo']);
        }
        return $comment;
    }

    public function getByNeed($need_id, $viewerId = null, $asTree = false) {
        $where = ["c.need_id = :need_id"];
        $params = ['need_id' => $need_id];

        if ($viewerId) {
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu WHERE bu.blocker_id = :viewer AND bu.blocked_id = c.sender_id)";
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu2 WHERE bu2.blocked_id = :viewer AND bu2.blocker_id = c.sender_id)";
            $params['viewer'] = $viewerId;
        }

        $sql = "SELECT c.*, u.name as user_name, u.profile_photo FROM comments c 
                JOIN users u ON c.sender_id = u.id 
                WHERE " . implode(' AND ', $where) . " ORDER BY c.created_at ASC, c.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $comments = $stmt->fetchAll();

        foreach ($comments as &$c) {
            if (!empty($c['profile_photo'])) {
                $c['profile_photo_url'] = $this->buildUploadUrl('profiles', $c['profile_photo']);
            }
        }

        if ($asTree) {
            return $this->buildTree($comments);
        }

        return $comments;
    }

    public function getThreadByNeed($need_id, $viewerId = null) {
        return $this->getByNeed($need_id, $viewerId, true);
    }

    private function buildUploadUrl($folder, $fileName) {
        if (empty($fileName)) return null;

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $basePath = $basePath === '/' ? '' : $basePath;

        return $scheme . '://' . $host . $basePath . '/uploads/' . $folder . '/' . $fileName;
    }

    private function buildTree(array $comments) {
        $byId = [];
        foreach ($comments as $comment) {
            $comment['replies'] = [];
            $byId[$comment['id']] = $comment;
        }

        $roots = [];
        foreach ($byId as $id => &$comment) {
            $parentId = $comment['parent_id'] ?? null;
            if ($parentId && isset($byId[$parentId])) {
                $byId[$parentId]['replies'][] = &$comment;
            } else {
                $roots[] = &$comment;
            }
        }
        // Clean up references
        unset($comment);
        return array_values($roots);
    }
}
