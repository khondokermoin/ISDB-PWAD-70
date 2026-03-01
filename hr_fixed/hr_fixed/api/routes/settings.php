<?php
function route_settings(array $parts, string $m): void {
  if ($m === 'GET') { require_login(); require __DIR__ . '/../settings/get.php'; handle_settings_get(); }
  if ($m === 'PUT') { require_admin(); require __DIR__ . '/../settings/update.php'; handle_settings_update(); }
  json_response(['error' => 'Not Found'], 404);
}
