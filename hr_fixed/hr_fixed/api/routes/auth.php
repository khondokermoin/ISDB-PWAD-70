<?php
function route_auth(array $parts, string $m): void {
  $action = $parts[1] ?? '';
  if ($action === 'login' && $m === 'POST') {
    require __DIR__ . '/../auth/login.php';
    handle_login();
  }
  if ($action === 'logout' && $m === 'POST') {
    require __DIR__ . '/../auth/logout.php';
    handle_logout();
  }
  if ($action === 'change_password' && $m === 'POST') {
    require __DIR__ . '/../auth/change_password.php';
    handle_change_password();
  }
  if ($action === 'me' && $m === 'GET') {
    require __DIR__ . '/../auth/me.php';
    handle_me();
  }
  json_response(['error' => 'Not Found'], 404);
}
