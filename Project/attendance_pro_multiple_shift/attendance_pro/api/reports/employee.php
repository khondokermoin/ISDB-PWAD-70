<?php
function handle_reports_employee(): void {
  // Reuse attendance/month endpoint logic by calling it
  require __DIR__ . '/../attendance/month.php';
  handle_attendance_month();
}
