<?php
function handle_reports_summary(): void {
  $month = $_GET['month'] ?? date('Y-m');
  $start = $month . '-01';
  $startDt = new DateTime($start, new DateTimeZone('Asia/Dhaka'));
  $endDt = (clone $startDt)->modify('first day of next month')->modify('-1 day');
  $end = $endDt->format('Y-m-d');

  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT 
      e.id as employee_id, e.emp_code, e.name, e.department,
      SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) as present_days,
      SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late_days,
      SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent_days,
      SUM(CASE WHEN a.status='leave' THEN 1 ELSE 0 END) as leave_days,
      SUM(CASE WHEN a.status='incomplete' THEN 1 ELSE 0 END) as incomplete_days,
      SUM(COALESCE(a.total_minutes,0)) as total_minutes
    FROM employees e
    LEFT JOIN attendance_logs a ON a.employee_id = e.id AND a.work_date BETWEEN ? AND ?
    WHERE e.status='active'
    GROUP BY e.id
    ORDER BY e.emp_code ASC
  ");
  $stmt->execute([$start, $end]);
  $rows = $stmt->fetchAll();

  foreach ($rows as &$r) {
    $r['total_hours'] = round(((int)$r['total_minutes'])/60, 2);
  }

  json_response(['ok' => true, 'month' => $month, 'rows' => $rows]);
}
