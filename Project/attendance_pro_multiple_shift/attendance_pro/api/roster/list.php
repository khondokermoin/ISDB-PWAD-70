<?php
function handle_roster_list(): void {
  $pdo = db();

  $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
  $month = isset($_GET['month']) ? preg_replace('/[^0-9\-]/','', (string)$_GET['month']) : null;

  $where = [];
  $params = [];

  if ($employee_id) { $where[] = 'a.employee_id = ?'; $params[] = $employee_id; }

  if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
    $start = $month . '-01';
    $startDt = new DateTime($start, new DateTimeZone('Asia/Dhaka'));
    $endDt = (clone $startDt)->modify('first day of next month')->modify('-1 day');
    $end = $endDt->format('Y-m-d');
    $where[] = '(a.effective_from_date <= ? AND (a.effective_to_date IS NULL OR a.effective_to_date >= ?))';
    $params[] = $end;
    $params[] = $start;
  }

  $sql = "SELECT a.id, a.employee_id, e.emp_code, e.name as employee_name,
                 a.shift_id, s.name as shift_name, s.start_time, s.end_time,
                 a.effective_from_date, a.effective_to_date
          FROM employee_shift_assignments a
          JOIN employees e ON e.id = a.employee_id
          JOIN shifts s ON s.id = a.shift_id";
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);
  $sql .= " ORDER BY a.effective_from_date DESC, a.id DESC LIMIT 500";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll();

  json_response(['ok' => true, 'items' => $rows]);
}
