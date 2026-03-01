<?php
require __DIR__ . '/_helpers.php';

function handle_checkin(): void {
  $user = require_login();
  if (($user['role'] ?? '') !== 'employee') json_response(['error' => 'Only employees can check-in'], 403);

  $pdo = db();

  // Ensure settings row exists
  $settings = $pdo->query('SELECT * FROM attendance_settings WHERE id = 1')->fetch();
  if (!$settings) {
    $pdo->prepare('INSERT INTO attendance_settings (id, attendance_open, allow_manual_override, ip_restriction_enabled, allowed_ips, device_binding_enabled, updated_at)
                   VALUES (1, 1, 1, 0, \'\', 0, ?)')->execute([now_dt()]);
    $settings = $pdo->query('SELECT * FROM attendance_settings WHERE id = 1')->fetch();
  }

  if ($settings && !(int)$settings['attendance_open']) json_response(['error' => 'Attendance is closed by admin'], 403);

  // --- IP restriction ---
  // 1) Per-user: if admin set allowed_ip for this employee, enforce it always.
  // 2) Global: if enabled in settings, enforce global allowed_ips as fallback.
  $ip = client_ip();

  // Device MAC (optional enforcement if admin configured allowed_mac)
  // NOTE: Browsers cannot reliably provide a true MAC. This is only meaningful
  // when the client/app provides a trusted device identifier.
  $body = get_json_body();
  $providedMac = trim((string)($_SERVER['HTTP_X_DEVICE_MAC'] ?? ($body['device_mac'] ?? '')));

  $uStmt = $pdo->prepare('SELECT allowed_ip, allowed_mac FROM users WHERE id = ? LIMIT 1');
  $uStmt->execute([(int)$user['id']]);
  $uRow = $uStmt->fetch();
  $userAllowed = trim((string)($uRow['allowed_ip'] ?? ''));
  $userAllowedMac = trim((string)($uRow['allowed_mac'] ?? ''));

  if ($userAllowed !== '' && !ip_allowed($ip, $userAllowed)) {
    json_response(['error' => 'You can only check-in from your assigned network'], 403);
  }

  // Per-user MAC restriction (if set, must match)
  if ($userAllowedMac !== '' && !mac_allowed($providedMac, $userAllowedMac)) {
    json_response(['error' => 'Device not allowed for this user'], 403);
  }

  if ($userAllowed === '' && $settings && (int)$settings['ip_restriction_enabled']) {
    $globalAllowed = (string)($settings['allowed_ips'] ?? '');
    if (!ip_allowed($ip, $globalAllowed)) {
      json_response(['error' => 'IP not allowed'], 403);
    }
  }

  $employee_id = (int)$user['employee_id'];
  $date = today_date();

  // Prevent starting a new check-in if there is an open session from a previous day
  $openStmt = $pdo->prepare('SELECT id, work_date, check_in FROM attendance_logs WHERE employee_id = ? AND check_in IS NOT NULL AND check_out IS NULL ORDER BY work_date DESC LIMIT 1');
  $openStmt->execute([$employee_id]);
  $open = $openStmt->fetch();
  if ($open && (string)$open['work_date'] !== $date) {
    json_response(['error' => 'You have an open attendance session from '.$open['work_date'].'. Please check-out first (or ask admin to override).'], 409);
  }

  $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
  $nowStr = $now->format('Y-m-d H:i:s');
  $timeStr = $now->format('H:i:s');

  // Holiday check
  $h = $pdo->prepare('SELECT id, title FROM holidays WHERE holiday_date = ? LIMIT 1');
  $h->execute([$date]);
  $holiday = $h->fetch();
  if ($holiday) {
    json_response(['error' => 'Today is a holiday: '.$holiday['title']], 403);
  }

  $shift = resolve_employee_shift($pdo, $employee_id, $date);

  // Check-in window (supports overnight windows)
  if (!in_time_window($timeStr, (string)$shift['checkin_window_start'], (string)$shift['checkin_window_end'])) {
    // Policy: allow but mark as late (or block). We'll allow and mark late.
    // If you want hard-block, return 403 here.
  }

  // Existing log for today?
  $stmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE employee_id = ? AND work_date = ? LIMIT 1');
  $stmt->execute([$employee_id, $date]);
  $log = $stmt->fetch();
  if ($log && $log['check_in']) json_response(['error' => 'Already checked in'], 409);

  // Late minutes calculation: after shift start + grace
  $start = new DateTime($date.' '.$shift['start_time'], new DateTimeZone('Asia/Dhaka'));
  $grace = (int)$shift['grace_minutes'];
  $lateAfter = (clone $start)->modify("+{$grace} minutes");
  $lateMinutes = 0;
  $status = 'present';
  if ($now > $lateAfter) {
    $lateMinutes = (int)floor(($now->getTimestamp() - $lateAfter->getTimestamp()) / 60);
    $status = 'late';
  }

  if ($log) {
    $upd = $pdo->prepare('UPDATE attendance_logs SET shift_id=?, check_in=?, status=?, late_minutes=?, updated_at=? WHERE id=?');
    $upd->execute([(int)$shift['id'], $nowStr, $status, $lateMinutes, now_dt(), (int)$log['id']]);
  } else {
    $ins = $pdo->prepare('INSERT INTO attendance_logs (employee_id, work_date, shift_id, check_in, status, late_minutes, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$employee_id, $date, (int)$shift['id'], $nowStr, $status, $lateMinutes, now_dt(), now_dt()]);
  }

  audit_log((int)$user['id'], 'CHECKIN', ['employee_id' => $employee_id, 'date' => $date, 'late_minutes' => $lateMinutes]);
  json_response(['ok' => true, 'date' => $date, 'check_in' => $nowStr, 'status' => $status, 'late_minutes' => $lateMinutes, 'shift' => $shift]);
}
