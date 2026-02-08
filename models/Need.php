<?php
// models/Need.php

class Need {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getPdo() {
        return $this->pdo;
    }

    public function getAll(array $filters = [], array $pagination = []) {
        $where = ["n.status = 'active'"];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = "n.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['province_id'])) {
            $where[] = "n.province_id = :province_id";
            $params['province_id'] = $filters['province_id'];
        }
        if (!empty($filters['district_id'])) {
            $where[] = "n.district_id = :district_id";
            $params['district_id'] = $filters['district_id'];
        }
        if (!empty($filters['query'])) {
            $where[] = "(n.title LIKE :q OR n.description LIKE :q)";
            $params['q'] = '%' . $filters['query'] . '%';
        }
        if (!empty($filters['viewer_id'])) {
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu WHERE bu.blocker_id = :viewer AND bu.blocked_id = n.user_id)";
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu2 WHERE bu2.blocked_id = :viewer AND bu2.blocker_id = n.user_id)";
            $params['viewer'] = $filters['viewer_id'];
        }

        $order = "ORDER BY n.is_sponsor DESC, n.created_at DESC";
        if (!empty($filters['sort']) && $filters['sort'] === 'recent') {
            $order = "ORDER BY n.created_at DESC";
        } elseif (!empty($filters['sort']) && $filters['sort'] === 'distance' && !empty($filters['lat']) && !empty($filters['lng'])) {
            $order = "ORDER BY distance ASC";
        }

        $page = max((int)($pagination['page'] ?? 1), 1);
        $perPage = min(max((int)($pagination['per_page'] ?? 20), 1), 100);
        $offset = ($page - 1) * $perPage;

        $distanceSelect = "";
        if (!empty($filters['lat']) && !empty($filters['lng'])) {
            $distanceSelect = ", (6371 * acos(\n                    cos(radians(:lat)) * cos(radians(n.latitude)) *\n                    cos(radians(n.longitude) - radians(:lng)) +\n                    sin(radians(:lat)) * sin(radians(n.latitude))\n                )) AS distance";
            $params['lat'] = $filters['lat'];
            $params['lng'] = $filters['lng'];
        }

        $sql = "SELECT n.*, i.image_path as main_image, u.name as user_name, u.profile_photo as user_avatar, u.karma_score,
                p.name as province_name, d.name as district_name, c.name as category_name
                $distanceSelect
                FROM needs n 
                LEFT JOIN need_images i ON n.id = i.need_id AND i.is_main = 1
                JOIN users u ON n.user_id = u.id
                LEFT JOIN provinces p ON n.province_id = p.id
                LEFT JOIN districts d ON n.district_id = d.id
                LEFT JOIN categories c ON n.category_id = c.id
                WHERE " . implode(' AND ', $where) . " 
                $order
                LIMIT :offset, :perPage";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', (int)$perPage, PDO::PARAM_INT);
        $stmt->execute();
        $needs = $stmt->fetchAll();
        foreach ($needs as &$need) {
            if (!empty($need['main_image'])) {
                $need['main_image_url'] = "http://localhost:8000/uploads/needs/" . $need['main_image'];
            }
            if (!empty($need['user_avatar'])) {
                $need['user_avatar_url'] = "http://localhost:8000/uploads/profiles/" . $need['user_avatar'];
            }
        }

        $total = $this->countAll($filters);

        return [
            'data' => $needs,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int)ceil($total / $perPage)
            ]
        ];
    }

    private function countAll(array $filters = []) {
        $where = ["n.status = 'active'"];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = "n.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['province_id'])) {
            $where[] = "n.province_id = :province_id";
            $params['province_id'] = $filters['province_id'];
        }
        if (!empty($filters['district_id'])) {
            $where[] = "n.district_id = :district_id";
            $params['district_id'] = $filters['district_id'];
        }
        if (!empty($filters['query'])) {
            $where[] = "(n.title LIKE :q OR n.description LIKE :q)";
            $params['q'] = '%' . $filters['query'] . '%';
        }
        if (!empty($filters['viewer_id'])) {
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu WHERE bu.blocker_id = :viewer AND bu.blocked_id = n.user_id)";
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu2 WHERE bu2.blocked_id = :viewer AND bu2.blocker_id = n.user_id)";
            $params['viewer'] = $filters['viewer_id'];
        }

        $sql = "SELECT COUNT(*) as cnt FROM needs n WHERE " . implode(' AND ', $where);
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getById($id) {
        $sql = "SELECT n.*, u.name as user_name, u.profile_photo as user_avatar, u.karma_score as user_karma,
                p.name as province_name, d.name as district_name, c.name as category_name,
                up.name as user_province_name, ud.name as user_district_name
                FROM needs n 
                JOIN users u ON n.user_id = u.id
                LEFT JOIN provinces p ON n.province_id = p.id
                LEFT JOIN districts d ON n.district_id = d.id
                LEFT JOIN categories c ON n.category_id = c.id
                LEFT JOIN provinces up ON u.province_id = up.id
                LEFT JOIN districts ud ON u.district_id = ud.id
                WHERE n.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $need = $stmt->fetch();
        
        if ($need) {
            // Images
            $stmtImages = $this->pdo->prepare("SELECT * FROM need_images WHERE need_id = :need_id");
            $stmtImages->execute(['need_id' => $id]);
            $images = $stmtImages->fetchAll();
            foreach ($images as &$img) {
                $img['url'] = "http://localhost:8000/uploads/needs/" . $img['image_path'];
            }
            $need['images'] = $images;

            // User Avatar URL
            if ($need['user_avatar']) {
                $need['user_avatar_url'] = "http://localhost:8000/uploads/profiles/" . $need['user_avatar'];
            }
        }
        return $need;
    }

    public function getByUser($userId) {
        $sql = "SELECT n.*, i.image_path as main_image, p.name as province_name, d.name as district_name, c.name as category_name
                FROM needs n 
                LEFT JOIN need_images i ON n.id = i.need_id AND i.is_main = 1
                LEFT JOIN provinces p ON n.province_id = p.id
                LEFT JOIN districts d ON n.district_id = d.id
                LEFT JOIN categories c ON n.category_id = c.id
                WHERE n.user_id = :user_id
                ORDER BY n.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $needs = $stmt->fetchAll();
        foreach ($needs as &$need) {
            if ($need['main_image']) {
                $need['main_image_url'] = "http://localhost:8000/uploads/needs/" . $need['main_image'];
            }
        }
        return $needs;
    }

    public function create($data) {
        $sql = "INSERT INTO needs (user_id, title, description, category_id, latitude, longitude, province_id, district_id) 
                VALUES (:user_id, :title, :description, :category_id, :latitude, :longitude, :province_id, :district_id)";
        $stmt = $this->pdo->prepare($sql);
        if ($stmt->execute([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'category_id' => $data['category_id'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'province_id' => $data['province_id'],
            'district_id' => $data['district_id']
        ])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function addImage($need_id, $path, $isMain = 0) {
        $sql = "INSERT INTO need_images (need_id, image_path, is_main) VALUES (:need_id, :path, :is_main)";
        return $this->pdo->prepare($sql)->execute(['need_id' => $need_id, 'path' => $path, 'is_main' => $isMain]);
    }

    public function getImages($need_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM need_images WHERE need_id = :need_id");
        $stmt->execute(['need_id' => $need_id]);
        return $stmt->fetchAll();
    }

    public function deleteImages($need_id, array $imageIds) {
        if (empty($imageIds)) return true;
        $in = implode(',', array_fill(0, count($imageIds), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM need_images WHERE need_id = ? AND id IN ($in)");
        return $stmt->execute(array_merge([$need_id], $imageIds));
    }

    public function setMainImage($need_id, $image_id) {
        $this->pdo->prepare("UPDATE need_images SET is_main = 0 WHERE need_id = :nid")->execute(['nid' => $need_id]);
        $stmt = $this->pdo->prepare("UPDATE need_images SET is_main = 1 WHERE need_id = :nid AND id = :iid");
        return $stmt->execute(['nid' => $need_id, 'iid' => $image_id]);
    }

    public function isBlockedBetween($viewerId, $ownerId) {
        $sql = "SELECT 1 FROM blocked_users WHERE (blocker_id = :viewer AND blocked_id = :owner) OR (blocker_id = :owner AND blocked_id = :viewer) LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['viewer' => $viewerId, 'owner' => $ownerId]);
        return (bool)$stmt->fetchColumn();
    }

    public function findNearbyUsers($lat, $lng, $radius = 5) {
        $sql = "SELECT id, (6371 * acos(
                    cos(radians(:lat1)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(:lng)) +
                    sin(radians(:lat2)) *
                    sin(radians(latitude))
                )) AS distance FROM users 
                HAVING distance < :radius";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['lat1' => $lat, 'lat2' => $lat, 'lng' => $lng, 'radius' => $radius]);
        return $stmt->fetchAll();
    }

    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        $allowed = ['title', 'description', 'category_id', 'latitude', 'longitude', 'province_id', 'district_id', 'status', 'is_sponsor'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        if (empty($fields)) return true;
        $sql = "UPDATE needs SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM needs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function liveSearch($query, $viewerId = null) {
        $where = ["(n.title LIKE :query OR n.description LIKE :query OR p.name LIKE :query OR d.name LIKE :query OR c.name LIKE :query)", "n.status = 'active'"];
        $params = ['query' => "%$query%"];

        if ($viewerId) {
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu WHERE bu.blocker_id = :viewer AND bu.blocked_id = n.user_id)";
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu2 WHERE bu2.blocked_id = :viewer AND bu2.blocker_id = n.user_id)";
            $params['viewer'] = $viewerId;
        }

        $sql = "SELECT n.id, n.title, n.is_sponsor, n.category_id, p.name as province_name, d.name as district_name, c.name as category_name
                FROM needs n
                LEFT JOIN provinces p ON n.province_id = p.id
                LEFT JOIN districts d ON n.district_id = d.id
                LEFT JOIN categories c ON n.category_id = c.id
                WHERE " . implode(' AND ', $where) . " 
                ORDER BY n.is_sponsor DESC, n.created_at DESC LIMIT 10";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function toggleSponsor($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE needs SET is_sponsor = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function getNear($lat, $lng, $radius, $viewerId = null, $page = 1, $perPage = 20) {
        $where = ["n.status = 'active'"];
        $params = ['lat' => $lat, 'lng' => $lng, 'radius' => $radius];

        if ($viewerId) {
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu WHERE bu.blocker_id = :viewer AND bu.blocked_id = n.user_id)";
            $where[] = "NOT EXISTS (SELECT 1 FROM blocked_users bu2 WHERE bu2.blocked_id = :viewer AND bu2.blocker_id = n.user_id)";
            $params['viewer'] = $viewerId;
        }

        $page = max((int)$page, 1);
        $perPage = min(max((int)$perPage, 1), 100);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT n.*, u.name as user_name, u.karma_score as user_karma, i.image_path as main_image,
                p.name as province_name, d.name as district_name, c.name as category_name,
                (6371 * acos(
                    cos(radians(:lat)) *
                    cos(radians(n.latitude)) *
                    cos(radians(n.longitude) - radians(:lng)) +
                    sin(radians(:lat)) *
                    sin(radians(n.latitude))
                )) AS distance
                FROM needs n
                JOIN users u ON n.user_id = u.id
                LEFT JOIN need_images i ON n.id = i.need_id AND i.is_main = 1
                LEFT JOIN provinces p ON n.province_id = p.id
                LEFT JOIN districts d ON n.district_id = d.id
                LEFT JOIN categories c ON n.category_id = c.id
                WHERE " . implode(' AND ', $where) . "
                HAVING distance < :radius
                ORDER BY distance
                LIMIT :offset, :perPage";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', (int)$perPage, PDO::PARAM_INT);
        $stmt->execute();
        $needs = $stmt->fetchAll();
        foreach ($needs as &$need) {
            if (!empty($need['main_image'])) {
                $need['main_image_url'] = "http://localhost:8000/uploads/needs/" . $need['main_image'];
            }
        }
        return $needs;
    }
}
