<?php
function handle_settings_get(): void {
  $pdo = db();
  $row = $pdo->query("SELECT * FROM attendance_settings WHERE id = 1")->fetch();
  if (!$row) {
    $pdo->prepare("INSERT INTO attendance_settings (id, attendance_open, allow_manual_override, ip_restriction_enabled, allowed_ips, device_binding_enabled, updated_at)
                   VALUES (1, 1, 1, 0, '', 0, ?)")->execute([now_dt()]);
    $row = $pdo->query("SELECT * FROM attendance_settings WHERE id = 1")->fetch();
  }
  json_response(['ok' => true, 'settings' => $row]);
}
