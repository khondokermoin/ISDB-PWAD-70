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
  if (!isset($_SESSION['user'])) json_response(['error' => 'Unauthorized'], 401);
  return $_SESSION['user'];
}

function require_admin(): array {
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
  $trusted = $sec['trusted_proxies'] ?? [];
  if (!is_array($trusted)) $trusted = [];

  return $remote !== '' && in_array($remote, $trusted, true);
}

/**
 * Best-effort client IP.
 * SECURITY NOTE:
 * - Proxy headers are spoofable unless you only trust them from your own proxy.
 * - To avoid bypassing IP restrictions, proxy headers are used ONLY when
 *   is_trusted_proxy_request() is true.
 */
function client_ip(): string {
  if (is_trusted_proxy_request()) {
    // Cloudflare
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);

    // Standard proxy header: take the first IP
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $xff = (string)$_SERVER['HTTP_X_FORWARDED_FOR'];
      $parts = array_map('trim', explode(',', $xff));
      if (!empty($parts[0])) return $parts[0];
    }

    if (!empty($_SERVER['HTTP_X_REAL_IP'])) return trim((string)$_SERVER['HTTP_X_REAL_IP']);
  }

  return trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
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

// --- MAC helpers ---
// Normalizes MAC to 12 hex characters (lowercase) or '' if invalid.
function mac_normalize(string $mac): string {
  $mac = strtolower(trim($mac));
  if ($mac === '') return '';

  // Remove common separators
  $mac = preg_replace('/[^0-9a-f]/', '', $mac);
  if (!is_string($mac)) return '';
  if (strlen($mac) !== 12) return '';
  if (!preg_match('/^[0-9a-f]{12}$/', $mac)) return '';
  return $mac;
}

// $allowed can be a single MAC or a comma-separated list.
function mac_allowed(string $providedMac, string $allowed): bool {
  $allowed = trim($allowed);
  if ($allowed === '') return true;

  $pm = mac_normalize($providedMac);
  if ($pm === '') return false;

  $list = array_filter(array_map('trim', explode(',', $allowed)));
  if (!$list) return true;

  foreach ($list as $entry) {
    $am = mac_normalize($entry);
    if ($am !== '' && $am === $pm) return true;
  }
  return false;
}
