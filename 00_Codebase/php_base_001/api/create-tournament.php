<?php
/**
 * Create tournament: verify JWT locally, then POST to Python /service/dutch/create-tournaments
 * with X-Service-Key. Returns Python response.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$config = require dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/jwt.php';
require_once dirname(__DIR__) . '/lib/python_client.php';

$payload = require_jwt($config);

$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$baseUrl = $config['python_api_base_url'] ?? '';
$serviceKey = $config['service_key'] ?? '';
if ($baseUrl === '' || $serviceKey === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service not configured']);
    exit;
}

$result = python_post(
    $baseUrl,
    '/service/dutch/create-tournaments',
    $serviceKey,
    $input
);
http_response_code($result['status']);
echo $result['body'];
