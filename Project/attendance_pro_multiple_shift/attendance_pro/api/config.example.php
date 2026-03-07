<?php
/**
 * Copy this file to config.php and set the values.
 * IMPORTANT: Do NOT commit config.php to version control.
 */
return [
  'db' => [
    'host' => 'localhost',
    'name' => 'u951246149_attendance',
    'user' => 'u951246149_attendance_pro',
    'pass' => 's47i61t56o08l',
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
     * If you use Cloudflare, you can simply set 'trusted_proxies' => ['cloudflare'] (recommended).
     */
    'trusted_proxies' => ['cloudflare'],
  ],
];
