<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$parts = parse_path();
$m = method();

if (count($parts) === 0) {
  json_response(['ok' => true, 'service' => 'Attendance API']);
}

$resource = $parts[0] ?? '';
$id = $parts[1] ?? null;

switch ($resource) {
  case 'auth':
    require __DIR__ . '/routes/auth.php';
    route_auth($parts, $m);
    break;

  case 'employees':
    require __DIR__ . '/routes/employees.php';
    route_employees($parts, $m);
    break;

  case 'attendance':
    require __DIR__ . '/routes/attendance.php';
    route_attendance($parts, $m);
    break;

  case 'settings':
    require __DIR__ . '/routes/settings.php';
    route_settings($parts, $m);
    break;

  case 'shifts':
    require __DIR__ . '/routes/shifts.php';
    route_shifts($parts, $m);
    break;

  case 'reports':
    require __DIR__ . '/routes/reports.php';
    route_reports($parts, $m);
    break;

  case 'leaves':
    require __DIR__ . '/routes/leaves.php';
    route_leaves($parts, $m);
    break;

  default:
    json_response(['error' => 'Not Found'], 404);
}
