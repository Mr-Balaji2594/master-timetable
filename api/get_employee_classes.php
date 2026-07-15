<?php
header('Content-Type: application/json');
require '../config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$employee_id = intval($_GET['employee_id'] ?? 0);
if (!$employee_id) {
    echo json_encode([]);
    exit;
}

$has_subjects = $conn->query("SELECT COUNT(*) as cnt FROM employee_subjects WHERE employee_id = $employee_id")->fetch_assoc()['cnt'];

if ($has_subjects > 0) {
    $result = $conn->query("SELECT c.id, c.name, c.year, d.name as dept_name,
                                   GROUP_CONCAT(DISTINCT t.room_no ORDER BY t.room_no SEPARATOR ', ') as rooms
                            FROM employee_subjects es
                            JOIN subjects s ON es.subject_id = s.id
                            JOIN departments d ON s.department_id = d.id
                            JOIN classes c ON c.department_id = d.id
                            LEFT JOIN timetable t ON t.class_id = c.id AND t.employee_id = $employee_id
                            WHERE es.employee_id = $employee_id
                            GROUP BY c.id, c.name, c.year, d.name
                            ORDER BY d.name, c.name");
} else {
    $emp = $conn->query("SELECT department_id FROM employees WHERE id = $employee_id")->fetch_assoc();
    $dept_id = $emp ? intval($emp['department_id']) : 0;
    $result = $conn->query("SELECT c.id, c.name, c.year, d.name as dept_name,
                                   GROUP_CONCAT(DISTINCT t.room_no ORDER BY t.room_no SEPARATOR ', ') as rooms
                            FROM classes c
                            JOIN departments d ON c.department_id = d.id
                            LEFT JOIN timetable t ON t.class_id = c.id AND t.employee_id = $employee_id
                            WHERE c.department_id = $dept_id
                            GROUP BY c.id, c.name, c.year, d.name
                            ORDER BY d.name, c.name");
}

$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

echo json_encode($classes);
?>