<?php
function handle_me(): void {
  $u = require_login();
  json_response(['ok' => true, 'user' => $u]);
}
