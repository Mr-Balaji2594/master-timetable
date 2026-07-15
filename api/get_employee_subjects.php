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

$result = $conn->query("SELECT s.id, s.code, s.name
                        FROM employee_subjects es
                        JOIN subjects s ON es.subject_id = s.id
                        WHERE es.employee_id = $employee_id
                        ORDER BY s.name");

$subjects = [];
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}

if (empty($subjects)) {
    $emp = $conn->query("SELECT department_id FROM employees WHERE id = $employee_id")->fetch_assoc();
    if ($emp) {
        $dept_id = intval($emp['department_id']);
        $result = $conn->query("SELECT id, code, name FROM subjects WHERE department_id = $dept_id ORDER BY name");
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
    }
}

echo json_encode($subjects);
?>