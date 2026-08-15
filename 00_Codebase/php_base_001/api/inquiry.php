<?php
/**
 * Site inquiry form: public endpoint (no JWT, no service token).
 * Expects: name, email, message; optional source, platform, recipient.
 * Sends email to allowlisted recipient (or MAIL_CONTACT_TO); Reply-To = submitter.
 *
 * Named inquiry.php (not contact.php) to avoid browser ad-blocker lists.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/lib/api_registry.php';
require_once $root . '/lib/public_api_security.php';
require_once $root . '/lib/mail_helper.php';

header('Content-Type: application/json; charset=utf-8');
public_api_send_cors_headers();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$definition = api_registry_get('inquiry');
if ($definition === null || !api_registry_method_allowed($definition)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

public_api_enforce_body_length();
$rawBody = file_get_contents('php://input') ?: '{}';
$rawInput = json_decode($rawBody, true) ?: [];
error_log(sprintf(
    'inquiry.php: origin=%s referer=%s source=%s recipient=%s keys=%s',
    $_SERVER['HTTP_ORIGIN'] ?? '',
    $_SERVER['HTTP_REFERER'] ?? '',
    is_string($rawInput['source'] ?? null) ? $rawInput['source'] : '',
    is_string($rawInput['recipient'] ?? null) ? $rawInput['recipient'] : '',
    implode(',', array_keys($rawInput))
));
$inputRules = $definition['input_rules'] ?? [];
if ($inputRules === []) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Endpoint not configured']);
    exit;
}

$filtered = public_api_filter_input($rawInput, $inputRules);

$name = trim($filtered['name'] ?? '');
$email = trim($filtered['email'] ?? '');
$message = trim($filtered['message'] ?? '');
$source = trim($filtered['source'] ?? '');
$platform = trim($filtered['platform'] ?? '');
$recipient = trim($filtered['recipient'] ?? '');

$config = require $root . '/config.php';

$resolvedTo = mail_resolve_contact_recipient(
    $config,
    $recipient !== '' ? $recipient : null,
    $source !== '' ? $source : null
);
if ($resolvedTo === null) {
    public_api_security_fail('Invalid or disallowed recipient');
}

$responseBody = json_encode([
    'success' => true,
    'message' => 'Message sent. We will get back to you soon.',
    'data'    => ['name' => $name, 'email' => $email],
]);

http_response_code(200);
header('Content-Length: ' . strlen($responseBody));
echo $responseBody;

ignore_user_abort(true);
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

$mailSent = mail_send_contact(
    $name,
    $email,
    $message,
    $config,
    $source !== '' ? $source : null,
    $platform !== '' ? $platform : null,
    $recipient !== '' ? $recipient : null
);
if (!$mailSent) {
    error_log(sprintf('inquiry.php: mail_send_contact failed for %s <%s> to=%s', $name, $email, $resolvedTo));
}
