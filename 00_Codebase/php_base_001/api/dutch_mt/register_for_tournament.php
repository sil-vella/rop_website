<?php
/**
 * Register for tournament: public endpoint (no JWT, no service token).
 * Expects: name, surname, email, event_slug, event_name (hidden fields).
 * Sends confirmation email first; only if mail succeeds do we insert into dutch_mt_registrations.
 * Event is looked up by slug; if missing, created with event_name as the event name.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/lib/api_registry.php';
require_once $root . '/lib/public_api_security.php';
require_once $root . '/lib/db.php';
require_once $root . '/lib/mail_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$definition = api_registry_get('dutch_mt/register_for_tournament');
if ($definition === null || !api_registry_method_allowed($definition)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

public_api_enforce_body_length();
$rawInput = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$inputRules = $definition['input_rules'] ?? [];
if ($inputRules === []) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Endpoint not configured']);
    exit;
}

$filtered = public_api_filter_input($rawInput, $inputRules);

$name = trim($filtered['name'] ?? '');
$surname = trim($filtered['surname'] ?? '');
$email = trim($filtered['email'] ?? '');
$eventSlug = trim($filtered['event_slug'] ?? '');
$eventName = trim($filtered['event_name'] ?? '');

if ($eventSlug === '' || $eventName === '') {
    public_api_security_fail('Event (slug and name) are required');
}

$config = require $root . '/config.php';
$pdo = db_connect($config);
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service unavailable']);
    exit;
}

// Get or create event by slug; use event_name as the event name (party date)
$stmt = $pdo->prepare('SELECT id, name FROM dutch_mt_events WHERE slug = ? LIMIT 1');
$stmt->execute([$eventSlug]);
$event = $stmt->fetch();

if ($event) {
    $eventId = (int) $event['id'];
} else {
    try {
        $ins = $pdo->prepare('INSERT INTO dutch_mt_events (slug, name) VALUES (?, ?)');
        $ins->execute([$eventSlug, $eventName]);
        $eventId = (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Registration failed']);
        exit;
    }
}

// Count existing registrations for this event (before adding this user)
$countStmt = $pdo->prepare('SELECT COUNT(*) AS n FROM dutch_mt_registrations WHERE event_id = ?');
$countStmt->execute([$eventId]);
$existingCount = (int) ($countStmt->fetch()['n'] ?? 0);

$toName = $name . ($surname !== '' ? ' ' . $surname : '');

if ($existingCount >= 1) {
    // Tournament already has at least one registration; treat as full — send "full" email, do not add to DB
    $mailSent = mail_send_tournament_full($email, $toName, $eventName, $config);
    if (!$mailSent) {
        http_response_code(503);
        echo json_encode(['success' => false, 'error' => 'Could not send email. Please try again later.']);
        exit;
    }
    $data = ['name' => $name, 'surname' => $surname, 'email' => $email, 'event_name' => $eventName];
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Tournament is full. We will notify you if a place becomes available.',
        'data'    => $data,
    ]);
    exit;
}

// Less than 1 registered: send confirmation email first; only then insert
$mailSent = mail_send_registration_confirmation($email, $toName, $eventName, $config);

if (!$mailSent) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Could not send confirmation email. Please try again later.']);
    exit;
}

try {
    $ins = $pdo->prepare('INSERT INTO dutch_mt_registrations (event_id, name, surname, email) VALUES (?, ?, ?, ?)');
    $ins->execute([$eventId, $name, $surname, $email]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Already registered for this event']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Registration failed']);
    exit;
}

$data = [
    'name'       => $name,
    'surname'    => $surname,
    'email'      => $email,
    'event_name' => $eventName,
];

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Registration confirmed. Check your email.', 'data' => $data]);
