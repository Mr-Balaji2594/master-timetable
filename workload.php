<?php
$msg = '';
$is_admin = isAdmin();
$user_id = $_SESSION['user_id'];
$my_dept = userDeptId();
$can_manage = isAdmin() || isHOD();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['calculate_workload'])) {
        $emp_id = intval($_POST['employee_id']);
        
        $total = $conn->query("SELECT COUNT(*) as total FROM timetable WHERE employee_id = $emp_id")->fetch_assoc()['total'];
        $periods = $conn->query("SELECT COUNT(DISTINCT CONCAT(day_of_week, '-', period_no)) as periods FROM timetable WHERE employee_id = $emp_id")->fetch_assoc()['periods'];
        
        $today = date('Y-m-d');
        $conn->query("DELETE FROM workload WHERE employee_id = $emp_id AND computed_date = '$today'");
        $conn->query("INSERT INTO workload (employee_id, total_hours, period_week, computed_date) VALUES ($emp_id, $total, $periods, '$today')");
        $msg = 'Workload calculated: ' . $total . ' total classes, ' . $periods . ' periods/week';
    } elseif (isset($_POST['calculate_all'])) {
        $emps = $conn->query("SELECT id FROM employees WHERE is_active=1");
        while ($e = $emps->fetch_assoc()) {
            $emp_id = $e['id'];
            $total = $conn->query("SELECT COUNT(*) as total FROM timetable WHERE employee_id = $emp_id")->fetch_assoc()['total'];
            $periods = $conn->query("SELECT COUNT(DISTINCT CONCAT(day_of_week, '-', period_no)) as periods FROM timetable WHERE employee_id = $emp_id")->fetch_assoc()['periods'];
            $today = date('Y-m-d');
            $conn->query("DELETE FROM workload WHERE employee_id = $emp_id AND computed_date = '$today'");
            $conn->query("INSERT INTO workload (employee_id, total_hours, period_week, computed_date) VALUES ($emp_id, $total, $periods, '$today')");
        }
        $msg = 'Workload calculated for all staff';
    }
}

$show_all = $_GET['show'] ?? ($can_manage ? 'all' : $user_id);
$emp_where_extra = isHOD() ? "AND e.department_id = $my_dept" : "";
$employees = $conn->query("SELECT e.*, d.name as dept_name, w.total_hours, w.period_week, w.computed_date
                         FROM employees e 
                         JOIN departments d ON e.department_id = d.id
                         LEFT JOIN workload w ON e.id = w.employee_id AND w.computed_date = CURDATE()
                         WHERE e.is_active=1 $emp_where_extra
                         ORDER BY d.name, e.name");

$depts = $conn->query("SELECT * FROM departments ORDER BY name");
?>
<div class="card">
    <h5>Calculate Workload</h5>
    <form method="POST" class="row g-3">
        <?= csrf_field() ?>
        <?php if ($can_manage): ?>
        <div class="col-md-4">
            <select name="employee_id" class="form-select">
                <option value="">Select Employee</option>
                <?php 
                $emps = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_where_extra ORDER BY name");
                while ($e = $emps->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>"><?= $e['name'] ?> (<?= $e['emp_id'] ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" name="calculate_workload" class="btn btn-primary">Calculate</button>
        </div>
        <?php if (isAdmin()): ?>
        <div class="col-md-3">
            <button type="submit" name="calculate_all" class="btn btn-warning">Calculate All</button>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="col-md-4">
            <button type="submit" name="calculate_workload" class="btn btn-primary">View My Workload</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<div class="card">
    <h5>Staff Workload Summary</h5>
    <p class="text-muted">Max workload: 36 periods/week (6 days × 6 periods)</p>
    <table class="table">
        <thead>
            <tr>
                <th>Emp ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Designation</th>
                <th>Total Classes</th>
                <th>Periods/Week</th>
                <th>Workload</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($e = $employees->fetch_assoc()): 
                $percentage = ($e['period_week'] / 36) * 100;
                $color = $percentage > 100 ? 'danger' : ($percentage > 75 ? 'warning' : 'success');
            ?>
            <tr>
                <td><?= $e['emp_id'] ?></td>
                <td><?= $e['name'] ?></td>
                <td><?= $e['dept_name'] ?></td>
                <td><?= $e['designation'] ?></td>
                <td><?= $e['total_hours'] ?: 0 ?></td>
                <td><?= $e['period_week'] ?: 0 ?></td>
                <td>
                    <div class="workload-bar" style="width: 100px">
                        <div class="workload-fill" style="width: <?= min($percentage, 100) ?>%; background: <?= $color === 'danger' ? '#e74c3c' : ($color === 'warning' ? '#f39c12' : '#27ae60') ?>"></div>
                    </div>
                    <small><?= round($percentage) ?>%</small>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>