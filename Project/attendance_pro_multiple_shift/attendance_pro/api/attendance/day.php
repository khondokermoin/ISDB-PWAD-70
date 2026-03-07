<?php
function handle_attendance_day(): void {
  $date = $_GET['date'] ?? today_date();
  $pdo = db();
  $stmt = $pdo->prepare("
    SELECT a.*, e.emp_code, e.name, e.department, e.designation
    FROM attendance_logs a
    JOIN employees e ON e.id = a.employee_id
    WHERE a.work_date = ?
    ORDER BY e.emp_code ASC
  ");
  $stmt->execute([$date]);
  $rows = $stmt->fetchAll();

  json_response(['ok' => true, 'date' => $date, 'rows' => $rows]);
}
