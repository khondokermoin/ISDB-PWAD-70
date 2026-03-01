<?php
/**
 * Copy this file to config.php and set the values.
 * IMPORTANT: Do NOT commit config.php to version control.
 */
return [
  'db' => [
    'host' => 'localhost',
    'name' => 'your_database_name',
    'user' => 'your_database_user',
    'pass' => 'your_database_password',
    'charset' => 'utf8mb4',
  ],

  'session' => [
    // Set true only when using HTTPS.
    'cookie_secure' => true,
    'cookie_samesite' => 'Lax',
  ],

  'security' => [
    /**
     * Only enable this if you are behind a trusted reverse proxy/CDN
     * and you have configured trusted_proxies correctly.
     */
    'trust_proxy_headers' => false,

    /**
     * List of reverse proxy IPs (REMOTE_ADDR) you trust to provide
     * X-Forwarded-For / CF-Connecting-IP / X-Real-IP headers.
     * Examples:
     *  - Your Nginx/Apache reverse proxy IP
     *  - Your load balancer private IP
     *
     * If you use Cloudflare, you can add Cloudflare IP ranges here (long list).
     */
    'trusted_proxies' => [],
  ],
];
