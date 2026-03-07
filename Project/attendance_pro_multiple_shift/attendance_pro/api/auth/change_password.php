<?php
function handle_change_password(): void {
  $user = require_login();
  $data = get_json_body();
  $current = (string)($data['current_password'] ?? '');
  $new = (string)($data['new_password'] ?? '');

  if ($current === '' || $new === '') json_response(['error' => 'current_password and new_password required'], 422);
  if (strlen($new) < 6) json_response(['error' => 'New password too short'], 422);

  $pdo = db();
  $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE id = ? LIMIT 1");
  $stmt->execute([(int)$user['id']]);
  $row = $stmt->fetch();

  if (!$row || !password_verify($current, (string)$row['password_hash'])) {
    json_response(['error' => 'Current password is incorrect'], 401);
  }

  $hash = password_hash($new, PASSWORD_BCRYPT);
  $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, (int)$user['id']]);

  audit_log((int)$user['id'], 'CHANGE_PASSWORD', []);
  json_response(['ok' => true]);
}
