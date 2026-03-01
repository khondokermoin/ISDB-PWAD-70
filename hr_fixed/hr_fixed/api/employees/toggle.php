<?php
function handle_employee_toggle(int $id, bool $active): void {
  $pdo = db();
  $status = $active ? 'active' : 'inactive';
  $pdo->prepare("UPDATE employees SET status = ? WHERE id = ?")->execute([$status, $id]);

  // Also toggle user
  $pdo->prepare("UPDATE users SET is_active = ? WHERE employee_id = ?")->execute([$active ? 1 : 0, $id]);

  $actor = $_SESSION['user']['id'] ?? null;
  audit_log($actor ? (int)$actor : null, $active ? 'ACTIVATE_EMPLOYEE' : 'DEACTIVATE_EMPLOYEE', ['employee_id' => $id]);

  json_response(['ok' => true]);
}
