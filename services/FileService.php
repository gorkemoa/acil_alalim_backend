<?php
// services/FileService.php

class FileService {
    private static $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    private static $maxSize = 20 * 1024 * 1024; // 20MB

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
            self::compressIfPossible($targetPath, $ext);
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
                self::compressIfPossible($targetPath, $ext);
                return ["path" => $fileName];
            }
        }
        return ["error" => "Invalid image format."];
    }

    /**
     * Best-effort compression to reduce file size without changing filename.
     */
    private static function compressIfPossible(string $path, string $ext): void {
        $ext = strtolower($ext);
        // Skip very small files
        if (!file_exists($path) || filesize($path) < 200 * 1024) {
            return;
        }

        // Only compress known raster formats
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return;
        }

        $image = @imagecreatefromstring(@file_get_contents($path));
        if (!$image) {
            return;
        }

        // Choose quality settings
        $jpegQuality = 80; // 0-100
        $pngCompression = 6; // 0-9 (higher is smaller)
        $webpQuality = 80; // 0-100

        switch ($ext) {
            case 'png':
                @imagepng($image, $path, $pngCompression);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    @imagewebp($image, $path, $webpQuality);
                    break;
                }
                // fall through to jpeg if webp unsupported
            case 'jpg':
            case 'jpeg':
            default:
                @imagejpeg($image, $path, $jpegQuality);
                break;
        }
        imagedestroy($image);
    }
}
