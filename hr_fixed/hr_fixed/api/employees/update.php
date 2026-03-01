<?php

function handle_employees_update(int $id): void {
  // JSON body read
  $data = get_json_body();

  // fallback: যদি কোনো hosting এ JSON empty আসে, তখন POST data দেখবে
  if ((!is_array($data) || !$data) && !empty($_POST)) {
    $data = $_POST;
  }

  if (!is_array($data) || !$data) {
    json_response(['error' => 'Empty request body'], 422);
  }

  // ✅ emp_code added
  $allowed = [
    'emp_code',
    'name',
    'phone',
    'email',
    'department',
    'designation',
    'join_date',
    'default_shift_id',
    'status'
  ];

  // Per-user attendance restrictions stored in users table (linked by employee_id)
  $allowedUserFields = ['allowed_ip', 'allowed_mac'];

  $set = [];
  $vals = [];

  foreach ($allowed as $f) {
    if (!array_key_exists($f, $data)) continue;

    $v = $data[$f];

    // normalize strings
    if (is_string($v)) {
      $v = trim($v);
      if ($v === '') $v = null;
    }

    // cast default_shift_id
    if ($f === 'default_shift_id') {
      if ($v === null || $v === '') $v = null;
      else $v = (int)$v;
    }

    // validate status
    if ($f === 'status') {
      if ($v === null) continue;
      $v = strtolower((string)$v);
      if (!in_array($v, ['active', 'inactive'], true)) {
        json_response(['error' => 'Invalid status'], 422);
      }
    }

    $set[] = "$f = ?";
    $vals[] = $v;
  }

  // Handle allowed_ip / allowed_mac updates (users table)
  $userSet = [];
  $userVals = [];

  if (array_key_exists('allowed_ip', $data)) {
    $allowed_ip = trim((string)($data['allowed_ip'] ?? ''));
    if ($allowed_ip === '') {
      $userSet[] = "allowed_ip = NULL";
    } else {
      // Validate allowed_ip (optional). Supports IPv4/IPv6, comma list, CIDR.
      $err = validate_allowed_ip_string($allowed_ip);
      if ($err) json_response(['error' => $err], 422);
      $userSet[] = "allowed_ip = ?";
      $userVals[] = $allowed_ip;
    }
  }

  if (array_key_exists('allowed_mac', $data)) {
    $allowed_mac = trim((string)($data['allowed_mac'] ?? ''));
    if ($allowed_mac === '') {
      $userSet[] = "allowed_mac = NULL";
    } else {
      $entries = array_filter(array_map('trim', explode(',', $allowed_mac)));
      foreach ($entries as $entry) {
        if ($entry === '') continue;
        if (mac_normalize($entry) === '') {
          json_response(['error' => 'Invalid allowed_mac (use format like AA:BB:CC:DD:EE:FF)'], 422);
        }
      }
      $userSet[] = "allowed_mac = ?";
      $userVals[] = $allowed_mac;
    }
  }

  if (!$set && !$userSet) {
    json_response(['error' => 'No fields to update'], 422);
  }

  $pdo = db();
  $pdo->beginTransaction();
  try {

  $changedEmp = 0;
  $changedUser = 0;

  // update employees table (if needed)
  if ($set) {
    $vals[] = $id;
    $sql = "UPDATE employees SET " . implode(', ', $set) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);
    $changedEmp = $stmt->rowCount();
  }

  // update users table restrictions (if needed)
  if ($userSet) {
    // Find corresponding user id
    $u = $pdo->prepare("SELECT id FROM users WHERE employee_id = ? AND role='employee' LIMIT 1");
    $u->execute([$id]);
    $uRow = $u->fetch(PDO::FETCH_ASSOC);
    if ($uRow) {
      $userVals[] = (int)$uRow['id'];
      $sqlU = "UPDATE users SET " . implode(', ', $userSet) . " WHERE id = ?";
      $stU = $pdo->prepare($sqlU);
      $stU->execute($userVals);
      $changedUser = $stU->rowCount();
    }
  }

  // fetch updated row (always return to verify)
  $st2 = $pdo->prepare("SELECT e.*, u.allowed_ip, u.allowed_mac
                         FROM employees e
                         LEFT JOIN users u ON u.employee_id = e.id AND u.role='employee'
                         WHERE e.id = ?");
  $st2->execute([$id]);
  $emp = $st2->fetch(PDO::FETCH_ASSOC);

  if (!$emp) {
    json_response(['error' => 'Employee not found'], 404);
  }

  $pdo->commit();

  $actor = $_SESSION['user']['id'] ?? null;
  audit_log($actor ? (int)$actor : null, 'UPDATE_EMPLOYEE', [
    'employee_id' => $id,
    'changed_rows_employees' => $changedEmp,
    'changed_rows_users' => $changedUser,
    'fields' => array_keys($data)
  ]);

  json_response([
    'ok' => true,
    'changed' => ($changedEmp + $changedUser),
    'employee' => $emp
  ]);
  } catch (Throwable $e) {
    $pdo->rollBack();
    server_log('UPDATE_EMPLOYEE_FAILED', ['employee_id' => $id, 'error' => $e->getMessage()]);
    json_response(['error' => 'Update failed'], 400);
  }
}
