<?php
$conn = new mysqli('localhost', 'REDACTED', 'REDACTED', 'college_timetable');
$password = password_hash('REMOVED', PASSWORD_DEFAULT);
$conn->query("INSERT INTO employees (emp_id, department_id, name, designation, password) 
VALUES ('ADMIN001', 1, 'System Administrator', 'Admin', '$password') 
ON DUPLICATE KEY UPDATE password = '$password'");
echo "Admin user created/updated. Username: ADMIN001, Password: REMOVED";
$conn->close();