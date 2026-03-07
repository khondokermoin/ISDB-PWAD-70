<?php
function handle_attendance_override(): void {
  $admin = require_admin();
  $d = get_json_body();

  $employee_id = (int)($d['employee_id'] ?? 0);
  $work_date = trim((string)($d['work_date'] ?? ''));
  $reason = trim((string)($d['reason'] ?? ''));

  if ($employee_id <= 0 || $work_date === '' || $reason === '') {
    json_response(['error' => 'employee_id, work_date, reason required'], 422);
  }

  // Respect settings: allow_manual_override
  $pdo = db();
  $settings = $pdo->query('SELECT * FROM attendance_settings WHERE id = 1')->fetch();
  if ($settings && !(int)$settings['allow_manual_override']) {
    json_response(['error' => 'Manual override is disabled by admin settings'], 403);
  }

  // Basic date format check
  $dt = DateTime::createFromFormat('Y-m-d', $work_date);
  if (!$dt || $dt->format('Y-m-d') !== $work_date) {
    json_response(['error' => 'work_date must be YYYY-MM-DD'], 422);
  }

  $stmt = $pdo->prepare('SELECT * FROM attendance_logs WHERE employee_id=? AND work_date=? LIMIT 1');
  $stmt->execute([$employee_id, $work_date]);
  $log = $stmt->fetch();

  $fields = ['check_in','check_out','status','note','shift_id'];
  $set = [];
  $vals = [];

  foreach ($fields as $f) {
    if (!array_key_exists($f, $d)) continue;
    $set[] = "$f = ?";
    $vals[] = $d[$f];
  }

  // Derived fields: total_minutes if both check_in and check_out are present in the final record
  $computeTotal = function(?string $checkIn, ?string $checkOut): ?int {
    if (!$checkIn || !$checkOut) return null;
    try {
      $in = new DateTime($checkIn, new DateTimeZone('Asia/Dhaka'));
      $out = new DateTime($checkOut, new DateTimeZone('Asia/Dhaka'));
      if ($out <= $in) return null;
      return (int)floor(($out->getTimestamp() - $in->getTimestamp()) / 60);
    } catch (Throwable $e) {
      return null;
    }
  };

  // Determine final check_in/check_out values
  $finalCheckIn = $log ? ($log['check_in'] ?? null) : null;
  $finalCheckOut = $log ? ($log['check_out'] ?? null) : null;
  if (array_key_exists('check_in', $d))  $finalCheckIn = $d['check_in'] ?: null;
  if (array_key_exists('check_out', $d)) $finalCheckOut = $d['check_out'] ?: null;

  $finalTotal = $computeTotal(is_string($finalCheckIn) ? $finalCheckIn : null, is_string($finalCheckOut) ? $finalCheckOut : null);

  // Update total_minutes if we can compute it; otherwise leave as-is unless admin explicitly set it
  if ($finalTotal !== null) {
    $set[] = 'total_minutes = ?';
    $vals[] = $finalTotal;
  }

  if (!$set) json_response(['error' => 'No fields to override'], 422);

  $set[] = 'updated_at = ?';
  $vals[] = now_dt();

  if ($log) {
    $vals[] = (int)$log['id'];
    $pdo->prepare('UPDATE attendance_logs SET '.implode(', ', $set).' WHERE id=?')->execute($vals);
  } else {
    // Insert minimal record with overrides
    $shift_id = $d['shift_id'] ?? null;
    $note = (string)($d['note'] ?? '');

    $pdo->prepare('INSERT INTO attendance_logs (employee_id, work_date, shift_id, check_in, check_out, total_minutes, status, note, created_at, updated_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
      ->execute([
        $employee_id,
        $work_date,
        $shift_id,
        $finalCheckIn,
        $finalCheckOut,
        $finalTotal,
        $d['status'] ?? 'present',
        ($note !== '' ? $note.' | ' : '') . 'OVERRIDE: ' . $reason,
        now_dt(),
        now_dt(),
      ]);
  }

  audit_log((int)$admin['id'], 'OVERRIDE_ATTENDANCE', ['employee_id' => $employee_id, 'work_date' => $work_date, 'reason' => $reason]);
  json_response(['ok' => true]);
}
