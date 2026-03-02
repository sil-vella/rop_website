<?php
/**
 * Refresh: verify refresh token, issue new access (and refresh) token (this app only; no proxy).
 * POST refresh_token. Returns access_token and refresh_token.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/jwt.php';

$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$refreshToken = (string) ($input['refresh_token'] ?? '');
$secret = $config['jwt_secret'] ?? '';

if ($refreshToken === '' || $secret === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Refresh token required']);
    exit;
}

$payload = jwt_verify($refreshToken, $secret);
if (!$payload || (isset($payload['type']) && $payload['type'] !== 'refresh')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired refresh token']);
    exit;
}

$userId = $payload['user_id'] ?? '';
if ($userId === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid refresh token']);
    exit;
}

$pdo = db_connect($config);
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service unavailable']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$role = isset($user['role']) ? (string) $user['role'] : 'user';
$accessPayload = [
    'user_id'  => (string) $user['id'],
    'username' => $user['username'],
    'email'    => $user['email'],
    'role'     => $role,
];
$refreshPayload = ['user_id' => (string) $user['id'], 'type' => 'refresh'];

$accessToken  = jwt_create($accessPayload, $secret, 3600);
$refreshTokenNew = jwt_create($refreshPayload, $secret, 604800);

http_response_code(200);
echo json_encode([
    'success'       => true,
    'access_token'  => $accessToken,
    'refresh_token' => $refreshTokenNew,
]);
