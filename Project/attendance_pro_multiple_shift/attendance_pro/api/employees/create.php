<?php
function handle_employees_create(): void {
  $data = get_json_body();
  $emp_code = trim((string)($data['emp_code'] ?? ''));
  $name = trim((string)($data['name'] ?? ''));
  $phone = trim((string)($data['phone'] ?? ''));
  $email = trim((string)($data['email'] ?? ''));
  $department = trim((string)($data['department'] ?? ''));
  $designation = trim((string)($data['designation'] ?? ''));
  $join_date = (string)($data['join_date'] ?? date('Y-m-d'));
  $default_shift_id = isset($data['default_shift_id']) ? (int)$data['default_shift_id'] : null;

  // Create login for employee
  $username = trim((string)($data['username'] ?? $emp_code));
  $password = (string)($data['password'] ?? '');
  $allowed_ip = trim((string)($data['allowed_ip'] ?? ''));
  $allowed_device_tokens = trim((string)($data['allowed_device_tokens'] ?? ''));

  if ($emp_code === '' || $name === '' || $username === '' || $password === '') {
    json_response(['error' => 'emp_code, name, username, password required'], 422);
  }

  // Validate allowed_ip (optional). Supports IPv4/IPv6, comma list, CIDR.
  if ($allowed_ip !== '') {
    $err = validate_allowed_ip_string($allowed_ip);
    if ($err) json_response(['error' => $err], 422);
  }

  // Validate allowed_device_tokens (optional). Comma-separated UUID tokens.
  if ($allowed_device_tokens !== '') {
    $entries = array_filter(array_map('trim', explode(',', $allowed_device_tokens)));
    foreach ($entries as $entry) {
      if ($entry === '') continue;
      if (!device_token_is_valid($entry)) {
        json_response(['error' => 'Invalid allowed_device_tokens (use the Device Token shown on the employee page)'], 422);
      }
    }
  }

  $pdo = db();
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare("INSERT INTO employees (emp_code, name, phone, email, department, designation, join_date, status, default_shift_id, created_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)");
    $stmt->execute([$emp_code, $name, $phone, $email, $department, $designation, $join_date, $default_shift_id, now_dt()]);
    $employee_id = (int)$pdo->lastInsertId();

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt2 = $pdo->prepare("INSERT INTO users (role, username, email, phone, allowed_ip, allowed_device_tokens, password_hash, employee_id, is_active, created_at)
                            VALUES ('employee', ?, ?, ?, ?, ?, ?, ?, 1, ?)");
    $stmt2->execute([
      $username,
      $email ?: null,
      $phone ?: null,
      $allowed_ip !== '' ? $allowed_ip : null,
      $allowed_device_tokens !== '' ? $allowed_device_tokens : null,
      $hash,
      $employee_id,
      now_dt()
    ]);

    $pdo->commit();

    $actor = $_SESSION['user']['id'] ?? null;
    audit_log($actor ? (int)$actor : null, 'CREATE_EMPLOYEE', ['employee_id' => $employee_id, 'emp_code' => $emp_code]);

    json_response(['ok' => true, 'employee_id' => $employee_id], 201);
  } catch (Throwable $e) {
    $pdo->rollBack();
    server_log('CREATE_EMPLOYEE_FAILED', ['error' => $e->getMessage()]);
    json_response(['error' => 'Create failed'], 400);
  }
}
