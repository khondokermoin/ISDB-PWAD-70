<?php
function handle_settings_update(): void {
  $data = get_json_body();
  $pdo = db();

  $current = $pdo->query('SELECT * FROM attendance_settings WHERE id = 1')->fetch();
  if (!$current) {
    $pdo->prepare('INSERT INTO attendance_settings (id, attendance_open, allow_manual_override, ip_restriction_enabled, allowed_ips, device_binding_enabled, updated_at)
                   VALUES (1, 1, 1, 0, \'\', 0, ?)')->execute([now_dt()]);
  }

  // Validate allowed_ips if provided
  if (array_key_exists('allowed_ips', $data)) {
    $allowed_ips = trim((string)($data['allowed_ips'] ?? ''));
    if ($allowed_ips !== '') {
      $err = validate_allowed_ip_string($allowed_ips);
      if ($err) json_response(['error' => $err], 422);
    }
  }

  $fields = ['attendance_open','allow_manual_override','ip_restriction_enabled','allowed_ips','device_binding_enabled'];
  $set = [];
  $vals = [];
  foreach ($fields as $f) {
    if (!array_key_exists($f, $data)) continue;

    $v = $data[$f];

    // Normalize switches to 0/1
    if (in_array($f, ['attendance_open','allow_manual_override','ip_restriction_enabled','device_binding_enabled'], true)) {
      $v = (int)(!!$v);
    }

    // allowed_ips as string
    if ($f === 'allowed_ips') {
      $v = trim((string)$v);
    }

    $set[] = "$f = ?";
    $vals[] = $v;
  }

  if (!$set) json_response(['error' => 'No settings to update'], 422);

  $set[] = 'updated_at = ?';
  $vals[] = now_dt();
  $vals[] = 1;

  $stmt = $pdo->prepare('UPDATE attendance_settings SET '.implode(', ', $set).' WHERE id = ?');
  $stmt->execute($vals);

  $actor = $_SESSION['user']['id'] ?? null;
  audit_log($actor ? (int)$actor : null, 'SETTINGS_UPDATE', ['fields' => array_keys($data)]);

  json_response(['ok' => true]);
}
