<?php
/**
 * Backend health check: requires JWT, then calls Python /service/health with X-Service-Key.
 * Returns Python's health response to the frontend.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../lib/python_client.php';

// 1. Verify JWT locally (no call to Python for auth)
$payload = require_jwt($config);

// 2. Call Python health with service key
$baseUrl = $config['python_api_base_url'] ?? '';
$serviceKey = $config['service_key'] ?? '';
if ($baseUrl === '' || $serviceKey === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Dashboard not configured for Python API',
        'dashboard' => 'ok',
        'python'  => null,
    ]);
    exit;
}

$result = python_get($baseUrl, '/service/health', $serviceKey);
$pythonBody = json_decode($result['body'], true);
if ($pythonBody === null) {
    $pythonBody = ['raw' => $result['body']];
}

// 3. Return combined status: dashboard (JWT verified) + Python response
http_response_code($result['status'] >= 200 && $result['status'] < 300 ? 200 : 502);
echo json_encode([
    'success'   => $result['status'] >= 200 && $result['status'] < 300,
    'dashboard' => 'ok',
    'jwt_user_id' => $payload['user_id'] ?? null,
    'python'    => [
        'status_code' => $result['status'],
        'body'        => $pythonBody,
    ],
]);
