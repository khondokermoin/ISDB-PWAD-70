-- MySQL schema for Attendance App (Starter)
-- Import in phpMyAdmin / cPanel MySQL

CREATE TABLE IF NOT EXISTS employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  emp_code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  department VARCHAR(120) NULL,
  designation VARCHAR(120) NULL,
  join_date DATE NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  default_shift_id INT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('admin','employee') NOT NULL,
  username VARCHAR(120) NOT NULL UNIQUE,
  email VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  -- Optional per-user attendance IP restriction.
  -- If set, employee can check-in/out ONLY from this IP (or comma-separated list / CIDR blocks).
  allowed_ip VARCHAR(255) NULL,
  -- Optional per-user attendance device restriction (device token).
  -- If set, employee can check-in/out only when the provided device token matches.
  -- Note: this is not a hardware identifier; it is a per-browser/per-device token stored in the browser.
  allowed_device_tokens VARCHAR(255) NULL,
  password_hash VARCHAR(255) NOT NULL,
  employee_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_users_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  grace_minutes INT NOT NULL DEFAULT 10,
  checkin_window_start TIME NOT NULL,
  checkin_window_end TIME NOT NULL,
  checkout_earliest_time TIME NULL,
  min_daily_minutes INT NOT NULL DEFAULT 480,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_shift_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  shift_id INT NOT NULL,
  effective_from_date DATE NOT NULL,
  effective_to_date DATE NULL,
  CONSTRAINT fk_assign_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_assign_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  work_date DATE NOT NULL,
  shift_id INT NULL,
  check_in DATETIME NULL,
  check_out DATETIME NULL,
  total_minutes INT NULL,
  overtime_minutes INT NULL DEFAULT 0,
  status ENUM('present','absent','late','incomplete','leave','wfh','on_duty','holiday') NOT NULL DEFAULT 'incomplete',
  late_minutes INT NULL DEFAULT 0,
  early_leave_minutes INT NULL DEFAULT 0,
  note TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_employee_date (employee_id, work_date),
  CONSTRAINT fk_att_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_att_shift FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance_settings (
  id INT PRIMARY KEY,
  attendance_open TINYINT(1) NOT NULL DEFAULT 1,
  allow_manual_override TINYINT(1) NOT NULL DEFAULT 1,
  ip_restriction_enabled TINYINT(1) NOT NULL DEFAULT 0,
  allowed_ips TEXT NULL,
  device_binding_enabled TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_date DATE NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  is_paid TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leave_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  yearly_quota_days INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leave_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  leave_type_id INT NOT NULL,
  from_date DATE NOT NULL,
  to_date DATE NOT NULL,
  days_count INT NOT NULL,
  reason TEXT NULL,
  attachment_path VARCHAR(255) NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  decided_by INT NULL,
  decided_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lr_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_lr_type FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT,
  CONSTRAINT fk_lr_admin FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS warnings_discipline (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  month CHAR(7) NOT NULL,
  late_count INT NOT NULL DEFAULT 0,
  action_taken VARCHAR(80) NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_employee_month (employee_id, month),
  CONSTRAINT fk_warn_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT NULL,
  action VARCHAR(120) NOT NULL,
  meta_json JSON NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_audit_user FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(64) NOT NULL,
  username VARCHAR(190) NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  last_attempt_at DATETIME NOT NULL,
  blocked_until DATETIME NULL,
  UNIQUE KEY uniq_login_attempt (ip, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default settings
INSERT INTO attendance_settings (id, attendance_open, allow_manual_override, ip_restriction_enabled, allowed_ips, device_binding_enabled, updated_at)
VALUES (1, 1, 1, 0, '', 0, NOW())
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Seed leave types (edit quotas as you want)
INSERT INTO leave_types (name, yearly_quota_days) VALUES
('Casual', 10),
('Sick', 10),
('Earned', 0)
ON DUPLICATE KEY UPDATE yearly_quota_days = VALUES(yearly_quota_days);

-- Seed one shift (edit as needed)
INSERT INTO shifts (name, start_time, end_time, grace_minutes, checkin_window_start, checkin_window_end, checkout_earliest_time, min_daily_minutes, created_at)
VALUES ('Regular', '09:30:00', '18:30:00', 10, '08:30:00', '11:00:00', NULL, 480, NOW());

-- Create admin user
-- IMPORTANT: Change the admin password immediately after first login.
-- (This seed includes a password hash; update it to match your chosen password.)
INSERT INTO users (role, username, email, phone, password_hash, employee_id, is_active, created_at)
VALUES ('admin', 'admin', NULL, NULL, '$2y$10$Y8uJd6z2F0bZf1p6Z2z4eOQxGz3V7oKpEw0c6rQyB/2n9zI1m2aAm', NULL, 1, NOW())
ON DUPLICATE KEY UPDATE is_active=1;