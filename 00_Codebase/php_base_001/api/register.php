<?php
/**
 * Register: create dashboard user (this app only; no proxy).
 * POST username, email, password. Returns success or error.
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

$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$username = trim((string) ($input['username'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username, email and password are required']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email']);
    exit;
}

$pdo = db_connect($config);
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database not available']);
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $email, $passwordHash, 'user']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Username or email already exists']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed']);
    exit;
}

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Registered. You can now sign in.']);
