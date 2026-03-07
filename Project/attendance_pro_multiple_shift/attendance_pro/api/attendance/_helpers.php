<?php
function resolve_employee_shift(PDO $pdo, int $employee_id, string $date): array {
  // Date-based assignment first
  $stmt = $pdo->prepare("SELECT s.* FROM employee_shift_assignments a JOIN shifts s ON s.id = a.shift_id
                         WHERE a.employee_id = ? AND a.effective_from_date <= ? AND (a.effective_to_date IS NULL OR a.effective_to_date >= ?)
                         ORDER BY a.effective_from_date DESC LIMIT 1");
  $stmt->execute([$employee_id, $date, $date]);
  $row = $stmt->fetch();
  if ($row) return $row;

  // Employee default shift
  $stmt = $pdo->prepare("SELECT default_shift_id FROM employees WHERE id = ? LIMIT 1");
  $stmt->execute([$employee_id]);
  $emp = $stmt->fetch();
  if ($emp && $emp['default_shift_id']) {
    $s2 = $pdo->prepare("SELECT * FROM shifts WHERE id = ? LIMIT 1");
    $s2->execute([(int)$emp['default_shift_id']]);
    $row2 = $s2->fetch();
    if ($row2) return $row2;
  }

  // Fallback to latest shift
  $row3 = $pdo->query("SELECT * FROM shifts ORDER BY id DESC LIMIT 1")->fetch();
  if ($row3) return $row3;

  json_response(['error' => 'No shift configured. Create a shift first.'], 400);
}

function in_time_window(string $time, string $start, string $end): bool {
 // Supports same-day and overnight windows.
 // Examples:
 // - 08:30:00 .. 11:00:00  (same-day)
 // - 22:00:00 .. 02:00:00  (overnight)
  if ($start <= $end) {
    return ($time >= $start && $time <= $end);
  }
  
 // Overnight: valid if time is after start OR before end
  return ($time >= $start || $time <= $end);
}
