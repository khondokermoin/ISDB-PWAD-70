<?php
function route_reports(array $parts, string $m): void {
  if (($parts[1] ?? '') === 'summary' && $m === 'GET') { require_admin(); require __DIR__ . '/../reports/summary.php'; handle_reports_summary(); }
  if (($parts[1] ?? '') === 'employee' && $m === 'GET') { require_login(); require __DIR__ . '/../reports/employee.php'; handle_reports_employee(); }
  if (($parts[1] ?? '') === 'export' && $m === 'GET') { require_admin(); require __DIR__ . '/../reports/export.php'; handle_reports_export(); }
  json_response(['error' => 'Not Found'], 404);
}
