<?php
declare(strict_types=1);

/**
 * Central bootstrap for the API.
 * - Loads config
 * - Starts session
 * - Provides DB + helper functions
 */

function app_config(): array {
  static $cfg = null;
  if (is_array($cfg)) return $cfg;

  $cfg = require __DIR__ . '/config.php';
  if (!is_array($cfg)) $cfg = [];
  return $cfg;
}

$config = app_config();

// --- Sessions ---
$secure = (bool)($config['session']['cookie_secure'] ?? true);
$samesite = (string)($config['session']['cookie_samesite'] ?? 'Lax');

session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => $secure,
  'httponly' => true,
  'samesite' => $samesite,
]);
session_start();


// --- CSRF ---
function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['csrf_token'];
}

function require_csrf(): void {
  $m = method();
  if (!in_array($m, ['POST','PUT','PATCH','DELETE'], true)) return;
  // Allow login without CSRF (no established session token yet)
  $path = $_SERVER['REQUEST_URI'] ?? '';
  if (strpos($path, '/api/auth/login') !== false) return;
  $provided = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  $expected = (string)($_SESSION['csrf_token'] ?? '');
  if ($expected === '') {
    // force create token so next requests can use it
    csrf_token();
    $expected = (string)$_SESSION['csrf_token'];
  }
  if ($provided === '' || !hash_equals($expected, $provided)) {
    json_response(['error' => 'CSRF token missing or invalid'], 419);
  }
}

// --- DB ---
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $config = app_config();
  $db = $config['db'] ?? [];

  $host = (string)($db['host'] ?? 'localhost');
  $name = (string)($db['name'] ?? '');
  $user = (string)($db['user'] ?? '');
  $pass = (string)($db['pass'] ?? '');
  $charset = (string)($db['charset'] ?? 'utf8mb4');

  if ($name === '' || $user === '') {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Server misconfigured: database credentials missing'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $name, $charset);

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  return $pdo;
}

// --- Response helpers ---
function json_response($data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function get_json_body(): array {
  $raw = file_get_contents('php://input') ?: '';
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function method(): string {
  $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  // Allow method override from POST
  if ($m === 'POST') {
    $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_POST['_method'] ?? null);
    if ($override) $m = strtoupper((string)$override);
  }
  return strtoupper($m);
}

function require_login(): array {
  require_csrf();
  if (!isset($_SESSION['user'])) json_response(['error' => 'Unauthorized'], 401);
  return $_SESSION['user'];
}

function require_admin(): array {
  require_csrf();
  $u = require_login();
  if (($u['role'] ?? '') !== 'admin') json_response(['error' => 'Forbidden'], 403);
  return $u;
}

function now_dt(): string {
  return (new DateTime('now', new DateTimeZone('Asia/Dhaka')))->format('Y-m-d H:i:s');
}

function today_date(): string {
  return (new DateTime('now', new DateTimeZone('Asia/Dhaka')))->format('Y-m-d');
}

function parse_path(): array {
  // Works for /api/index.php/... or /api/...
  $uri = $_SERVER['REQUEST_URI'] ?? '/';
  $qpos = strpos($uri, '?');
  if ($qpos !== false) $uri = substr($uri, 0, $qpos);

  // Normalize to remove leading /api
  $uri = preg_replace('#^/api(?:/index\.php)?#', '', $uri);
  $uri = trim($uri, '/');
  return $uri === '' ? [] : explode('/', $uri);
}

function server_log(string $message, array $context = []): void {
  // Basic structured logging to PHP error log
  $line = '[HR] ' . $message;
  if ($context) $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  error_log($line);
}

function audit_log(?int $actor_user_id, string $action, array $meta = []): void {
  try {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO audit_logs (actor_user_id, action, meta_json, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$actor_user_id, $action, json_encode($meta, JSON_UNESCAPED_UNICODE), now_dt()]);
  } catch (Throwable $e) {
    // Don't block the main flow
  }
}

// --- IP helpers ---
function is_trusted_proxy_request(): bool {
  $cfg = app_config();
  $sec = $cfg['security'] ?? [];
  $trust = (bool)($sec['trust_proxy_headers'] ?? false);
  if (!$trust) return false;

  $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
  if ($remote === '') return false;

  $trusted = $sec['trusted_proxies'] ?? [];
  if (!is_array($trusted)) $trusted = [];

  foreach ($trusted as $entry) {
    $e = strtolower(trim((string)$entry));
    if ($e === '') continue;

    // Convenience: allow 'cloudflare' to trust Cloudflare proxy headers safely
    if ($e === 'cloudflare') {
      if (ip_in_cloudflare($remote)) return true;
      continue;
    }

    // Allow exact IP or CIDR blocks (v4/v6)
    if (ip_in_cidr($remote, (string)$entry)) return true;
  }

  return false;
}

/**
 * Best-effort client IP.
 * SECURITY NOTE:
 * - Proxy headers are spoofable unless you only trust them from your own proxy.
 * - To avoid bypassing IP restrictions, proxy headers are used ONLY when
 *   is_trusted_proxy_request() is true.
 */
