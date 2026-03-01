<?php
function handle_logout(): void {
  $u = $_SESSION['user']['id'] ?? null;
  audit_log($u ? (int)$u : null, 'LOGOUT', []);
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
  json_response(['ok' => true]);
}
