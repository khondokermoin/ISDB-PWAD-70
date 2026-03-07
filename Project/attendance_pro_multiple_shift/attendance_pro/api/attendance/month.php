<?php
function handle_attendance_month(): void {
  $user = require_login();
  $pdo = db();

  $month = $_GET['month'] ?? date('Y-m');
  $employee_id = null;

  if (($user['role'] ?? '') === 'admin' && isset($_GET['employee_id']) && is_numeric($_GET['employee_id'])) {
    $employee_id = (int)$_GET['employee_id'];
  } else if (($user['role'] ?? '') === 'employee') {
    $employee_id = (int)$user['employee_id'];
  } else {
    json_response(['error' => 'employee_id required'], 422);
  }

  $start = $month . '-01';
  $startDt = new DateTime($start, new DateTimeZone('Asia/Dhaka'));
  $endDt = (clone $startDt)->modify('first day of next month')->modify('-1 day');
  $end = $endDt->format('Y-m-d');

  $stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE employee_id = ? AND work_date BETWEEN ? AND ? ORDER BY work_date ASC");
  $stmt->execute([$employee_id, $start, $end]);
  $rows = $stmt->fetchAll();

  // Summary counts
  $presentDays = 0; $absentDays = 0; $lateDays = 0; $incompleteDays = 0; $leaveDays = 0;
  $totalMinutes = 0;
  foreach ($rows as $r) {
    $st = $r['status'] ?? '';
    if ($st === 'present') $presentDays++;
    if ($st === 'late') { $lateDays++; $presentDays++; }
    if ($st === 'absent') $absentDays++;
    if ($st === 'incomplete') $incompleteDays++;
    if ($st === 'leave') $leaveDays++;
    $totalMinutes += (int)($r['total_minutes'] ?? 0);
  }

  json_response([
    'ok' => true,
    'month' => $month,
    'employee_id' => $employee_id,
    'summary' => [
      'present_days' => $presentDays,
      'late_days' => $lateDays,
      'absent_days' => $absentDays,
      'leave_days' => $leaveDays,
      'incomplete_days' => $incompleteDays,
      'total_hours' => round($totalMinutes / 60, 2),
    ],
    'rows' => $rows
  ]);
}
