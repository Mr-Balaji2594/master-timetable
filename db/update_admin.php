<?php
$conn = new mysqli('localhost', 'root', '', 'college_timetable');
$hash = '$2y$10$8Nlgn8brBwzVzXuLKyNUPeRz5z7v.6Nmb98ab.pEPi4Sg6RGp7/wC';
$conn->query("UPDATE employees SET password = '$hash' WHERE emp_id = 'ADMIN001'");
echo "Admin password updated. Login: ADMIN001, Password: admin123";
$conn->close();