<?php
function handle_roster_create(): void {
  $user = require_admin();
  $pdo = db();
  $data = get_json_body();

  $employee_id = (int)($data['employee_id'] ?? 0);
  $shift_id = (int)($data['shift_id'] ?? 0);
  $from = trim((string)($data['effective_from_date'] ?? ''));
  $to = trim((string)($data['effective_to_date'] ?? ''));

  if ($employee_id <= 0 || $shift_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    json_response(['error' => 'employee_id, shift_id, effective_from_date are required'], 422);
  }
  if ($to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    json_response(['error' => 'effective_to_date must be YYYY-MM-DD or empty'], 422);
  }

  // Validate employee and shift exist
  $e = $pdo->prepare('SELECT id FROM employees WHERE id=? LIMIT 1'); $e->execute([$employee_id]);
  if (!$e->fetch()) json_response(['error' => 'Employee not found'], 404);
  $s = $pdo->prepare('SELECT id FROM shifts WHERE id=? LIMIT 1'); $s->execute([$shift_id]);
  if (!$s->fetch()) json_response(['error' => 'Shift not found'], 404);

  // Overlap check (simple): any assignment for same employee where date ranges overlap
  // overlap if existing_from <= new_to (or new_to NULL treated as far future) AND existing_to >= new_from (or existing_to NULL treated as far future)
  $newTo = ($to === '') ? '9999-12-31' : $to;

  $chk = $pdo->prepare("
    SELECT id FROM employee_shift_assignments
    WHERE employee_id = ?
      AND effective_from_date <= ?
      AND COALESCE(effective_to_date, '9999-12-31') >= ?
    LIMIT 1
  ");
  $chk->execute([$employee_id, $newTo, $from]);
  if ($chk->fetch()) {
    json_response(['error' => 'This assignment overlaps an existing assignment for the employee. Adjust the date range.'], 409);
  }

  $ins = $pdo->prepare("INSERT INTO employee_shift_assignments (employee_id, shift_id, effective_from_date, effective_to_date)
                        VALUES (?,?,?,?)");
  $ins->execute([$employee_id, $shift_id, $from, ($to===''? null : $to)]);

  audit_log((int)$user['id'], 'ROSTER_ASSIGN_CREATE', [
    'employee_id' => $employee_id,
    'shift_id' => $shift_id,
    'effective_from_date' => $from,
    'effective_to_date' => ($to===''? null : $to),
  ]);

  json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}
