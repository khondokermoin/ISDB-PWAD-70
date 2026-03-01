<?php
function handle_employees_list(): void {
  $status = $_GET['status'] ?? null;
  $pdo = db();

  if ($status === 'active' || $status === 'inactive') {
    $stmt = $pdo->prepare("SELECT e.*, u.allowed_ip, u.allowed_mac
                           FROM employees e
                           LEFT JOIN users u ON u.employee_id = e.id AND u.role='employee'
                           WHERE e.status = ?
                           ORDER BY e.id DESC");
    $stmt->execute([$status]);
  } else {
    $stmt = $pdo->query("SELECT e.*, u.allowed_ip, u.allowed_mac
                         FROM employees e
                         LEFT JOIN users u ON u.employee_id = e.id AND u.role='employee'
                         ORDER BY e.id DESC");
  }
  json_response(['ok' => true, 'employees' => $stmt->fetchAll()]);
}
