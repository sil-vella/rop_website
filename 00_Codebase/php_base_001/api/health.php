<?php
/**
 * Health check: no auth. Returns 200 and simple status.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

http_response_code(200);
echo json_encode([
    'success' => true,
    'service' => 'dutch-mt-dashboard',
    'status'  => 'ok',
]);
