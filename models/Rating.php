<?php
// models/Rating.php

class Rating {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function add($rater_id, $rated_id, $score, $comment) {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO ratings (rater_id, rated_id, score, comment) VALUES (:rater_id, :rated_id, :score, :comment)";
            $this->pdo->prepare($sql)->execute([
                'rater_id' => $rater_id,
                'rated_id' => $rated_id,
                'score' => $score,
                'comment' => $comment
            ]);

            // Update karma: each 4-5 star rating adds 10 karma, 1-2 star removes 5
            $karmaChange = $score >= 4 ? 10 : ($score <= 2 ? -5 : 0);
            $sqlKarma = "UPDATE users SET karma_score = karma_score + :change WHERE id = :id";
            $this->pdo->prepare($sqlKarma)->execute(['id' => $rated_id, 'change' => $karmaChange]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getByUser($user_id) {
        $sql = "SELECT r.*, u.name as rater_name FROM ratings r 
                JOIN users u ON r.rater_id = u.id 
                WHERE r.rated_id = :user_id ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }
}
