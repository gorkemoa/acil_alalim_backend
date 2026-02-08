<?php
// scripts/import_data.php

require_once __DIR__ . '/../api/db.php';

function importProvinces($pdo, $filePath) {
    echo "Importing provinces...\n";
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);
    
    // Find the data array in the phpMyAdmin export format
    $provinces = [];
    foreach ($data as $item) {
        if (isset($item['type']) && $item['type'] === 'table' && $item['name'] === 'il') {
            $provinces = $item['data'];
            break;
        }
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO provinces (id, name) VALUES (:id, :name)");
    foreach ($provinces as $province) {
        $stmt->execute([
            'id' => $province['id'],
            'name' => $province['name']
        ]);
    }
    echo "Done.\n";
}

function importDistricts($pdo, $filePath) {
    echo "Importing districts...\n";
    $json = file_get_contents($filePath);
    $data = json_decode($json, true);
    
    $districts = [];
    foreach ($data as $item) {
        if (isset($item['type']) && $item['type'] === 'table' && $item['name'] === 'ilce') {
            $districts = $item['data'];
            break;
        }
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO districts (id, province_id, name) VALUES (:id, :province_id, :name)");
    foreach ($districts as $district) {
        $stmt->execute([
            'id' => $district['id'],
            'province_id' => $district['il_id'],
            'name' => $district['name']
        ]);
    }
    echo "Done.\n";
}

function importCategories($pdo, $filePath) {
    echo "Importing categories...\n";
    $json = file_get_contents($filePath);
    $categories = json_decode($json, true);

    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (id, parent_id, name, slug, url_path) VALUES (:id, :parent_id, :name, :slug, :url_path)");
    foreach ($categories as $cat) {
        $stmt->execute([
            'id' => $cat['id'],
            'parent_id' => $cat['parent_id'],
            'name' => $cat['ad'],
            'slug' => $cat['slug'],
            'url_path' => $cat['url_path']
        ]);
    }
    echo "Done.\n";
}

try {
    importProvinces($pdo, __DIR__ . '/../il.json');
    importDistricts($pdo, __DIR__ . '/../ilce.json');
    importCategories($pdo, __DIR__ . '/../kategoriler.json');
    echo "All data imported successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
