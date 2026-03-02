<?php
/**
 * Login: verify credentials against dashboard users, issue JWT (this app only; no proxy).
 * POST username, password. Returns access_token and refresh_token.
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
$username = trim((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');
$secret = $config['jwt_secret'] ?? '';

if ($username === '' || $password === '' || $secret === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit;
}

$pdo = db_connect($config);
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service unavailable']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    exit;
}

$userId = (string) $user['id'];
$role = isset($user['role']) ? (string) $user['role'] : 'user';
$accessPayload = [
    'user_id'  => $userId,
    'username' => $user['username'],
    'email'    => $user['email'],
    'role'     => $role,
];
$refreshPayload = ['user_id' => $userId, 'type' => 'refresh'];

$accessToken  = jwt_create($accessPayload, $secret, 3600);       // 1 hour
$refreshToken = jwt_create($refreshPayload, $secret, 604800);      // 7 days

http_response_code(200);
echo json_encode([
    'success'       => true,
    'access_token'  => $accessToken,
    'refresh_token' => $refreshToken,
]);
