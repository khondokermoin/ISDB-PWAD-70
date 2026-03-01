<?php
function handle_employees_update(int $id): void {
  require_admin();
  $data = get_json_body();

  $emp_code = trim((string)($data['emp_code'] ?? ''));
  $name = trim((string)($data['name'] ?? ''));

  if ($emp_code === '' || $name === '') {
    json_response(['error' => 'emp_code and name required'], 422);
  }

  $phone = trim((string)($data['phone'] ?? ''));
  $email = trim((string)($data['email'] ?? ''));
  $department = trim((string)($data['department'] ?? ''));
  $designation = trim((string)($data['designation'] ?? ''));
  $join_date = (string)($data['join_date'] ?? null);
  $default_shift_id = isset($data['default_shift_id']) ? (int)$data['default_shift_id'] : null;

  $pdo = db();
  $stmt = $pdo->prepare("
    UPDATE employees
    SET emp_code = ?, name = ?, phone = ?, email = ?, department = ?, designation = ?, join_date = ?, default_shift_id = ?
    WHERE id = ?
  ");
  $stmt->execute([$emp_code, $name, $phone, $email, $department, $designation, $join_date, $default_shift_id, $id]);

  audit_log((int)($_SESSION['user_id'] ?? 0), 'UPDATE_EMPLOYEE', ['employee_id' => $id]);
  json_response(['ok' => true]);
}
