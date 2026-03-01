<?php
/**
 * DB connection helper for this app (dashboard users, audit, etc.).
 */

declare(strict_types=1);

/**
 * Get PDO connection from config. Returns null if config or connection fails.
 *
 * @param array $config Config from config.php (must have 'db' with host, name, user, password)
 * @return PDO|null
 */
function db_connect(array $config): ?PDO
{
    $db = $config['db'] ?? null;
    if (!$db || empty($db['host']) || empty($db['name'])) {
        return null;
    }
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $db['host'],
        $db['name']
    );
    try {
        $pdo = new PDO($dsn, $db['user'] ?? '', $db['password'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}
