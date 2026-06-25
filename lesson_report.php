<?php
$msg = '';
$my_dept = userDeptId();
$emp_id_filter = $_GET['emp_id'] ?? '';
$day_filter = $_GET['day'] ?? 0;
$class_filter = $_GET['class_id'] ?? 0;
$sem_filter = $_GET['semester'] ?? '';

$emp_where = isHOD() ? "AND department_id = $my_dept" : "";
$employees = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_where ORDER BY name");
$class_where = isHOD() ? "WHERE c.department_id = $my_dept" : "";
$dept_where = isHOD() ? "AND c.department_id = $my_dept" : "";

$where = [];
if ($emp_id_filter) $where[] = "t.employee_id = " . intval($emp_id_filter);
if ($day_filter) $where[] = "t.day_of_week = " . intval($day_filter);
if ($class_filter) $where[] = "t.class_id = " . intval($class_filter);
if ($sem_filter) $where[] = "t.semester = '$sem_filter'";
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$report = $conn->query("SELECT t.*, s.name as subject_name, s.code as subject_code, e.name as emp_name, e.emp_id, c.name as class_name, d.name as dept_name
                       FROM timetable t
                       JOIN subjects s ON t.subject_id = s.id
                       JOIN employees e ON t.employee_id = e.id
                       JOIN classes c ON t.class_id = c.id
                       JOIN departments d ON c.department_id = d.id
                       $where_sql
                       ORDER BY d.name, c.name, t.day_of_week, t.period_no");

$summary = $conn->query("SELECT e.emp_id, e.name, d.name as dept_name, COUNT(*) as total_classes, COUNT(DISTINCT CONCAT(t.day_of_week, '-', t.period_no)) as periods
                        FROM timetable t
                        JOIN employees e ON t.employee_id = e.id
                        JOIN classes c ON t.class_id = c.id
                        JOIN departments d ON c.department_id = d.id
                        $where_sql
                        GROUP BY e.id
                        ORDER BY d.name, e.name");
?>
<div class="card mb-3">
    <h5>Filter Report</h5>
    <form method="GET" class="row g-3">
        <input type="hidden" name="page" value="lesson_report">
        <div class="col-md-2">
            <select name="emp_id" class="form-select">
                <option value="">All Employees</option>
                <?php while ($e = $employees->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>" <?= $emp_id_filter==$e['id']?'selected':'' ?>><?= $e['name'] ?> (<?= $e['emp_id'] ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="day" class="form-select">
                <option value="">All Days</option>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>" <?= $day_filter==$i?'selected':'' ?>><?= ['I','II','III','IV','V','VI'][$i-1] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="class_id" class="form-select">
                <option value="">All Classes</option>
                <?php
                $classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id $class_where ORDER BY d.name");
                while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $class_filter==$c['id']?'selected':'' ?>><?= $c['name'] ?> - <?= $c['dept_name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="semester" class="form-select">
                <option value="">All Sem</option>
                <option value="odd" <?= $sem_filter=='odd'?'selected':'' ?>>Odd</option>
                <option value="even" <?= $sem_filter=='even'?'selected':'' ?>>Even</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            <button type="button" class="btn btn-info" onclick="exportCSV()"><i class="bi bi-download"></i> CSV</button>
            <a href="export_pdf.php?type=lesson_report&emp_id=<?= $emp_id_filter ?>&day=<?= $day_filter ?>&class_id=<?= $class_filter ?>&semester=<?= $sem_filter ?>" class="btn btn-danger" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
        </div>
    </form>
</div>
<script>
function exportCSV() {
    var tables = document.querySelectorAll('.card table');
    if (!tables.length) return;
    var csv = [];
    tables.forEach(function(table) {
        var rows = table.querySelectorAll('tr');
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('th, td');
            var rowData = [];
            cells.forEach(function(cell) {
                var text = cell.innerText.replace(/"/g, '""').trim();
                rowData.push('"' + text + '"');
            });
            if (rowData.length) csv.push(rowData.join(','));
        });
        csv.push('');
    });
    var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'lesson_report_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}
</script>

<?php if ($emp_id_filter || $day_filter || $class_filter || $sem_filter): ?>
<div class="card mb-3">
    <h5>Summary by Employee</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Emp ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Total Classes</th>
                <th>Periods/Week</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($s = $summary->fetch_assoc()): ?>
            <tr>
                <td><?= $s['emp_id'] ?></td>
                <td><?= $s['name'] ?></td>
                <td><?= $s['dept_name'] ?></td>
                <td><?= $s['total_classes'] ?></td>
                <td><?= $s['periods'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <h5>Lesson Taught Details</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Sem</th>
                <th>Day</th>
                <th>Period</th>
                <th>Emp ID</th>
                <th>Employee</th>
                <th>Class</th>
                <th>Dept</th>
                <th>Subject</th>
                <th>Room</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($r = $report->fetch_assoc()): ?>
            <tr>
                <td><span class="badge bg-<?= $r['semester']=='odd'?'info':'secondary' ?>"><?= ucfirst($r['semester'] ?? 'N/A') ?></span></td>
                <td><?= ['I','II','III','IV','V','VI'][$r['day_of_week']-1] ?></td>
                <td><?= $r['period_no'] ?></td>
                <td><?= $r['emp_id'] ?></td>
                <td><?= $r['emp_name'] ?></td>
                <td><?= $r['class_name'] ?></td>
                <td><?= $r['dept_name'] ?></td>
                <td><?= $r['subject_code'] ?> - <?= $r['subject_name'] ?></td>
                <td><?= $r['room_no'] ?: '-' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
