<?php
header('Content-Type: application/json');
require '../config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$my_dept = userDeptId();
$my_user_id = $_SESSION['user_id'] ?? 0;
$is_admin_or_management = isAdmin() || isPrincipal() || isVicePrincipal();
$is_hod = isHOD();

$emp_id = $_GET['emp_id'] ?? '';
$day = $_GET['day'] ?? 0;
$class_id = $_GET['class_id'] ?? 0;
$format = $_GET['format'] ?? 'json';

$where = [];
if ($is_hod) {
    $where[] = "e.department_id = $my_dept";
} elseif (!$is_admin_or_management) {
    $where[] = "t.employee_id = $my_user_id";
}
if ($emp_id) $where[] = "e.emp_id = '" . $conn->real_escape_string($emp_id) . "'";
if ($day) $where[] = "t.day_of_week = " . intval($day);
if ($class_id) $where[] = "t.class_id = " . intval($class_id);
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$result = $conn->query("SELECT e.emp_id, e.name as emp_name, d.name as dept_name, 
                         t.day_of_week, t.period_no, 
                         c.name as class_name, 
                         s.code as subject_code, s.name as subject_name,
                         t.room_no
                         FROM timetable t
                         JOIN subjects s ON t.subject_id = s.id
                         JOIN employees e ON t.employee_id = e.id
                         JOIN classes c ON t.class_id = c.id
                         JOIN departments d ON c.department_id = d.id
                         $where_sql
                         ORDER BY t.day_of_week, t.period_no");

$lessons = [];
while ($row = $result->fetch_assoc()) {
    $lessons[] = [
        'emp_id' => $row['emp_id'],
        'employee_name' => $row['emp_name'],
        'department' => $row['dept_name'],
        'day' => ['I','II','III','IV','V','VI'][$row['day_of_week']-1],
        'period' => ['I','II','III','IV','V','VI'][$row['period_no']-1],
        'class' => $row['class_name'],
        'subject_code' => $row['subject_code'],
        'subject_name' => $row['subject_name'],
        'room' => $row['room_no']
    ];
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    echo "Employee ID,Employee Name,Department,Day,Period,Class,Subject Code,Subject Name,Room\n";
    foreach ($lessons as $l) {
        echo "{$l['emp_id']},{$l['employee_name']},{$l['department']},{$l['day']},{$l['period']},{$l['class']},{$l['subject_code']},{$l['subject_name']},{$l['room']}\n";
    }
} else {
    echo json_encode($lessons, JSON_PRETTY_PRINT);
}
?>