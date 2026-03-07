<?php
function handle_employees_delete(int $id): void {
  require_admin();
  $pdo = db();

  // Optional safety: যদি attendance আছে তাহলে delete ব্লক
  // (চাইলে enable করুন)
  /*
  $st = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE employee_id = ?");
  $st->execute([$id]);
  if ((int)$st->fetchColumn() > 0) {
    json_response(['error' => 'Cannot delete: employee has attendance records'], 409);
  }
  */

  // delete user account first (if linked)
  $pdo->prepare("DELETE FROM users WHERE employee_id = ?")->execute([$id]);
  $pdo->prepare("DELETE FROM employees WHERE id = ?")->execute([$id]);

  audit_log((int)($_SESSION['user_id'] ?? 0), 'DELETE_EMPLOYEE', ['employee_id' => $id]);
  json_response(['ok' => true]);
}
