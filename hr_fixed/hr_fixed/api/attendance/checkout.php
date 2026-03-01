<?php
require __DIR__ . '/_helpers.php';

function handle_checkout(): void {
  $user = require_login();
  if (($user['role'] ?? '') !== 'employee') json_response(['error' => 'Only employees can check-out'], 403);

  $pdo = db();

  // If attendance is closed, optionally block checkout too (keeps policy consistent with check-in).
  $settings = $pdo->query('SELECT * FROM attendance_settings WHERE id = 1')->fetch();
  if ($settings && !(int)$settings['attendance_open']) {
    json_response(['error' => 'Attendance is closed by admin'], 403);
  }

  // --- IP/MAC restriction ---
  $ip = client_ip();

  $body = get_json_body();
  $providedMac = trim((string)($_SERVER['HTTP_X_DEVICE_MAC'] ?? ($body['device_mac'] ?? '')));

  $uStmt = $pdo->prepare('SELECT allowed_ip, allowed_mac FROM users WHERE id = ? LIMIT 1');
  $uStmt->execute([(int)$user['id']]);
  $uRow = $uStmt->fetch();
  $userAllowed = trim((string)($uRow['allowed_ip'] ?? ''));
  $userAllowedMac = trim((string)($uRow['allowed_mac'] ?? ''));

  if ($userAllowed !== '' && !ip_allowed($ip, $userAllowed)) {
    json_response(['error' => 'You can only check-out from your assigned network'], 403);
  }

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

  // Find the latest open session (supports overnight shifts)
  $stmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE employee_id = ? AND check_in IS NOT NULL AND check_out IS NULL ORDER BY work_date DESC LIMIT 1');
  $stmt->execute([$employee_id]);
  $log = $stmt->fetch();

  if (!$log || !$log['check_in']) json_response(['error' => 'You must check-in first'], 409);

  $workDate = (string)$log['work_date'];

  $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
  $nowStr = $now->format('Y-m-d H:i:s');

  $shift = resolve_employee_shift($pdo, $employee_id, $workDate);

  // Earliest checkout rule (optional by shift)
  if (!empty($shift['checkout_earliest_time'])) {
    $earliest = new DateTime($workDate.' '.$shift['checkout_earliest_time'], new DateTimeZone('Asia/Dhaka'));

    // If this is an overnight shift and earliest time is "before" start time, interpret it as next day.
    if ((string)$shift['end_time'] < (string)$shift['start_time'] && (string)$shift['checkout_earliest_time'] < (string)$shift['start_time']) {
      $earliest->modify('+1 day');
    }

    if ($now < $earliest) json_response(['error' => 'Checkout not allowed yet'], 403);
  }

  $checkIn = new DateTime((string)$log['check_in'], new DateTimeZone('Asia/Dhaka'));
  if ($now <= $checkIn) json_response(['error' => 'Invalid checkout time'], 422);

  $totalMinutes = (int)floor(($now->getTimestamp() - $checkIn->getTimestamp()) / 60);

  // Shift end (supports overnight)
  $end = new DateTime($workDate.' '.$shift['end_time'], new DateTimeZone('Asia/Dhaka'));
  if ((string)$shift['end_time'] < (string)$shift['start_time']) {
    $end->modify('+1 day');
  }

  // Early leave calc (if checked out before shift end)
  $earlyLeaveMinutes = 0;
  if ($now < $end) $earlyLeaveMinutes = (int)floor(($end->getTimestamp() - $now->getTimestamp()) / 60);

  // Keep status from check-in (present/late) unless admin overrides elsewhere
  $status = $log['status'] ?: 'present';
  if ($status === 'incomplete') $status = 'present';

  // Optional: if total < min_daily => mark incomplete
  if (!empty($shift['min_daily_minutes']) && in_array($status, ['present','late'], true)) {
    $minDaily = (int)$shift['min_daily_minutes'];
    if ($minDaily > 0 && $totalMinutes < $minDaily) {
      $status = 'incomplete';
    }
  }

  $upd = $pdo->prepare('UPDATE attendance_logs SET check_out=?, total_minutes=?, early_leave_minutes=?, status=?, updated_at=? WHERE id=?');
  $upd->execute([$nowStr, $totalMinutes, $earlyLeaveMinutes, $status, now_dt(), (int)$log['id']]);

  audit_log((int)$user['id'], 'CHECKOUT', ['employee_id' => $employee_id, 'work_date' => $workDate, 'total_minutes' => $totalMinutes]);
  json_response(['ok' => true, 'work_date' => $workDate, 'check_out' => $nowStr, 'total_minutes' => $totalMinutes, 'early_leave_minutes' => $earlyLeaveMinutes, 'status' => $status]);
}
