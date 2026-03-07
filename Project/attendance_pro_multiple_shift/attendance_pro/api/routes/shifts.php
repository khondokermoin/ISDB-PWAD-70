<?php
function route_shifts(array $parts, string $m): void {

  // /shifts
  if ($m === 'GET') {
    require_login();
    require __DIR__ . '/../settings/shifts_list.php';
    handle_shifts_list();
    return;
  }

  require_admin();

  if ($m === 'POST') {
    require __DIR__ . '/../settings/shifts_create.php';
    handle_shifts_create();
    return;
  }

  $id = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;

  if ($id !== null && $m === 'PUT') {
    require __DIR__ . '/../settings/shifts_update.php';
    handle_shifts_update($id);
    return;
  }

  if ($id !== null && $m === 'DELETE') {
    require __DIR__ . '/../settings/shifts_delete.php';
    handle_shifts_delete($id);
    return;
  }

  json_response(['error' => 'Not Found'], 404);
}
