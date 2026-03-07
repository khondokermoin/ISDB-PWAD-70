<?php
function handle_employees_delete(int $id): void {
  // (route layer already enforces admin)
  require_admin();

  $pdo = db();

  // Optional safety: block deletion if attendance records exist
  /*
  $st = $pdo->prepare('SELECT COUNT(*) FROM attendance_logs WHERE employee_id = ?');
  $st->execute([$id]);
  if ((int)$st->fetchColumn() > 0) {
    json_response(['error' => 'Cannot delete: employee has attendance records'], 409);
  }
  */

  $pdo->beginTransaction();
  try {
    // delete linked user first (if any)
    $pdo->prepare('DELETE FROM users WHERE employee_id = ?')->execute([$id]);

    // then delete employee
    $pdo->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);

    $pdo->commit();

    $actor = $_SESSION['user']['id'] ?? null;
    audit_log($actor ? (int)$actor : null, 'DELETE_EMPLOYEE', ['employee_id' => $id]);

    json_response(['ok' => true]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    server_log('DELETE_EMPLOYEE_FAILED', ['employee_id' => $id, 'error' => $e->getMessage()]);
    json_response(['error' => 'Delete failed'], 400);
  }
}
