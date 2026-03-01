<?php
function handle_shifts_list(): void {
  $pdo = db();
  $rows = $pdo->query("SELECT * FROM shifts ORDER BY id DESC")->fetchAll();
  json_response(['ok' => true, 'shifts' => $rows]);
}