function client_ip(): string {
  $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));

  if (is_trusted_proxy_request()) {
    $candidates = [];

    // Cloudflare
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
      $candidates[] = trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
    }

    // Standard proxy header: take the first IP
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $xff = (string)$_SERVER['HTTP_X_FORWARDED_FOR'];
      $parts = array_map('trim', explode(',', $xff));
      if (!empty($parts[0])) $candidates[] = $parts[0];
    }

    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
      $candidates[] = trim((string)$_SERVER['HTTP_X_REAL_IP']);
    }

    foreach ($candidates as $ip) {
      if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
  }

  return $remote;
}

function ip_in_cidr(string $ip, string $cidr): bool {
  $cidr = trim($cidr);
  $ip = trim($ip);
  if ($cidr === '' || $ip === '') return false;

  if (strpos($cidr, '/') === false) {
    // Exact match (works for both IPv4 and IPv6)
    return $ip === $cidr;
  }

  [$subnet, $maskBits] = explode('/', $cidr, 2);
  $subnet = trim($subnet);
  $maskBits = (int)$maskBits;

  $ipBin = @inet_pton($ip);
  $subBin = @inet_pton($subnet);
  if ($ipBin === false || $subBin === false) return false;
  if (strlen($ipBin) !== strlen($subBin)) return false; // v4 vs v6 mismatch

  $maxBits = strlen($ipBin) * 8;
  if ($maskBits < 0 || $maskBits > $maxBits) return false;

  $bytes = intdiv($maskBits, 8);
  $bits = $maskBits % 8;

  // Compare full bytes
  if ($bytes > 0) {
    if (substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) return false;
  }

  // Compare remaining bits in next byte
  if ($bits === 0) return true;

  $ipByte = ord($ipBin[$bytes]);
  $subByte = ord($subBin[$bytes]);
  $mask = 0xFF & (0xFF << (8 - $bits));

  return (($ipByte & $mask) === ($subByte & $mask));
}

// $allowed can be a single IP, a comma-separated list, and/or CIDR blocks (IPv4 or IPv6).
function ip_allowed(string $clientIp, string $allowed): bool {
  $allowed = trim($allowed);
  if ($allowed === '') return true;

  $list = array_filter(array_map('trim', explode(',', $allowed)));
  if (!$list) return true;

  foreach ($list as $entry) {
    if ($entry === '') continue;
    if (ip_in_cidr($clientIp, $entry)) return true;
  }
  return false;
}

function validate_allowed_ip_string(string $allowed_ip): ?string {
  $allowed_ip = trim($allowed_ip);
  if ($allowed_ip === '') return null;

  $entries = array_filter(array_map('trim', explode(',', $allowed_ip)));
  foreach ($entries as $entry) {
    if ($entry === '') continue;

    if (strpos($entry, '/') !== false) {
      [$base, $bits] = explode('/', $entry, 2);
      $base = trim($base);
      $bits = (int)$bits;

      if (!filter_var($base, FILTER_VALIDATE_IP)) {
        return 'Invalid allowed_ip (base IP is invalid).';
      }

      $isV4 = (bool)filter_var($base, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
      $maxBits = $isV4 ? 32 : 128;

      if ($bits < 0 || $bits > $maxBits) {
        return 'Invalid allowed_ip CIDR mask (use /0..' . $maxBits . ').';
      }
    } else {
      if (!filter_var($entry, FILTER_VALIDATE_IP)) {
        return 'Invalid allowed_ip (use IPv4/IPv6, comma-separated, and/or CIDR).';
      }
    }
  }

  return null;
}

// --- Cloudflare proxy helper ---
// Used to safely trust CF-Connecting-IP only when REMOTE_ADDR is a Cloudflare edge IP.
function cloudflare_ip_ranges(): array {
  return [
    '173.245.48.0/20',
    '103.21.244.0/22',
    '103.22.200.0/22',
    '103.31.4.0/22',
    '141.101.64.0/18',
    '108.162.192.0/18',
    '190.93.240.0/20',
    '188.114.96.0/20',
    '197.234.240.0/22',
    '198.41.128.0/17',
    '162.158.0.0/15',
    '104.16.0.0/13',
    '104.24.0.0/14',
    '172.64.0.0/13',
    '131.0.72.0/22',
    '2400:cb00::/32',
    '2606:4700::/32',
    '2803:f800::/32',
    '2405:b500::/32',
    '2405:8100::/32',
    '2a06:98c0::/29',
    '2c0f:f248::/32',
  ];
}

function ip_in_cloudflare(string $ip): bool {
  foreach (cloudflare_ip_ranges() as $cidr) {
    if (ip_in_cidr($ip, $cidr)) return true;
  }
  return false;
}

// --- Device token helpers ---
// A device token is a per-browser/per-device identifier stored in localStorage by the frontend.
// It is NOT a hardware identifier.
function device_token_normalize(string $token): string {
  return strtolower(trim($token));
}

// Basic validation for UUID-style tokens (as generated by crypto.randomUUID()).
function device_token_is_valid(string $token): bool {
  $t = device_token_normalize($token);
  if ($t === '') return false;
  return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $t);
}

// $allowed can be a single token or a comma-separated list.
function device_token_allowed(string $providedToken, string $allowed): bool {
  $allowed = trim($allowed);
  if ($allowed === '') return true;

  $pt = device_token_normalize($providedToken);
  if ($pt === '') return false;

  $list = array_filter(array_map('trim', explode(',', $allowed)));
  if (!$list) return true;

  foreach ($list as $entry) {
    $et = device_token_normalize($entry);
    if ($et !== '' && $et === $pt) return true;
  }
  return false;
}
