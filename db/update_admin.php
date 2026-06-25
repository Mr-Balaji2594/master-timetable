<?php
$conn = new mysqli('localhost', 'REDACTED', '', 'college_timetable');
$hash = '<REDACTED_HASH>';
$conn->query("UPDATE employees SET password = '$hash' WHERE emp_id = 'ADMIN001'");
echo "Admin password updated. Login: ADMIN001, Password: REMOVED";
$conn->close();