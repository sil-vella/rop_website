<?php
/**
 * Security helpers for public API endpoints: sanitize and validate input,
 * filter harmful data (XSS, injection patterns, control chars).
 */

declare(strict_types=1);

/** Max total JSON body size (bytes) for public endpoints */
const PUBLIC_API_MAX_BODY_LENGTH = 4096;

/** Patterns considered harmful (reject request if found in string input) */
const PUBLIC_API_HARMFUL_PATTERNS = [
    '/<script\b/i',
    '/javascript\s*:/i',
    '/vbscript\s*:/i',
    '/<\s*iframe/i',
    '/\b(SELECT|INSERT|UPDATE|DELETE|UNION|DROP|ALTER|EXEC|EXECUTE)\s+/i',
    '/\x00/',  // null byte
];

/**
 * CORS headers for public API endpoints (cross-origin from reignofplay.com, dutch.mt, etc.).
 */
function public_api_send_cors_headers(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

/**
 * Send 400 JSON error and exit.
 */
function public_api_security_fail(string $error): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    error_log(sprintf(
        'public_api_security_fail: %s | origin=%s | referer=%s | method=%s | uri=%s',
        $error,
        $origin,
        $referer,
        $_SERVER['REQUEST_METHOD'] ?? '',
        $_SERVER['REQUEST_URI'] ?? ''
    ));
    header('Content-Type: application/json; charset=utf-8');
    public_api_send_cors_headers();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

/**
 * Sanitize a string: trim, strip tags, remove null bytes and control chars, enforce max length.
 *
 * @param string $value Raw value
 * @param int $maxLength Max length (default 1024)
 * @return string Sanitized value
 */
function public_api_sanitize_string(string $value, int $maxLength = 1024, bool $preserveNewlines = false): string
{
    $value = trim($value);
    $value = strip_tags($value);
    if ($preserveNewlines) {
        $value = str_replace(["\0", "\r"], '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    } else {
        $value = str_replace(["\0", "\r", "\n"], '', $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    }
    if (strlen($value) > $maxLength) {
        $value = substr($value, 0, $maxLength);
    }
    return $value;
}

/**
 * Check if a string contains harmful patterns; if so, call public_api_security_fail and exit.
 *
 * @param string $value Sanitized string to check
 * @param string $fieldName For error message
 */
function public_api_reject_harmful(string $value, string $fieldName): void
{
    foreach (PUBLIC_API_HARMFUL_PATTERNS as $pattern) {
        if (preg_match($pattern, $value)) {
            public_api_security_fail('Invalid characters in ' . $fieldName);
        }
    }
}

/**
 * Validate and sanitize input for a public endpoint using registry input_rules.
 * Returns only keys defined in rules; passwords are not returned.
 * On validation failure, sends 400 and exits.
 *
 * @param array $input Raw POST/JSON input (assoc)
 * @param array $inputRules From api_registry_get()['input_rules']
 * @return array Sanitized and validated input (only allowed keys; password fields omitted from return for logging/serialization)
 */
function public_api_filter_input(array $input, array $inputRules): array
{
    $out = [];

    foreach ($inputRules as $key => $rules) {
        $raw = $input[$key] ?? null;
        $required = $rules['required'] ?? false;

        if ($raw === null || $raw === '') {
            if ($required) {
                public_api_security_fail('Missing required field: ' . $key);
            }
            continue;
        }

        if (!is_string($raw) && !is_numeric($raw)) {
            public_api_security_fail('Invalid type for field: ' . $key);
        }
        $value = trim((string) $raw);

        $maxLength = $rules['max_length'] ?? 1024;
        $preserveNewlines = !empty($rules['multiline']);
        $value = public_api_sanitize_string($value, $maxLength, $preserveNewlines);

        if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
            public_api_security_fail('Field ' . $key . ' is too short');
        }

        if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
            public_api_security_fail('Invalid format for field: ' . $key);
        }

        if (isset($rules['filter'])) {
            if ($rules['filter'] === 'email') {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    public_api_security_fail('Invalid email');
                }
            }
        }

        if (empty($rules['skip_harmful'])) {
            public_api_reject_harmful($value, $key);
        }

        $out[$key] = $value;
    }

    return $out;
}

/**
 * Enforce max request body size for public endpoints. Call before json_decode.
 * On exceed: send 413 and exit.
 */
function public_api_enforce_body_length(): void
{
    $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($len > PUBLIC_API_MAX_BODY_LENGTH) {
        header('Content-Type: application/json; charset=utf-8');
        public_api_send_cors_headers();
        http_response_code(413);
        echo json_encode(['success' => false, 'error' => 'Request too large']);
        exit;
    }
}
