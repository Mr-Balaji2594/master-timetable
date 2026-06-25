-- Master Timetable Database Setup
-- Database: college_timetable

CREATE DATABASE IF NOT EXISTS college_timetable;
USE college_timetable;

-- Departments Table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    hod_id INT NULL,
    staff_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employees/Staff Table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id VARCHAR(20) UNIQUE NOT NULL,
    department_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    designation VARCHAR(50) NOT NULL,
    total_leave_per_year INT DEFAULT 12,
    casual_leave_limit INT DEFAULT 12,
    medical_leave_limit INT DEFAULT 10,
    onduty_leave_limit INT DEFAULT 5,
    permission_limit INT DEFAULT 5,
    deputation_limit INT DEFAULT 5,
    casual_leave_availed INT DEFAULT 0,
    medical_leave_availed INT DEFAULT 0,
    onduty_leave_availed INT DEFAULT 0,
    permission_availed INT DEFAULT 0,
    deputation_availed INT DEFAULT 0,
    role VARCHAR(20) DEFAULT 'staff',
    phone VARCHAR(20),
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Classes/Batches Table
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    department_id INT NOT NULL,
    batch_year INT NOT NULL,
    year VARCHAR(5),
    section VARCHAR(5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Subjects Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    department_id INT NOT NULL,
    credits INT DEFAULT 0,
    lecture_hours_per_week INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Timetable Table (6 days x 6 periods = 36 slots per week)
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    employee_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Monday to 6=Saturday',
    period_no TINYINT NOT NULL COMMENT '1 to 6',
    semester VARCHAR(10) COMMENT 'odd or even',
    room_no VARCHAR(20),
    combined_group_id INT NULL COMMENT 'links two slots for combined classes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slot (class_id, day_of_week, period_no)
);

-- Lesson Plans Table
CREATE TABLE IF NOT EXISTS lesson_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    day TINYINT NOT NULL,
    period TINYINT NOT NULL,
    semester VARCHAR(10),
    topic VARCHAR(255) NOT NULL,
    description TEXT,
    objectives TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Leave Requests Table
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_date DATE NOT NULL,
    nature VARCHAR(50) DEFAULT 'casual',
    days INT DEFAULT 1,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Substitution Duties Table
CREATE TABLE IF NOT EXISTS substitution_duties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_employee_id INT NOT NULL,
    substitute_employee_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,
    period_no TINYINT NOT NULL,
    leave_date DATE NOT NULL,
    status ENUM('pending', 'accepted', 'completed', 'cancelled') DEFAULT 'pending',
    compensation_hours DECIMAL(3,1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (original_employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (substitute_employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Compensation Table (compensated duties)
CREATE TABLE IF NOT EXISTS compensations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    substitute_employee_id INT NOT NULL,
    original_employee_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    day_of_week TINYINT NOT NULL,
    period_no TINYINT NOT NULL,
    leave_date DATE NOT NULL,
    compensation_date DATE NOT NULL,
    compensation_period TINYINT NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (substitute_employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (original_employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Workload Table
CREATE TABLE IF NOT EXISTS workload (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    total_hours DECIMAL(5,1) DEFAULT 0,
    period_week DECIMAL(5,1) DEFAULT 0,
    computed_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    emp_id VARCHAR(20) NOT NULL,
    action VARCHAR(100) NOT NULL,
    details VARCHAR(500),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);

-- Login Attempts Table (rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45),
    success TINYINT DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp (emp_id, attempted_at),
    INDEX idx_ip (ip_address, attempted_at)
);

-- Insert sample departments
INSERT INTO departments (name, code) VALUES 
('Computer Science', 'CS'),
('Biochemistry', 'BC'),
('Chemistry', 'CE'),
('Computer Application', 'CA'),
('Commerce', 'CM'),
('Commerce_Computer Applications', 'CC'),
('Business Administration', 'BA'),
('PG Commerce', 'MC')
ON DUPLICATE KEY UPDATE name = name;

-- Insert default admin (username: admin, password: admin123)
INSERT INTO employees (emp_id, department_id, name, designation, role, password) 
VALUES ('ADMIN001', 1, 'System Administrator', 'Admin', 'admin', '$2y$12$14AmPKtjL89K099z0demme0TrD0mce5hKiy9i6rrZh2/A/j.NmxXe')
ON DUPLICATE KEY UPDATE name = name;

-- Insert sample classes
INSERT INTO classes (name, department_id, batch_year, year, section) VALUES
('CS', 1, 2024, 'III', 'III'),
('BC', 2, 2024, 'III', 'III'),
('CE', 3, 2024, 'III', 'III'),
('CA', 4, 2024, 'III', 'III'),
('CM', 5, 2024, 'III', 'III'),
('CC', 6, 2024, 'III', 'III'),
('BA', 7, 2024, 'III', 'III'),
('MC', 8, 2024, 'III', 'III'),
('CS', 1, 2025, 'II', 'II'),
('BC', 2, 2025, 'II', 'II'),
('CE', 3, 2025, 'II', 'II'),
('CA', 4, 2025, 'II', 'II'),
('CM', 5, 2025, 'II', 'II'),
('CC', 6, 2025, 'II', 'II'),
('BA', 7, 2025, 'II', 'II'),
('MC', 8, 2025, 'II', 'II'),
('CS', 1, 2026, 'I', 'I'),
('BC', 2, 2026, 'I', 'I'),
('CE', 3, 2026, 'I', 'I'),
('CA', 4, 2026, 'I', 'I'),
('CM', 5, 2026, 'I', 'I'),
('CC', 6, 2026, 'I', 'I'),
('BA', 7, 2026, 'I', 'I'),
('MC', 8, 2026, 'I', 'I')
ON DUPLICATE KEY UPDATE name = name;

-- Migration: add combined_group_id for existing tables (safe to re-run)
ALTER TABLE timetable ADD COLUMN IF NOT EXISTS combined_group_id INT NULL COMMENT 'links two slots for combined classes' AFTER room_no;