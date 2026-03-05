<?php
/**
 * Single source of truth for API endpoint definitions.
 * Keys are logical paths relative to api/ (e.g. dutch_mt/register_for_tournament).
 * Used for consistent method/auth checks and for public endpoint input rules.
 */

declare(strict_types=1);

/**
 * Returns the full API registry: path => definition.
 *
 * Definition keys:
 * - methods: string[] (e.g. ['POST'])
 * - auth: 'public' | 'jwt'
 * - input_rules: optional; for public endpoints, key => rule (see public_api_security.php)
 *
 * @return array<string, array{methods: array<string>, auth: string, input_rules?: array<string, array>}>
 */
function api_registry_get_all(): array
{
    return [
        'register' => [
            'methods' => ['POST'],
            'auth'    => 'public',
        ],
        'login' => [
            'methods' => ['POST'],
            'auth'    => 'public',
        ],
        'refresh' => [
            'methods' => ['POST'],
            'auth'    => 'public',
        ],
        'health' => [
            'methods' => ['GET'],
            'auth'    => 'public',
        ],
        'health-python' => [
            'methods' => ['GET'],
            'auth'    => 'jwt',
        ],
        'create-tournament' => [
            'methods' => ['POST'],
            'auth'    => 'jwt',
        ],
        'dutch_mt/register_for_tournament' => [
            'methods'     => ['POST'],
            'auth'        => 'public',
            'input_rules' => [
                'name' => [
                    'required'   => true,
                    'max_length' => 255,
                    'pattern'    => '/^[a-zA-Z\s\-\'.]+$/',
                ],
                'surname' => [
                    'required'   => true,
                    'max_length' => 255,
                    'pattern'    => '/^[a-zA-Z\s\-\'.]+$/',
                ],
                'email' => [
                    'required'   => true,
                    'max_length' => 255,
                    'filter'     => 'email',
                ],
                'event_slug' => [
                    'required'   => true,
                    'max_length' => 64,
                    'pattern'    => '/^[a-zA-Z0-9_.\-]+$/',
                ],
                'event_name' => [
                    'required'   => true,
                    'max_length' => 255,
                ],
            ],
        ],
    ];
}

/**
 * Get definition for an endpoint by logical path (e.g. dutch_mt/register_for_tournament).
 *
 * @param string $path Logical path relative to api/
 * @return array|null Definition or null if not found
 */
function api_registry_get(string $path): ?array
{
    $all = api_registry_get_all();
    return $all[$path] ?? null;
}

/**
 * Check if the current request method is allowed for the endpoint.
 *
 * @param array $definition From api_registry_get()
 * @return bool
 */
function api_registry_method_allowed(array $definition): bool
{
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    return in_array($method, $definition['methods'] ?? [], true);
}
