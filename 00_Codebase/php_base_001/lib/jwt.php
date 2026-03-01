<?php
/**
 * JWT create and verify for this app (no proxy to main app).
 * Only the signing secret is in config (JWT_SECRET). Tokens are created here at login and
 * verified here on protected requests.
 */

declare(strict_types=1);

/**
 * Create a signed JWT (HS256).
 *
 * @param array $payload Claims (e.g. user_id, username, exp, iat)
 * @param string $secret JWT_SECRET
 * @param int $ttlSeconds Optional expiry in seconds from now (adds exp to payload)
 * @return string JWT string
 */
function jwt_create(array $payload, string $secret, int $ttlSeconds = 3600): string
{
    $now = time();
    if (!isset($payload['iat'])) {
        $payload['iat'] = $now;
    }
    if (!isset($payload['exp'])) {
        $payload['exp'] = $now + $ttlSeconds;
    }
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $headerB64 = base64_encode_url(json_encode($header));
    $payloadB64 = base64_encode_url(json_encode($payload));
    $signatureInput = $headerB64 . '.' . $payloadB64;
    $sig = base64_encode_url(hash_hmac('sha256', $signatureInput, $secret, true));
    return $signatureInput . '.' . $sig;
}

/**
 * Decode and verify JWT. Returns payload array or null on failure.
 *
 * @param string $token Raw JWT (with or without "Bearer " prefix)
 * @param string $secret JWT_SECRET
 * @return array|null Payload (e.g. ['user_id' => ..., 'exp' => ...]) or null
 */
function jwt_verify(string $token, string $secret): ?array
{
    $token = preg_replace('/^Bearer\s+/i', '', trim($token));
    if ($token === '' || $secret === '') {
        return null;
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    $payloadB64 = $parts[1];
    $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'), true);
    if ($payloadJson === false) {
        return null;
    }

    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return null;
    }

    // Verify signature (HMAC SHA-256)
    $signatureInput = $parts[0] . '.' . $parts[1];
    $expectedSig = base64_encode_url(
        hash_hmac('sha256', $signatureInput, $secret, true)
    );
    $actualSig = $parts[2];
    if (!hash_equals($expectedSig, $actualSig)) {
        return null;
    }

    // Expiry
    if (isset($payload['exp']) && is_numeric($payload['exp'])) {
        if ((int) $payload['exp'] < time()) {
            return null;
        }
    }

    return $payload;
}

/**
 * Base64 URL-safe encode (no padding).
 */
function base64_encode_url(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Get Bearer token from request and verify. Sends 401 and exits if invalid.
 *
 * @param array $config Config array from config.php (must have 'jwt_secret')
 * @return array Verified JWT payload (e.g. user_id)
 */
function require_jwt(array $config): array
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $payload = jwt_verify($auth, $config['jwt_secret'] ?? '');
    if ($payload === null) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    return $payload;
}
