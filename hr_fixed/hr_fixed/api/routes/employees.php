<?php
function route_employees(array $parts, string $m): void {
  require_admin();

  $id = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;

  // /employees
  if ($id === null) {
    if ($m === 'GET') {
      require __DIR__ . '/../employees/list.php';
      handle_employees_list();
      return;
    }

    if ($m === 'POST') {
      require __DIR__ . '/../employees/create.php';
      handle_employees_create();
      return;
    }

    json_response(['error' => 'Not Found'], 404);
    return;
  }

  // /employees/{id}
  if ($m === 'PUT') {
    require __DIR__ . '/../employees/update.php';
    handle_employees_update($id);
    return;
  }

  if ($m === 'DELETE') {
    require __DIR__ . '/../employees/delete.php';
    handle_employees_delete($id);
    return;
  }

  // /employees/{id}/activate|deactivate
  if ($m === 'PATCH') {
    $action = $parts[2] ?? '';

    if ($action === 'deactivate') {
      require __DIR__ . '/../employees/toggle.php';
      handle_employee_toggle($id, false);
      return;
    }

    if ($action === 'activate') {
      require __DIR__ . '/../employees/toggle.php';
      handle_employee_toggle($id, true);
      return;
    }

    json_response(['error' => 'Not Found'], 404);
    return;
  }

  json_response(['error' => 'Not Found'], 404);
}
