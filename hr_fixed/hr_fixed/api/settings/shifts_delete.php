<?php
function handle_shifts_delete(int $id): void {
  $pdo = db();

  // If any employee uses this shift as default
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE default_shift_id = ?');
  $stmt->execute([$id]);
  $cntEmp = (int)$stmt->fetchColumn();

  if ($cntEmp > 0) {
    json_response([
      'error' => "Cannot delete: this shift is assigned to {$cntEmp} employee(s). Change their default shift first."
    ], 409);
  }

  // Prevent deleting a shift already referenced by attendance logs
  $st2 = $pdo->prepare('SELECT COUNT(*) FROM attendance_logs WHERE shift_id = ?');
  $st2->execute([$id]);
  $cntAtt = (int)$st2->fetchColumn();
  if ($cntAtt > 0) {
    json_response([
      'error' => "Cannot delete: this shift is used in attendance logs ({$cntAtt} rows)."
    ], 409);
  }

  $stmt = $pdo->prepare('DELETE FROM shifts WHERE id = ?');
  $stmt->execute([$id]);

  $actor = $_SESSION['user']['id'] ?? null;
  audit_log($actor ? (int)$actor : null, 'DELETE_SHIFT', ['shift_id' => $id]);

  json_response(['ok' => true]);
}
