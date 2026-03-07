<?php
function handle_roster_delete(int $id): void {
  $user = require_admin();
  $pdo = db();

  $st = $pdo->prepare("SELECT * FROM employee_shift_assignments WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $row = $st->fetch();
  if (!$row) json_response(['error' => 'Not found'], 404);

  $pdo->prepare("DELETE FROM employee_shift_assignments WHERE id=?")->execute([$id]);

  audit_log((int)$user['id'], 'ROSTER_ASSIGN_DELETE', ['id' => $id, 'employee_id' => (int)$row['employee_id'], 'shift_id' => (int)$row['shift_id']]);
  json_response(['ok' => true]);
}
