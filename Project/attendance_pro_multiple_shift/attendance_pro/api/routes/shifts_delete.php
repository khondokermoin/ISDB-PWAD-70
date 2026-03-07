<?php
function handle_shifts_delete(int $id): void {
  $pdo = db();

  // Optional safety: prevent deleting shift if used in attendance_logs
  // Uncomment if you want strict rule:
  /*
  $st = $pdo->prepare("SELECT COUNT(*) c FROM attendance_logs WHERE shift_id = ?");
  $st->execute([$id]);
  if ((int)$st->fetchColumn() > 0) {
    json_response(['error' => 'Cannot delete: shift already used in attendance logs'], 409);
  }
  */

  $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = ?");
  $stmt->execute([$id]);

  audit_log((int)($_SESSION['user_id'] ?? 0), 'DELETE_SHIFT', ['shift_id' => $id]);

  json_response(['ok' => true]);
}
