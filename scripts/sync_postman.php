<?php
// scripts/sync_postman.php

$collectionPath = __DIR__ . '/../postman_collection.json';
if (!file_exists($collectionPath)) {
    fwrite(STDERR, "postman_collection.json not found\n");
    exit(1);
}

$raw = file_get_contents($collectionPath);
$collection = json_decode($raw, true);
if (!is_array($collection)) {
    fwrite(STDERR, "Invalid JSON in postman_collection.json\n");
    exit(1);
}

function updateRequestByName(array &$items, string $name, callable $updater) {
    foreach ($items as $idx => &$item) {
        if (($item['name'] ?? '') === $name) {
            $updater($item);
            return true;
        }
        if (isset($item['item']) && is_array($item['item'])) {
            if (updateRequestByName($item['item'], $name, $updater)) {
                return true;
            }
        }
    }
    return false;
}

function setJsonBody(array &$request, array $payload) {
    $request['body'] = [
        'mode' => 'raw',
        'raw' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        'options' => [
            'raw' => [
                'language' => 'json'
            ]
        ]
    ];
}

function setTestScript(array &$item, array $lines) {
    $item['event'] = [[
        'listen' => 'test',
        'script' => [
            'type' => 'text/javascript',
            'exec' => $lines
        ]
    ]];
}

if (!isset($collection['item']) || !is_array($collection['item'])) {
    fwrite(STDERR, "Invalid collection structure\n");
    exit(1);
}

// Register payload standard
updateRequestByName($collection['item'], 'Register (Üye Ol)', function (&$register) {
    if (!isset($register['request'])) return;
    setJsonBody($register['request'], [
        'name' => 'Gorkem',
        'surname' => 'Ozturk',
        'email' => 'gorkem{{$timestamp}}@example.com',
        'password' => 'Gorkem123.'
    ]);
    setTestScript($register, [
        'pm.test("Status is 201", function () {',
        '    pm.response.to.have.status(201);',
        '});',
        'let jsonData = {};',
        'try { jsonData = pm.response.json(); } catch (e) {}',
        'if (jsonData.token) {',
        '    pm.collectionVariables.set("token", jsonData.token);',
        '}',
        'if (jsonData.user && jsonData.user.id) {',
        '    pm.collectionVariables.set("userId", String(jsonData.user.id));',
        '}'
    ]);
});

// Login test script standard
updateRequestByName($collection['item'], 'Login (Giriş)', function (&$login) {
    if (!isset($login['request'])) return;
    setTestScript($login, [
        'pm.test("Status is 200", function () {',
        '    pm.response.to.have.status(200);',
        '});',
        'let jsonData = {};',
        'try { jsonData = pm.response.json(); } catch (e) {}',
        'if (jsonData.token) {',
        '    pm.collectionVariables.set("token", jsonData.token);',
        '}',
        'if (jsonData.user && jsonData.user.id) {',
        '    pm.collectionVariables.set("userId", String(jsonData.user.id));',
        '}'
    ]);
});

// Update profile token refresh script standard
updateRequestByName($collection['item'], 'Update Profile', function (&$updateProfile) {
    if (!isset($updateProfile['request'])) return;
    setTestScript($updateProfile, [
        'pm.test("Status is 200", function () {',
        '    pm.response.to.have.status(200);',
        '});',
        'let jsonData = {};',
        'try { jsonData = pm.response.json(); } catch (e) {}',
        'if (jsonData.token) {',
        '    pm.collectionVariables.set("token", jsonData.token);',
        '}'
    ]);
});

$encoded = json_encode($collection, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents($collectionPath, $encoded . PHP_EOL);
echo "postman_collection.json synced\n";
