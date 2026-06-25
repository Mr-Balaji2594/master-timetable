<?php
$msg = '';
$my_dept = userDeptId();
$emp_id_filter = $_GET['emp_id'] ?? '';
$day_filter = $_GET['day'] ?? 0;
$class_filter = $_GET['class_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_plan'])) {
        $emp_id = intval($_POST['employee_id']);
        $class_id = intval($_POST['class_id']);
        $subject_id = intval($_POST['subject_id']);
        $day = intval($_POST['day']);
        $period = intval($_POST['period']);
        $topic = sanitize($_POST['topic']);
        $description = sanitize($_POST['description']);
        $objectives = sanitize($_POST['objectives']);
        $semester = sanitize($_POST['semester']);

        $conn->query("INSERT INTO lesson_plans (employee_id, class_id, subject_id, day, period, semester, topic, description, objectives) 
                      VALUES ($emp_id, $class_id, $subject_id, $day, $period, '$semester', '$topic', '$description', '$objectives')");
        $msg = 'Lesson plan added successfully';
    } elseif (isset($_POST['delete_plan'])) {
        $conn->query("DELETE FROM lesson_plans WHERE id=" . intval($_POST['delete_plan']));
        $msg = 'Lesson plan deleted';
    }
}

$emp_where = isHOD() ? "AND department_id = $my_dept" : "";
$employees = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_where ORDER BY name");
$class_where = isHOD() ? "WHERE c.department_id = $my_dept" : "";
$dept_filter = isHOD() ? "WHERE department_id = $my_dept" : "";

$where = [];
if ($emp_id_filter) $where[] = "p.employee_id = " . intval($emp_id_filter);
if ($day_filter) $where[] = "p.day = " . intval($day_filter);
if ($class_filter) $where[] = "p.class_id = " . intval($class_filter);
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$plans = $conn->query("SELECT p.*, e.name as emp_name, e.emp_id, c.name as class_name, d.name as dept_name, s.code as subject_code, s.name as subject_name
                       FROM lesson_plans p
                       JOIN employees e ON p.employee_id = e.id
                       JOIN classes c ON p.class_id = c.id
                       JOIN departments d ON c.department_id = d.id
                       JOIN subjects s ON p.subject_id = s.id
                       $where_sql
                       ORDER BY p.day, p.period");
?>
<style>
@media print {
    body * { visibility: hidden; }
    #print-area, #print-area * { visibility: visible; }
    #print-area { position: absolute; left: 0; top: 0; width: 100%; }
    #print-area .btn { display: none; }
    .sidebar, .navbar, .card:not(#print-area) { display: none !important; }
}
</style>
<script>
function printLessonPlan() {
    var printArea = document.getElementById('print-area');
    if (printArea) {
        var original = document.body.innerHTML;
        document.body.innerHTML = printArea.outerHTML;
        window.print();
        document.body.innerHTML = original;
        location.reload();
    } else {
        window.print();
    }
}
</script>
<?php if ($msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<div class="card mb-3">
    <h5>Add Lesson Plan</h5>
    <form method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-2">
            <select name="semester" class="form-select" required>
                <option value="">Sem</option>
                <option value="odd">Odd</option>
                <option value="even">Even</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="employee_id" class="form-select" required>
                <option value="">Employee</option>
                <?php
                $employees->data_seek(0);
                while ($e = $employees->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>"><?= $e['name'] ?> (<?= $e['emp_id'] ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="class_id" class="form-select" required>
                <option value="">Class</option>
                <?php
                $classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id $class_where ORDER BY d.name");
                while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?> - <?= $c['dept_name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="subject_id" class="form-select" required>
                <option value="">Subject</option>
                <?php
                $subs = $conn->query("SELECT * FROM subjects $dept_filter ORDER BY name");
                while ($s = $subs->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>"><?= $s['code'] ?> - <?= $s['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-1">
            <select name="day" class="form-select" required>
                <option value="">Day</option>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>"><?= ['I','II','III','IV','V','VI'][$i-1] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-1">
            <select name="period" class="form-select" required>
                <option value="">Per</option>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="topic" class="form-control" placeholder="Topic" required>
        </div>
        <div class="col-md-2">
            <textarea name="description" class="form-control" placeholder="Description" rows="1"></textarea>
        </div>
        <div class="col-md-2">
            <input type="text" name="objectives" class="form-control" placeholder="Objectives">
        </div>
        <div class="col-md-1">
            <button type="submit" name="add_plan" class="btn btn-success">Add</button>
        </div>
    </form>
</div>

<div class="card mb-3">
    <h5>Filter Plans</h5>
    <form method="GET" class="row g-3">
        <input type="hidden" name="page" value="lesson_plan">
        <div class="col-md-3">
            <select name="emp_id" class="form-select">
                <option value="">All Employees</option>
                <?php
                $employees->data_seek(0);
                while ($e = $employees->fetch_assoc()): ?>
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
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        </div>
        <div class="col-md-3">
            <button type="button" class="btn btn-success" onclick="printLessonPlan()"><i class="bi bi-printer"></i> Print</button>
            <a href="export_pdf.php?type=lesson_plan&emp_id=<?= $emp_id_filter ?>&day=<?= $day_filter ?>&class_id=<?= $class_filter ?>" class="btn btn-danger" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
        </div>
    </form>
</div>

<div class="card" id="print-area">
    <h5>Lesson Plan Details</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Sem</th>
                <th>Day</th>
                <th>Period</th>
                <th>Emp ID</th>
                <th>Employee</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Topic</th>
                <th>Description</th>
                <th>Objectives</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($p = $plans->fetch_assoc()): ?>
            <tr>
                <td><span class="badge bg-<?= $p['semester']=='odd'?'info':'secondary' ?>"><?= ucfirst($p['semester'] ?? 'N/A') ?></span></td>
                <td><?= ['I','II','III','IV','V','VI'][$p['day']-1] ?></td>
                <td><?= $p['period'] ?></td>
                <td><?= $p['emp_id'] ?></td>
                <td><?= $p['emp_name'] ?></td>
                <td><?= $p['class_name'] ?></td>
                <td><?= $p['subject_code'] ?></td>
                <td><?= $p['topic'] ?></td>
                <td><?= $p['description'] ?: '-' ?></td>
                <td><?= $p['objectives'] ?: '-' ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_plan" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
