<?php
/**
 * Reign of Play website PHP configuration (used by Dutch dashboard and other RoP apps).
 * Loads from environment or .env (not committed). Never expose JWT_SECRET or
 * DUTCH_MT_DASHBOARD_SERVICE_KEY to the frontend.
 *
 * JWT (this app only):
 * - Only the signing secret (JWT_SECRET) is in config. PHP creates tokens at login and verifies them on protected requests.
 * - No proxy to main app for auth.
 */

declare(strict_types=1);

// Load env file if present (do not commit). Prefer .env.rop_website (deploy source); .env is legacy.
$envCandidates = [__DIR__ . '/.env.rop_website', __DIR__ . '/.env'];
foreach ($envCandidates as $envPath) {
    if (!is_readable($envPath)) {
        continue;
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
            $key = trim($m[1]);
            $value = trim($m[2]);
            $value = preg_replace('/^["\']|["\']$/', '', $value);
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    break;
}

return [
    'python_api_base_url' => rtrim(getenv('PYTHON_API_BASE_URL') ?: '', '/'),
    'service_key'         => getenv('DUTCH_MT_DASHBOARD_SERVICE_KEY') ?: '',
    'jwt_secret'          => getenv('JWT_SECRET') ?: '',
    'mail_from'           => getenv('MAIL_FROM') ?: 'noreply@reignofplay.com',
    'mail_from_name'      => getenv('MAIL_FROM_NAME') ?: 'Dutch.mt',
    'mail_contact_to'     => getenv('MAIL_CONTACT_TO') ?: (getenv('MAIL_FROM') ?: 'hello@reignofplay.com'),
    // Comma-separated inbox addresses contact forms may target (open-relay prevention).
    'mail_contact_allowlist' => array_values(array_filter(array_map(
        static function (string $e): string {
            return strtolower(trim($e));
        },
        explode(',', (string) (getenv('MAIL_CONTACT_ALLOWLIST') ?: ''))
    ))),
    // Optional source→inbox map: ROP:a@x.com,Dutch:b@x.com,Portfolio:c@x.com
    'mail_contact_by_source' => (static function (): array {
        $raw = (string) (getenv('MAIL_CONTACT_BY_SOURCE') ?: '');
        $map = [];
        if ($raw === '') {
            return $map;
        }
        foreach (explode(',', $raw) as $pair) {
            $pair = trim($pair);
            if ($pair === '' || strpos($pair, ':') === false) {
                continue;
            }
            [$src, $email] = array_map('trim', explode(':', $pair, 2));
            if ($src !== '' && $email !== '') {
                $map[$src] = strtolower($email);
            }
        }
        return $map;
    })(),
    'mail_smtp_enabled'   => filter_var(getenv('MAIL_SMTP_ENABLED'), FILTER_VALIDATE_BOOLEAN),
    'mail_smtp_host'      => getenv('MAIL_SMTP_HOST') ?: 'smtp.gmail.com',
    'mail_smtp_port'      => (int) (getenv('MAIL_SMTP_PORT') ?: '587'),
    'mail_smtp_encrypt'   => strtolower(getenv('MAIL_SMTP_ENCRYPT') ?: 'tls'),
    'mail_smtp_user'      => getenv('MAIL_SMTP_USER') ?: '',
    'mail_smtp_password'  => getenv('MAIL_SMTP_PASSWORD') ?: '',
    'db'                  => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'name'     => getenv('DB_NAME') ?: 'dutch_dashboard',
        'user'     => getenv('DB_USER') ?: 'dutch_dash',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
];
