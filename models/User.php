<?php
// models/User.php

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO users (name, email, password, province_id, district_id, latitude, longitude, phone, whatsapp, bio, website) 
                VALUES (:name, :email, :password, :province_id, :district_id, :latitude, :longitude, :phone, :whatsapp, :bio, :website)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'province_id' => $data['province_id'] ?? null,
            'district_id' => $data['district_id'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'bio' => $data['bio'] ?? null,
            'website' => $data['website'] ?? null
        ]);
    }

    public function updateProfile($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        foreach (['name', 'province_id', 'district_id', 'profile_photo', 'latitude', 'longitude', 'phone', 'whatsapp', 'bio', 'website'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) return true;
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function updateKarma($id, $points) {
        $sql = "UPDATE users SET karma_score = karma_score + :points WHERE id = :id";
        return $this->pdo->prepare($sql)->execute(['id' => $id, 'points' => $points]);
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $sql = "SELECT id, name, email, profile_photo, province_id, district_id, karma_score, latitude, longitude, phone, whatsapp, bio, website, created_at FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function updatePassword($id, $newPassword) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute(['id' => $id, 'password' => $hashed]);
    }

    public function deleteUser($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function blockUser($blocker_id, $blocked_id) {
        $sql = "INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (:blocker, :blocked)";
        return $this->pdo->prepare($sql)->execute(['blocker' => $blocker_id, 'blocked' => $blocked_id]);
    }

    public function unblockUser($blocker_id, $blocked_id) {
        $sql = "DELETE FROM blocked_users WHERE blocker_id = :blocker AND blocked_id = :blocked";
        return $this->pdo->prepare($sql)->execute(['blocker' => $blocker_id, 'blocked' => $blocked_id]);
    }

    public function getBlockedUsers($user_id) {
        $sql = "SELECT b.blocked_id, u.name, u.profile_photo FROM blocked_users b 
                JOIN users u ON b.blocked_id = u.id WHERE b.blocker_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll();
    }

    public function storeResetToken($email, $token) {
        $sql = "INSERT INTO password_resets (email, token) VALUES (:email, :token)";
        return $this->pdo->prepare($sql)->execute(['email' => $email, 'token' => $token]);
    }

    public function verifyResetToken($email, $token) {
        $sql = "SELECT * FROM password_resets WHERE email = :email AND token = :token AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email, 'token' => $token]);
        return $stmt->fetch();
    }

    public function deleteResetTokens($email) {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE email = :email");
        return $stmt->execute(['email' => $email]);
    }
}
