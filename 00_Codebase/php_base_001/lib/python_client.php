<?php
/**
 * Python API client for Dutch.mt Dashboard.
 * POSTs to PYTHON_API_BASE_URL with X-Service-Key. Used only for business (e.g. create-tournaments).
 */

declare(strict_types=1);

/**
 * POST JSON to Python API with service key.
 *
 * @param string $baseUrl PYTHON_API_BASE_URL (no trailing slash)
 * @param string $path Path to append (e.g. /service/dutch/create-tournaments)
 * @param string $serviceKey DUTCH_MT_DASHBOARD_SERVICE_KEY
 * @param array $body JSON body as array
 * @return array{status: int, body: string} HTTP status and response body
 */
function python_post(string $baseUrl, string $path, string $serviceKey, array $body): array
{
    $url = $baseUrl . $path;
    $json = json_encode($body);
    if ($json === false) {
        return ['status' => 500, 'body' => json_encode(['success' => false, 'error' => 'JSON encode failed'])];
    }

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        =>
                "Content-Type: application/json\r\n" .
                "X-Service-Key: " . $serviceKey . "\r\n",
            'content'       => $json,
            'ignore_errors' => true,
            'timeout'       => 30,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    $status = 500;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $status = (int) $m[0];
    }
    return [
        'status' => $status,
        'body'   => $response !== false ? $response : json_encode(['success' => false, 'error' => 'Request failed']),
    ];
}

/**
 * GET from Python API with service key (e.g. /service/health).
 *
 * @param string $baseUrl PYTHON_API_BASE_URL (no trailing slash)
 * @param string $path Path to append (e.g. /service/health)
 * @param string $serviceKey DUTCH_MT_DASHBOARD_SERVICE_KEY
 * @return array{status: int, body: string} HTTP status and response body
 */
function python_get(string $baseUrl, string $path, string $serviceKey): array
{
    $url = $baseUrl . $path;

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'header'        => "X-Service-Key: " . $serviceKey . "\r\n",
            'ignore_errors' => true,
            'timeout'       => 10,
        ],
    ]);

    $response = @file_get_contents($url, false, $ctx);
    $status = 500;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $status = (int) $m[0];
    }
    return [
        'status' => $status,
        'body'   => $response !== false ? $response : json_encode(['success' => false, 'error' => 'Request failed']),
    ];
}

