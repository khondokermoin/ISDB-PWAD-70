<?php
function handle_reports_export(): void {
  $month = $_GET['month'] ?? date('Y-m');
  $type = $_GET['type'] ?? 'csv';
  if ($type !== 'csv') json_response(['error' => 'Only csv export supported in this starter'], 400);

  $pdo = db();
  $_GET['month'] = $month;
  require __DIR__ . '/summary.php';

  // We'll generate CSV from summary query (copy small part)
  $start = $month . '-01';
  $startDt = new DateTime($start, new DateTimeZone('Asia/Dhaka'));
  $endDt = (clone $startDt)->modify('first day of next month')->modify('-1 day');
  $end = $endDt->format('Y-m-d');

  $stmt = $pdo->prepare("
    SELECT 
      e.emp_code, e.name, e.department,
      SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) as present_days,
      SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late_days,
      SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent_days,
      SUM(CASE WHEN a.status='leave' THEN 1 ELSE 0 END) as leave_days,
      SUM(CASE WHEN a.status='incomplete' THEN 1 ELSE 0 END) as incomplete_days,
      ROUND(SUM(COALESCE(a.total_minutes,0))/60, 2) as total_hours
    FROM employees e
    LEFT JOIN attendance_logs a ON a.employee_id = e.id AND a.work_date BETWEEN ? AND ?
    WHERE e.status='active'
    GROUP BY e.id
    ORDER BY e.emp_code ASC
  ");
  $stmt->execute([$start, $end]);
  $rows = $stmt->fetchAll();

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="attendance_summary_'.$month.'.csv"');

  $out = fopen('php://output', 'w');
  fputcsv($out, ['emp_code','name','department','present_days','late_days','absent_days','leave_days','incomplete_days','total_hours']);
  foreach ($rows as $r) {
    fputcsv($out, [$r['emp_code'],$r['name'],$r['department'],$r['present_days'],$r['late_days'],$r['absent_days'],$r['leave_days'],$r['incomplete_days'],$r['total_hours']]);
  }
  fclose($out);
  exit;
}
