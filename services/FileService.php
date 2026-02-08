<?php
// services/FileService.php

class FileService {
    private static $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];
    private static $maxInputSize = 128 * 1024 * 1024; // 128MB input hard limit
    private static $targetMaxSize = 3 * 1024 * 1024; // Target <= 3MB per image
    private static $maxDimension = 2200; // Initial max width/height before iterative squeeze
    private static $minCompressBytes = 150 * 1024; // Skip tiny assets

    public static function upload($file, $targetDir) {
        if (!isset($file)) {
            return ["error" => "File upload error."];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ["error" => self::translateUploadError((int)$file['error'])];
        }

        if (($file['size'] ?? 0) > self::$maxInputSize) {
            return ["error" => "File is too large (max 128MB input)."];
        }

        $ext = self::normalizeExtension(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions)) {
            return ["error" => "Invalid file type. Allowed: " . implode(', ', self::$allowedExtensions)];
        }

        $fileName = uniqid('img_') . '.' . $ext;
        $targetPath = $targetDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $normalized = self::normalizeForStorage($targetPath, $ext);
            if (!$normalized['ok']) {
                self::cleanupTemporaryPaths([$targetPath, $normalized['path'] ?? null]);
                return ["error" => $normalized['error']];
            }
            return ["path" => basename($normalized['path'])];
        }

        return ["error" => "Failed to save file."];
    }

    public static function uploadBase64($base64String, $targetDir) {
        if (preg_match('/^data:image\/([a-zA-Z0-9+.-]+);base64,/', $base64String, $type)) {
            $data = substr($base64String, strpos($base64String, ',') + 1);
            $ext = self::normalizeExtension($type[1]);

            if (!in_array($ext, self::$allowedExtensions)) {
                return ["error" => "Invalid image type."];
            }

            $data = base64_decode($data, true);
            if ($data === false) return ["error" => "Invalid base64 data."];

            if (strlen($data) > self::$maxInputSize) {
                return ["error" => "File is too large (max 128MB input)."];
            }

            $fileName = uniqid('img_') . '.' . $ext;
            $targetPath = $targetDir . $fileName;

            if (file_put_contents($targetPath, $data)) {
                $normalized = self::normalizeForStorage($targetPath, $ext);
                if (!$normalized['ok']) {
                    self::cleanupTemporaryPaths([$targetPath, $normalized['path'] ?? null]);
                    return ["error" => $normalized['error']];
                }
                return ["path" => basename($normalized['path'])];
            }
        }
        return ["error" => "Invalid image format."];
    }

    private static function normalizeForStorage(string $path, string $ext): array {
        clearstatcache(true, $path);
        if (!file_exists($path)) {
            return ['ok' => false, 'path' => $path, 'error' => 'Failed to save file.'];
        }

        $workingPath = $path;
        $workingExt = self::normalizeExtension($ext);

        if (in_array($workingExt, ['heic', 'heif'], true)) {
            $jpegPath = self::convertHeicToJpeg($workingPath);
            if ($jpegPath) {
                @unlink($workingPath);
                $workingPath = $jpegPath;
                $workingExt = 'jpg';
            } else {
                clearstatcache(true, $workingPath);
                if (filesize($workingPath) > self::$targetMaxSize) {
                    return [
                        'ok' => false,
                        'path' => $workingPath,
                        'error' => 'HEIC/HEIF image is too large and server cannot convert it. Install Imagick or upload JPG/PNG/WEBP.'
                    ];
                }
                return ['ok' => true, 'path' => $workingPath];
            }
        }

        if (in_array($workingExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            self::squeezeToTarget($workingPath, $workingExt);
            clearstatcache(true, $workingPath);

            if (filesize($workingPath) > self::$targetMaxSize && !in_array($workingExt, ['jpg', 'jpeg'], true)) {
                $jpegPath = self::convertToJpeg($workingPath);
                if ($jpegPath) {
                    @unlink($workingPath);
                    $workingPath = $jpegPath;
                    $workingExt = 'jpg';
                    self::squeezeToTarget($workingPath, $workingExt);
                }
            }
        }

        clearstatcache(true, $workingPath);
        if (filesize($workingPath) > self::$targetMaxSize) {
            return ['ok' => false, 'path' => $workingPath, 'error' => 'Image could not be reduced below 3MB.'];
        }

        return ['ok' => true, 'path' => $workingPath];
    }

    private static function squeezeToTarget(string $path, string $ext): void {
        clearstatcache(true, $path);
        if (!file_exists($path)) return;
        if (filesize($path) < self::$minCompressBytes) return;
        if (filesize($path) <= self::$targetMaxSize) return;

        $maxDim = self::$maxDimension;
        $jpegQuality = 84;
        $webpQuality = 84;
        $pngCompression = 7;

        for ($i = 0; $i < 10; $i++) {
            self::reencodeImage($path, $ext, $maxDim, $jpegQuality, $pngCompression, $webpQuality);
            clearstatcache(true, $path);
            if (filesize($path) <= self::$targetMaxSize) {
                return;
            }

            $maxDim = max(700, (int)round($maxDim * 0.85));
            $jpegQuality = max(40, $jpegQuality - 6);
            $webpQuality = max(40, $webpQuality - 6);
            $pngCompression = min(9, $pngCompression + 1);
        }
    }

    private static function reencodeImage(string $path, string $ext, int $maxDim, int $jpegQuality, int $pngCompression, int $webpQuality): void {
        $ext = self::normalizeExtension($ext);
        $imageData = @file_get_contents($path);
        if ($imageData === false) return;

        $src = @imagecreatefromstring($imageData);
        if (!$src) return;

        $width = imagesx($src);
        $height = imagesy($src);
        $ratio = min(1, min($maxDim / max(1, $width), $maxDim / max(1, $height)));
        $targetW = max(1, (int)round($width * $ratio));
        $targetH = max(1, (int)round($height * $ratio));

        $dst = imagecreatetruecolor($targetW, $targetH);
        if (in_array($ext, ['png', 'webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
        } else {
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

        switch ($ext) {
            case 'png':
                @imagepng($dst, $path, $pngCompression);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    @imagewebp($dst, $path, $webpQuality);
                }
                break;
            case 'jpg':
            case 'jpeg':
            default:
                @imagejpeg($dst, $path, $jpegQuality);
                break;
        }

        imagedestroy($dst);
        imagedestroy($src);
    }

    private static function convertToJpeg(string $path): ?string {
        $imageData = @file_get_contents($path);
        if ($imageData === false) return null;

        $src = @imagecreatefromstring($imageData);
        if (!$src) return null;

        $width = imagesx($src);
        $height = imagesy($src);
        $dst = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $width, $height, $white);
        imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);

        $jpegPath = self::replaceExtension($path, 'jpg');
        $ok = @imagejpeg($dst, $jpegPath, 84);

        imagedestroy($dst);
        imagedestroy($src);

        return $ok ? $jpegPath : null;
    }

    private static function convertHeicToJpeg(string $path): ?string {
        if (!class_exists('Imagick')) {
            return null;
        }

        try {
            $imagick = new Imagick();
            $imagick->readImage($path);
            $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(84);

            $jpegPath = self::replaceExtension($path, 'jpg');
            $ok = $imagick->writeImage($jpegPath);
            $imagick->clear();
            $imagick->destroy();

            return $ok ? $jpegPath : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function replaceExtension(string $path, string $ext): string {
        $dotPos = strrpos($path, '.');
        if ($dotPos === false) {
            return $path . '.' . $ext;
        }
        return substr($path, 0, $dotPos + 1) . $ext;
    }

    private static function normalizeExtension(string $ext): string {
        $ext = strtolower(trim($ext));
        if ($ext === 'jpeg') return 'jpeg';
        if ($ext === 'jpg') return 'jpg';
        if ($ext === 'png') return 'png';
        if ($ext === 'webp') return 'webp';
        if ($ext === 'heic' || $ext === 'x-heic' || $ext === 'heic-sequence') return 'heic';
        if ($ext === 'heif' || $ext === 'x-heif' || $ext === 'heif-sequence') return 'heif';
        return $ext;
    }

    private static function translateUploadError(int $code): string {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds server upload limit. Increase upload_max_filesize and post_max_size.';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary upload directory.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the upload.';
            default:
                return 'File upload error.';
        }
    }

    private static function cleanupTemporaryPaths(array $paths): void {
        foreach ($paths as $path) {
            if (!empty($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }
}
