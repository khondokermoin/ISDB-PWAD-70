<?php
function route_attendance(array $parts, string $m): void {
  $action = $parts[1] ?? '';
  if ($action === 'checkin' && $m === 'POST') { require __DIR__ . '/../attendance/checkin.php'; handle_checkin(); }
  if ($action === 'checkout' && $m === 'POST') { require __DIR__ . '/../attendance/checkout.php'; handle_checkout(); }
  if ($action === 'day' && $m === 'GET') { require_admin(); require __DIR__ . '/../attendance/day.php'; handle_attendance_day(); }
  if ($action === 'month' && $m === 'GET') { require_login(); require __DIR__ . '/../attendance/month.php'; handle_attendance_month(); }
  if ($action === 'override' && $m === 'POST') { require_admin(); require __DIR__ . '/../attendance/override.php'; handle_attendance_override(); }
  json_response(['error' => 'Not Found'], 404);
}
