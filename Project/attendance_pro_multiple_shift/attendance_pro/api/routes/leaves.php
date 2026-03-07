<?php
function route_leaves(array $parts, string $m): void {
  // Starter skeleton. Extend as needed.
  require_login();
  json_response(['ok' => true, 'message' => 'Leaves module starter (not fully implemented yet)']);
}
