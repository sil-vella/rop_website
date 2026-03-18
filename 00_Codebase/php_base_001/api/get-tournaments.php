<?php
/**
 * Get tournaments: requires JWT (user auth), then calls Python GET /service/dutch/get-tournaments
 * with X-Service-Key and returns the Python response to the frontend.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../lib/python_client.php';

// 1. Verify JWT (user auth)
$payload = require_jwt($config);
// require_jwt exits on failure; we only get here when JWT is valid

// 2. Call Python with service token
$baseUrl = $config['python_api_base_url'] ?? '';
$serviceKey = $config['service_key'] ?? '';
if ($baseUrl === '' || $serviceKey === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Dashboard not configured for Python API',
    ]);
    exit;
}

$result = python_get($baseUrl, '/service/dutch/get-tournaments', $serviceKey);

// 3. Forward Python response (status + body) so frontend gets same shape (e.g. data.tournaments)
http_response_code($result['status'] >= 200 && $result['status'] < 300 ? 200 : $result['status']);
echo $result['body'];
