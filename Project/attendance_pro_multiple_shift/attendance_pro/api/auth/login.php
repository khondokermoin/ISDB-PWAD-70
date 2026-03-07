<?php
function handle_login(): void {
  $data = get_json_body();
  $username = trim((string)($data['username'] ?? ''));
  $password = (string)($data['password'] ?? '');

  if ($username === '' || $password === '') {
    json_response(['error' => 'Username and password required'], 422);
  }



// Basic brute-force protection (per IP + username)
$ip = client_ip();
$keyUser = strtolower($username);
try {
  $pdo = db();
  $stA = $pdo->prepare("SELECT attempts, last_attempt_at, blocked_until FROM login_attempts WHERE ip = ? AND username = ? LIMIT 1");
  $stA->execute([$ip, $keyUser]);
  $rowA = $stA->fetch();
  if ($rowA && !empty($rowA['blocked_until'])) {
    $blockedUntil = (string)$rowA['blocked_until'];
    if ($blockedUntil && $blockedUntil > now_dt()) {
      json_response(['error' => 'Too many attempts. Try again later.'], 429);
    }
  }
} catch (Throwable $e) {
  // If throttling storage fails, do not block login flow.
}

  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, role, password_hash, is_active, employee_id, allowed_ip, allowed_device_tokens FROM users WHERE username = ? OR email = ? OR phone = ? LIMIT 1");
  $stmt->execute([$username, $username, $username]);
  $u = $stmt->fetch();

  
if (!$u || !(int)$u['is_active'] || !password_verify($password, $u['password_hash'])) {
    // record failed attempt
    try {
      $ip = client_ip();
      $keyUser = strtolower($username);
      $now = now_dt();
      $stFail = $pdo->prepare("INSERT INTO login_attempts (ip, username, attempts, last_attempt_at, blocked_until) VALUES (?, ?, 1, ?, NULL)
                     ON DUPLICATE KEY UPDATE
                       attempts = IF(TIMESTAMPDIFF(MINUTE, last_attempt_at, VALUES(last_attempt_at)) > 15, 1, attempts + 1),
                       last_attempt_at = VALUES(last_attempt_at),
                       blocked_until = IF(attempts + 1 >= 5 AND TIMESTAMPDIFF(MINUTE, last_attempt_at, VALUES(last_attempt_at)) <= 15,
                                          DATE_ADD(VALUES(last_attempt_at), INTERVAL 15 MINUTE),
                                          blocked_until)");
      $stFail->execute([$ip, $keyUser, $now]);
    } catch (Throwable $e) {
      // ignore
    }
    json_response(['error' => 'Invalid credentials'], 401);
  }

  // Prevent session fixation
  session_regenerate_id(true);

  // Load employee profile if employee role
  $employee = null;
  if ($u['role'] === 'employee' && $u['employee_id']) {
    $s2 = $pdo->prepare("SELECT id, emp_code, name, department, designation, status, default_shift_id FROM employees WHERE id = ? LIMIT 1");
    $s2->execute([(int)$u['employee_id']]);
    $employee = $s2->fetch();
  }

  // clear any recorded failures
  try {
    $pdo->prepare("DELETE FROM login_attempts WHERE ip = ? AND username = ?")->execute([client_ip(), strtolower($username)]);
  } catch (Throwable $e) {}

  $_SESSION['user'] = [
    'id' => (int)$u['id'],
    'role' => $u['role'],
    'employee_id' => $u['employee_id'] ? (int)$u['employee_id'] : null,
    'allowed_ip' => $u['allowed_ip'] ?? null,
    'allowed_device_tokens' => $u['allowed_device_tokens'] ?? null,
    'employee' => $employee,
  ];

  $pdo->prepare("UPDATE users SET last_login_at = ? WHERE id = ?")->execute([now_dt(), (int)$u['id']]);

  audit_log((int)$u['id'], 'LOGIN', []);
  json_response(['ok' => true, 'user' => $_SESSION['user'], 'csrf_token' => csrf_token()]);
}
