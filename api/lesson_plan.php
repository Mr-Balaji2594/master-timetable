<?php
header('Content-Type: application/json');
require '../config.php';

$emp_id = $_GET['emp_id'] ?? '';
$day = $_GET['day'] ?? 0;
$class_id = $_GET['class_id'] ?? 0;
$format = $_GET['format'] ?? 'json';

$where = [];
if ($emp_id) $where[] = "e.emp_id = '$emp_id'";
if ($day) $where[] = "p.day = " . intval($day);
if ($class_id) $where[] = "p.class_id = " . intval($class_id);
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$result = $conn->query("SELECT e.emp_id, e.name as emp_name, d.name as dept_name, 
                         p.day, p.period, p.topic, p.description, p.objectives,
                         c.name as class_name, 
                         s.code as subject_code, s.name as subject_name
                         FROM lesson_plans p
                         JOIN employees e ON p.employee_id = e.id
                         JOIN classes c ON p.class_id = c.id
                         JOIN departments d ON c.department_id = d.id
                         JOIN subjects s ON p.subject_id = s.id
                         $where_sql
                         ORDER BY p.day, p.period");

$plans = [];
while ($row = $result->fetch_assoc()) {
    $plans[] = [
        'emp_id' => $row['emp_id'],
        'employee_name' => $row['emp_name'],
        'department' => $row['dept_name'],
        'day' => ['I','II','III','IV','V','VI'][$row['day']-1],
        'period' => ['I','II','III','IV','V','VI'][$row['period']-1],
        'class' => $row['class_name'],
        'subject_code' => $row['subject_code'],
        'subject_name' => $row['subject_name'],
        'topic' => $row['topic'],
        'description' => $row['description'],
        'objectives' => $row['objectives']
    ];
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    echo "Employee ID,Employee Name,Department,Day,Period,Class,Subject Code,Subject Name,Topic,Objectives\n";
    foreach ($plans as $p) {
        echo "{$p['emp_id']},{$p['employee_name']},{$p['department']},{$p['day']},{$p['period']},{$p['class']},{$p['subject_code']},{$p['subject_name']},{$p['topic']},{$p['objectives']}\n";
    }
} else {
    echo json_encode($plans, JSON_PRETTY_PRINT);
}
?>