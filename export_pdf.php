<?php
require 'config.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isLoggedIn()) redirect('index.php');

$type = $_GET['type'] ?? '';
if (!$type) die('No export type specified');

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

function subject_color_pdf($subject_id) {
    $colors = [
        ['bg' => '#dbeafe', 'text' => '#1e40af'],
        ['bg' => '#dcfce7', 'text' => '#166534'],
        ['bg' => '#fef3c7', 'text' => '#92400e'],
        ['bg' => '#fce7f3', 'text' => '#9d174d'],
        ['bg' => '#e0e7ff', 'text' => '#3730a3'],
        ['bg' => '#ccfbf1', 'text' => '#115e59'],
        ['bg' => '#fff7ed', 'text' => '#9a3412'],
        ['bg' => '#f3e8ff', 'text' => '#6b21a8'],
        ['bg' => '#fef2f2', 'text' => '#991b1b'],
        ['bg' => '#e5f4fd', 'text' => '#0c4a6e'],
    ];
    return $colors[$subject_id % count($colors)];
}

function build_timetable_grid($result) {
    $timetable = [];
    while ($row = $result->fetch_assoc()) {
        $timetable[$row['day_of_week']][$row['period_no']][] = $row;
    }
    $day_names = ['I', 'II', 'III', 'IV', 'V', 'VI'];
    $html = '<table class="ttable" style="width:100%;border-collapse:collapse;font-size:10px">';
    $html .= '<thead><tr>';
    $html .= '<th style="background:#667eea;color:#fff;padding:8px 6px;font-weight:bold;text-align:center;border:1px solid #5a6fd6;width:50px">Day</th>';
    for ($p = 1; $p <= 6; $p++) {
        $html .= '<th style="background:#667eea;color:#fff;padding:8px 6px;font-weight:bold;text-align:center;border:1px solid #5a6fd6">Period ' . $p . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    for ($d = 1; $d <= 6; $d++) {
        $html .= '<tr>';
        $html .= '<td style="background:#f1f5f9;padding:6px;font-weight:bold;text-align:center;border:1px solid #e2e8f0;width:50px;vertical-align:middle;font-size:11px">' . $day_names[$d-1] . '</td>';
        for ($p = 1; $p <= 6; $p++) {
            $html .= '<td style="padding:4px;text-align:center;border:1px solid #e2e8f0;vertical-align:top;height:50px">';
            if (isset($timetable[$d][$p])) {
                foreach ($timetable[$d][$p] as $s) {
                    $sc = subject_color_pdf($s['subject_id']);
                    $html .= '<div style="background:' . $sc['bg'] . ';border-left:3px solid ' . $sc['text'] . ';border-radius:4px;padding:3px 5px;margin-bottom:2px;text-align:left">';
                    $html .= '<strong style="color:' . $sc['text'] . ';font-size:9px">' . $s['subject_code'] . '</strong>';
                    if (!empty($s['subject_name'])) {
                        $html .= '<br><span style="color:#64748b;font-size:7px">' . htmlspecialchars($s['subject_name']) . '</span>';
                    }
                    $html .= '<br><span style="color:#475569;font-size:8px">' . $s['emp_name'] . '</span>';
                    if (!empty($s['class_name'])) {
                        $html .= ' <span style="color:#94a3b8;font-size:7px">[' . htmlspecialchars($s['class_name']) . ']</span>';
                    }
                    if (!empty($s['combined_class_name'])) {
                        $html .= '<br><span style="color:#6d28d9;font-weight:bold;font-size:7px">+' . htmlspecialchars($s['combined_class_name']) . '</span>';
                    }
                    if (!empty($s['room_no'])) {
                        $html .= ' <span style="color:#94a3b8;font-size:7px">' . $s['room_no'] . '</span>';
                    }
                    $html .= '</div>';
                }
            }
            $html .= '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

$html = '<html><head><meta charset="UTF-8"><style>
body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #1e293b; margin: 0; padding: 20px; }
h2 { color: #1a1a2e; font-size: 16px; margin: 16px 0 6px; }
h4 { color: #475569; font-size: 12px; margin-top: 0; font-weight: normal; }
table.data { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 8px; }
table.data th { background: #667eea; color: #fff; padding: 6px 8px; border: 1px solid #5a6fd6; text-align: left; font-size: 9px; text-transform: uppercase; }
table.data td { padding: 5px 8px; border: 1px solid #e2e8f0; }
table.data tr:nth-child(even) { background: #f8fafc; }
.header { text-align: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 3px solid #667eea; }
.header .title-line { font-size: 20px; font-weight: 700; color: #1a1a2e; margin: 0; }
.header .sub-title { color: #64748b; font-size: 11px; margin: 2px 0 0; }
.header .info-line { color: #475569; font-size: 12px; margin: 4px 0 0; font-weight: 600; }
.footer { text-align: center; color: #94a3b8; font-size: 9px; margin-top: 16px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; }
.ttable { page-break-inside: avoid; }
.ttable tbody tr:nth-child(even) td { background: #fafbfc; }
</style></head><body>
<div class="header">
    <div class="title-line">Master Timetable</div>
    <div class="sub-title">College Management System</div>
    <div class="info-line">' . strtoupper(str_replace('_', ' ', $type)) . '</div>
</div>';

$my_dept = userDeptId();

if ($type === 'timetable_class') {
    $class_id = intval($_GET['class_id'] ?? 0);
    $semester = $_GET['semester'] ?? '';
    $class = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id WHERE c.id=$class_id")->fetch_assoc();
    if (!$class) die('Class not found');
    $sem_filter = $semester ? " AND t.semester='$semester'" : "";
    $result = $conn->query("SELECT t.*, s.name as subject_name, s.code as subject_code, e.name as emp_name, e.emp_id,
                                   cc.name as combined_class_name
                           FROM timetable t JOIN subjects s ON t.subject_id = s.id
                           JOIN employees e ON t.employee_id = e.id
                           LEFT JOIN timetable tc ON t.combined_group_id = tc.combined_group_id AND tc.class_id != t.class_id
                           LEFT JOIN classes cc ON tc.class_id = cc.id
                           WHERE t.class_id = $class_id $sem_filter
                           ORDER BY t.day_of_week, t.period_no");
    $html .= '<p><strong>Class:</strong> ' . e($class['name']) . ' - ' . e($class['dept_name']) . ($semester ? ' | <strong>Semester:</strong> ' . $semester : '') . '</p>';
    $html .= build_timetable_grid($result);

} elseif ($type === 'timetable_staff') {
    $emp_id = intval($_GET['employee_id'] ?? 0);
    $semester = $_GET['semester'] ?? '';
    $emp = $conn->query("SELECT * FROM employees WHERE id=$emp_id")->fetch_assoc();
    if (!$emp) die('Employee not found');
    $sem_filter = $semester ? " AND t.semester='$semester'" : "";
    $result = $conn->query("SELECT t.*, s.name as subject_name, s.code as subject_code, e.name as emp_name, e.emp_id, c.name as class_name,
                                   cc.name as combined_class_name
                           FROM timetable t JOIN subjects s ON t.subject_id = s.id
                           JOIN employees e ON t.employee_id = e.id
                           JOIN classes c ON t.class_id = c.id
                           LEFT JOIN timetable tc ON t.combined_group_id = tc.combined_group_id AND tc.class_id != t.class_id
                           LEFT JOIN classes cc ON tc.class_id = cc.id
                           WHERE t.employee_id = $emp_id $sem_filter
                           ORDER BY t.day_of_week, t.period_no");
    $html .= '<p><strong>Staff:</strong> ' . e($emp['name']) . ' (' . e($emp['emp_id']) . ')' . ($semester ? ' | <strong>Semester:</strong> ' . $semester : '') . '</p>';
    $html .= build_timetable_grid($result);

} elseif ($type === 'timetable_dept') {
    $dept_id = intval($_GET['dept_id'] ?? $my_dept);
    $semester = $_GET['semester'] ?? '';
    $dept = $conn->query("SELECT * FROM departments WHERE id=$dept_id")->fetch_assoc();
    if (!$dept) die('Department not found');
    $sem_filter = $semester ? " AND t.semester='$semester'" : "";
    $html .= '<p><strong>Department:</strong> ' . e($dept['name']) . ($semester ? ' | <strong>Semester:</strong> ' . $semester : '') . '</p>';
    $classes = $conn->query("SELECT * FROM classes WHERE department_id=$dept_id ORDER BY name");
    while ($c = $classes->fetch_assoc()) {
        $result = $conn->query("SELECT t.*, s.name as subject_name, s.code as subject_code, e.name as emp_name, e.emp_id,
                                       cc.name as combined_class_name
                               FROM timetable t JOIN subjects s ON t.subject_id = s.id
                               JOIN employees e ON t.employee_id = e.id
                               LEFT JOIN timetable tc ON t.combined_group_id = tc.combined_group_id AND tc.class_id != t.class_id
                               LEFT JOIN classes cc ON tc.class_id = cc.id
                               WHERE t.class_id = {$c['id']} $sem_filter
                               ORDER BY t.day_of_week, t.period_no");
        if ($result->num_rows > 0) {
            $html .= '<h2>' . e($c['name']) . ' <span style="font-weight:normal;color:#94a3b8;font-size:14px">(Batch ' . $c['batch_year'] . ')</span></h2>';
            $html .= build_timetable_grid($result);
        }
    }

} elseif ($type === 'lesson_report') {
    $emp_id_filter = intval($_GET['emp_id'] ?? 0);
    $day_filter = intval($_GET['day'] ?? 0);
    $class_filter = intval($_GET['class_id'] ?? 0);
    $sem_filter_val = $_GET['semester'] ?? '';
    $where = [];
    if ($emp_id_filter) $where[] = "t.employee_id = $emp_id_filter";
    if ($day_filter) $where[] = "t.day_of_week = $day_filter";
    if ($class_filter) $where[] = "t.class_id = $class_filter";
    if ($sem_filter_val) $where[] = "t.semester = '$sem_filter_val'";
    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
    $result = $conn->query("SELECT t.*, s.name as subject_name, s.code as subject_code, e.name as emp_name, e.emp_id, c.name as class_name, d.name as dept_name
                           FROM timetable t JOIN subjects s ON t.subject_id = s.id
                           JOIN employees e ON t.employee_id = e.id
                           JOIN classes c ON t.class_id = c.id
                           JOIN departments d ON c.department_id = d.id
                           $where_sql ORDER BY d.name, c.name, t.day_of_week, t.period_no");
    $html .= '<p><strong>Lesson Report</strong> — Lessons taught' . ($sem_filter_val ? " ($sem_filter_val semester)" : '') . '</p>';
    $html .= '<table class="data"><thead><tr><th>Day</th><th>Period</th><th>Emp ID</th><th>Employee</th><th>Class</th><th>Subject</th><th>Room</th></tr></thead><tbody>';
    $days = ['I','II','III','IV','V','VI'];
    while ($r = $result->fetch_assoc()) {
        $html .= '<tr>';
        $html .= '<td>' . $days[$r['day_of_week']-1] . '</td>';
        $html .= '<td>' . $r['period_no'] . '</td>';
        $html .= '<td>' . e($r['emp_id']) . '</td>';
        $html .= '<td>' . e($r['emp_name']) . '</td>';
        $html .= '<td>' . e($r['class_name']) . '</td>';
        $html .= '<td>' . e($r['subject_code']) . '</td>';
        $html .= '<td>' . e($r['room_no'] ?: '-') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

} elseif ($type === 'lesson_plan') {
    $emp_id_filter = intval($_GET['emp_id'] ?? 0);
    $day_filter = intval($_GET['day'] ?? 0);
    $class_filter = intval($_GET['class_id'] ?? 0);
    $where = [];
    if ($emp_id_filter) $where[] = "p.employee_id = $emp_id_filter";
    if ($day_filter) $where[] = "p.day = $day_filter";
    if ($class_filter) $where[] = "p.class_id = $class_filter";
    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
    $result = $conn->query("SELECT p.*, e.name as emp_name, e.emp_id, c.name as class_name, s.code as subject_code, s.name as subject_name
                           FROM lesson_plans p JOIN employees e ON p.employee_id = e.id
                           JOIN classes c ON p.class_id = c.id
                           JOIN subjects s ON p.subject_id = s.id
                           $where_sql ORDER BY p.day, p.period");
    $html .= '<p><strong>Lesson Plan</strong></p>';
    $html .= '<table class="data"><thead><tr><th>Day</th><th>Period</th><th>Emp ID</th><th>Employee</th><th>Class</th><th>Subject</th><th>Topic</th></tr></thead><tbody>';
    $days = ['I','II','III','IV','V','VI'];
    while ($p = $result->fetch_assoc()) {
        $html .= '<tr>';
        $html .= '<td>' . $days[$p['day']-1] . '</td>';
        $html .= '<td>' . $p['period'] . '</td>';
        $html .= '<td>' . e($p['emp_id']) . '</td>';
        $html .= '<td>' . e($p['emp_name']) . '</td>';
        $html .= '<td>' . e($p['class_name']) . '</td>';
        $html .= '<td>' . e($p['subject_code']) . '</td>';
        $html .= '<td>' . e($p['topic']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
} else {
    die('Invalid export type');
}

$html .= '<div class="footer">Generated by Master Timetable System | ' . date('d M Y H:i') . ' | Page 1 of 1</div>';
$html .= '</body></html>';

$filename = str_replace('_', '-', $type) . '_' . date('Ymd') . '.pdf';
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream($filename, ['Attachment' => true]);
exit;
