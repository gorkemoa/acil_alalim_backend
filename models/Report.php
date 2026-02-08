<?php
// models/Report.php

class Report {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO reports (reporter_id, reported_user_id, reported_need_id, reason) 
                VALUES (:reporter_id, :reported_user_id, :reported_need_id, :reason)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'reporter_id' => $data['reporter_id'],
            'reported_user_id' => $data['reported_user_id'] ?? null,
            'reported_need_id' => $data['reported_need_id'] ?? null,
            'reason' => $data['reason']
        ]);
    }

    public function getAll() {
        return $this->pdo->query("SELECT * FROM reports ORDER BY created_at DESC")->fetchAll();
    }
}
