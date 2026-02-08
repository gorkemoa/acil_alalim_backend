<?php
// api/db.php

$charset = 'utf8mb4';

// Optional: allow overriding from a local .env without extra deps
if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env') as $line) {
        if (strpos(trim($line), '=') !== false) {
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            if ($k && !getenv($k)) putenv("{$k}={$v}");
        }
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_DATABASE') ?: 'acil_alalim';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'Gorkem123.';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true, // enable emulation to avoid native placeholder edge-cases
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
