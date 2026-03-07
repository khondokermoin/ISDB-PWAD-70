<?php
function route_roster(array $parts, string $m): void {
  require_admin();

  // /roster
  if (count($parts) === 1) {
    if ($m === 'GET') {
      require __DIR__ . '/../roster/list.php';
      handle_roster_list();
      return;
    }
    if ($m === 'POST') {
      require __DIR__ . '/../roster/create.php';
      handle_roster_create();
      return;
    }
  }

  // /roster/{id}
  $id = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
  if ($id !== null) {
    if ($m === 'DELETE') {
      require __DIR__ . '/../roster/delete.php';
      handle_roster_delete($id);
      return;
    }
  }

  json_response(['error' => 'Not Found'], 404);
}
