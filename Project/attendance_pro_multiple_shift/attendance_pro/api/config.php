<?php
/**
 * App configuration.
 *
 * SECURITY NOTE:
 * - Keep this file out of version control.
 * - On shared hosting, set strict file permissions.
 */

// Allow env vars (optional), but keep explicit defaults for cPanel setups.
$env = fn(string $k, $default = '') => getenv($k) !== false ? getenv($k) : $default;

return [
  'db' => [
    'host' => $env('DB_HOST', 'localhost'),
    'name' => $env('DB_NAME', 'your_database_name'),
    'user' => $env('DB_USER', 'your_database_user'),
    'pass' => $env('DB_PASS', 'your_database_password'),
    'charset' => 'utf8mb4',
  ],

  'session' => [
    // Set to true only when using HTTPS.
    // If you are testing over plain HTTP, set this to false.
    'cookie_secure' => filter_var($env('COOKIE_SECURE', 'true'), FILTER_VALIDATE_BOOL),
    'cookie_samesite' => $env('COOKIE_SAMESITE', 'Lax'),
  ],

  'security' => [
    /**
     * If enabled, client_ip() will trust proxy headers like X-Forwarded-For.
     * Only turn this on when your app is behind a trusted proxy and you list
     * proxy IPs in trusted_proxies.
     */
    'trust_proxy_headers' => filter_var($env('TRUST_PROXY_HEADERS', 'false'), FILTER_VALIDATE_BOOL),

    /**
     * Comma-separated list of trusted proxy IPs (REMOTE_ADDR).
     * Example: "127.0.0.1,10.0.0.10"
     */
    // TIP: If you use Cloudflare, you can set TRUSTED_PROXIES=cloudflare (and TRUST_PROXY_HEADERS=true).
    'trusted_proxies' => array_filter(array_map('trim', explode(',', (string)$env('TRUSTED_PROXIES', '')))),
  ],
];
