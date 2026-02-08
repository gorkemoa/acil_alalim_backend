<?php
// services/FileService.php

class FileService {
    private static $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    private static $maxSize = 5 * 1024 * 1024; // 5MB

    public static function upload($file, $targetDir) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ["error" => "File upload error."];
        }

        if ($file['size'] > self::$maxSize) {
            return ["error" => "File is too large (max 5MB)."];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions)) {
            return ["error" => "Invalid file type. Allowed: " . implode(', ', self::$allowedExtensions)];
        }

        $fileName = uniqid('img_') . '.' . $ext;
        $targetPath = $targetDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ["path" => $fileName];
        }

        return ["error" => "Failed to save file."];
    }

    public static function uploadBase64($base64String, $targetDir) {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
            $data = substr($base64String, strpos($base64String, ',') + 1);
            $ext = strtolower($type[1]); // jpg, png, gif

            if (!in_array($ext, self::$allowedExtensions)) {
                return ["error" => "Invalid image type."];
            }

            $data = base64_decode($data);
            if ($data === false) return ["error" => "Invalid base64 data."];
            
            if (strlen($data) > self::$maxSize) {
                return ["error" => "File is too large."];
            }

            $fileName = uniqid('img_') . '.' . $ext;
            $targetPath = $targetDir . $fileName;

            if (file_put_contents($targetPath, $data)) {
                return ["path" => $fileName];
            }
        }
        return ["error" => "Invalid image format."];
    }
}
