<?php
// api/index.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// If server ignores .htaccess/.user.ini, still enforce a soft cap and return JSON
$incomingSize = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
$maxBytes = 20 * 1024 * 1024; // 20MB
if ($incomingSize > $maxBytes) {
    http_response_code(413);
    echo json_encode(["error" => "Payload too large. Max 20MB."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';

spl_autoload_register(function ($class_name) {
    $paths = [__DIR__ . '/../controllers/', __DIR__ . '/../models/', __DIR__ . '/../services/'];
    foreach ($paths as $path) {
        $file = $path . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$path = $_GET['path'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$path = trim((string)$path, '/');

// Handle /api prefix (with or without trailing slash)
if (strpos($path, 'api') === 0) {
    $path = substr($path, 3);
    $path = trim($path, '/');
}

$pathParts = explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];

$resource = $pathParts[0] ?? '';
$id = $pathParts[1] ?? null;

switch ($resource) {
    case 'auth':
        $controller = new AuthController($pdo);
        if ($id == 'register') $controller->register();
        elseif ($id == 'login') $controller->login();
        elseif ($id == 'forgot-password') $controller->forgotPassword();
        elseif ($id == 'reset-password') $controller->resetPassword();
        elseif ($id == 'profile') {
            if ($method == 'GET') $controller->getProfile($pathParts[2] ?? null);
            elseif ($method == 'POST') $controller->updateProfile();
        }
        elseif ($id == 'my-products') {
            $controller = new NeedController($pdo);
            $controller->getMyProducts();
        }
        elseif ($id == 'delete-account') $controller->deleteAccount();
        break;

    case 'users':
        $controller = new AuthController($pdo);
        if ($id == 'block') $controller->block();
        elseif ($id == 'unblock') $controller->unblock();
        elseif ($id == 'blocked-list') $controller->getBlockedList();
        break;

    case 'products': // Mapping requested "products" to "needs"
    case 'needs':
        $controller = new NeedController($pdo);
        if ($method == 'GET') {
            if ($id === 'search') $controller->liveSearch();
            elseif ($id === 'near') $controller->getNear();
            elseif ($id) $controller->getOne($id);
            else $controller->getAll();
        } elseif ($method == 'POST') {
            if ($id === 'list') $controller->getAll(); // Body-based filtering support
            else $controller->create();
        } elseif ($method == 'PUT' && $id) {
            if ($pathParts[2] ?? '' === 'sponsor') $controller->setSponsor($id);
            else $controller->update($id);
        } elseif ($method == 'DELETE' && $id) {
            $controller->delete($id);
        }
        break;

    case 'favorites':
        $controller = new FavoriteController($pdo);
        if ($method == 'POST') $controller->add();
        elseif ($method == 'GET') $controller->getAll();
        elseif ($method == 'DELETE') $controller->remove();
        break;

    case 'comments':
        $controller = new CommentController($pdo);
        if ($method == 'POST') $controller->add();
        elseif ($method == 'GET' && $id) $controller->getByNeed($id);
        break;

    case 'notifications':
        $controller = new NotificationController($pdo);
        if ($method == 'GET') $controller->getAll();
        elseif ($method == 'PUT' && $id) $controller->markAsRead($id);
        break;

    case 'ratings':
        $controller = new RatingController($pdo);
        if ($method == 'POST') $controller->add();
        elseif ($method == 'GET' && $id) $controller->getByUser($id);
        break;

    case 'reports':
        $controller = new ReportController($pdo);
        if ($method == 'POST') $controller->create();
        elseif ($method == 'GET') $controller->getAll();
        elseif ($method == 'PUT' && $id) $controller->updateStatus($id);
        break;

    case 'locations':
        $controller = new SystemController($pdo);
        if ($id === 'provinces') $controller->getProvinces();
        elseif ($id === 'districts' && isset($pathParts[2])) $controller->getDistricts($pathParts[2]);
        break;

    case 'categories':
        $controller = new SystemController($pdo);
        if ($method == 'GET') $controller->getCategories();
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found"]);
        break;
}
