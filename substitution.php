<?php
$msg = '';
$is_admin = isAdmin();
$user_id = $_SESSION['user_id'];
$my_dept = userDeptId();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['assign_substitution'])) {
        $original_id = intval($_POST['original_employee_id']);
        $substitute_id = intval($_POST['substitute_employee_id']);
        $class_id = intval($_POST['class_id']);
        $subject_id = intval($_POST['subject_id']);
        $day = intval($_POST['day_of_week']);
        $period = intval($_POST['period_no']);
        $leave_date = sanitize($_POST['leave_date']);
        
        $conn->query("INSERT INTO substitution_duties (original_employee_id, substitute_employee_id, class_id, subject_id, day_of_week, period_no, leave_date) 
                      VALUES ($original_id, $substitute_id, $class_id, $subject_id, $day, $period, '$leave_date')");
        $msg = 'Substitution assigned';
    } elseif (isset($_POST['accept'])) {
        $conn->query("UPDATE substitution_duties SET status='accepted' WHERE id=" . intval($_POST['accept']));
        $msg = 'Substitution accepted';
    } elseif (isset($_POST['complete'])) {
        $sub_id = intval($_POST['complete']);
        $comp_day = intval($_POST['comp_day']);
        $comp_period = intval($_POST['comp_period']);
        $comp_date = sanitize($_POST['compensation_date']);
        
        $sub = $conn->query("SELECT * FROM substitution_duties WHERE id=$sub_id")->fetch_assoc();
        
        $conn->query("INSERT INTO compensations (substitute_employee_id, original_employee_id, class_id, subject_id, day_of_week, period_no, leave_date, compensation_date, compensation_period, status) 
                      VALUES ({$sub['substitute_employee_id']}, {$sub['original_employee_id']}, {$sub['class_id']}, {$sub['subject_id']}, {$sub['day_of_week']}, {$sub['period_no']}, '{$sub['leave_date']}', '$comp_date', $comp_period, 'completed')");
        $conn->query("UPDATE substitution_duties SET status='completed', compensation_hours={$comp_period} WHERE id=$sub_id");
        $msg = 'Substitution completed with compensation';
    } elseif (isset($_POST['cancel'])) {
        $conn->query("UPDATE substitution_duties SET status='cancelled' WHERE id=" . intval($_POST['cancel']));
        $msg = 'Substitution cancelled';
    }
}

$sub_where = isHOD() ? "WHERE (o.department_id = $my_dept OR s.department_id = $my_dept)" : "";
$subs = $conn->query("SELECT sub.*, 
                       o.name as original_name, o.emp_id as original_emp_id,
                       s.name as substitute_name, s.emp_id as substitute_emp_id,
                       c.name as class_name, sub2.name as subject_name,
                       sub2.code as subject_code
                       FROM substitution_duties sub
                       JOIN employees o ON sub.original_employee_id = o.id
                       JOIN employees s ON sub.substitute_employee_id = s.id
                       JOIN classes c ON sub.class_id = c.id
                       JOIN subjects sub2 ON sub.subject_id = sub2.id
                       $sub_where
                       ORDER BY sub.created_at DESC");

$emp_where = isHOD() ? "AND department_id = $my_dept" : "";
$employees = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_where ORDER BY name");
$class_where = isHOD() ? "WHERE c.department_id = $my_dept" : "";
$classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id $class_where ORDER BY d.name");
$subj_where = isHOD() ? "WHERE department_id = $my_dept" : "";

$depts = $conn->query("SELECT * FROM departments ORDER BY name");
?>
<div class="card">
    <h5>Assign Substitution Duty</h5>
    <form method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-2">
            <select name="original_employee_id" class="form-select" required>
                <option value="">Select Employee on Leave</option>
                <?php 
                $emps = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_where ORDER BY name");
                while ($e = $emps->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>"><?= $e['name'] ?> (<?= $e['emp_id'] ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="substitute_employee_id" class="form-select" required>
                <option value="">Select Substitute</option>
                <?php 
                $emps = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_where ORDER BY name");
                while ($e = $emps->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>"><?= $e['name'] ?> (<?= $e['emp_id'] ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="class_id" class="form-select" required>
                <option value="">Class</option>
                <?php while ($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="subject_id" class="form-select" required>
                <option value="">Subject</option>
                <?php
                $subs2 = $conn->query("SELECT * FROM subjects $subj_where ORDER BY name");
                while ($s = $subs2->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>"><?= $s['code'] ?> - <?= $s['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="day_of_week" class="form-select" required>
                <option value="">Day</option>
                <option value="1">I</option>
                <option value="2">II</option>
                <option value="3">III</option>
                <option value="4">IV</option>
                <option value="5">V</option>
                <option value="6">VI</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="period_no" class="form-select" required>
                <option value="">Period</option>
                <option value="1">I</option>
                <option value="2">II</option>
                <option value="3">III</option>
                <option value="4">IV</option>
                <option value="5">V</option>
                <option value="6">VI</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="leave_date" class="form-control" required>
        </div>
        <div class="col-md-3">
            <button type="submit" name="assign_substitution" class="btn btn-success">Assign</button>
        </div>
    </form>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<div class="card">
    <h5>Substitution Duties</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Original</th>
                <th>Substitute</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Day</th>
                <th>Period</th>
                <th>Status</th>
                <th>Comp.</th>
                <?php if ($is_admin || isHOD()): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php while ($s = $subs->fetch_assoc()): ?>
            <tr>
                <td><?= $s['leave_date'] ?></td>
                <td><?= $s['original_name'] ?></td>
                <td><?= $s['substitute_name'] ?></td>
                <td><?= $s['class_name'] ?></td>
                <td><?= $s['subject_code'] ?></td>
                <td><?= ['I','II','III','IV','V','VI'][$s['day_of_week']-1] ?></td>
                <td><?= ['I','II','III','IV','V','VI'][$s['period_no']-1] ?></td>
                <td><span class="badge bg-<?= $s['status']=='pending'?'warning':($s['status']=='accepted'?'primary':($s['status']=='completed'?'success':'danger')) ?>"><?= ucfirst($s['status']) ?></span></td>
                <td><?= $s['compensation_hours'] ? ['I','II','III','IV','V','VI'][$s['compensation_hours']-1] : '-' ?></td>
                <?php if ($is_admin || isHOD()): ?>
                <td>
                    <?php if ($s['status'] == 'pending'): ?>
                    <form method="POST" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accept" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                    </form>
                    <form method="POST" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="cancel" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                    </form>
                    <?php elseif ($s['status'] == 'accepted'): ?>
                    <form method="POST" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="complete" value="<?= $s['id'] ?>">
                        <input type="date" name="compensation_date" style="width:110px" required>
                        <select name="comp_day" style="width:50px" required>
                            <option value="1">I</option>
                            <option value="2">II</option>
                            <option value="3">III</option>
                            <option value="4">IV</option>
                            <option value="5">V</option>
                            <option value="6">VI</option>
                        </select>
                        <select name="comp_period" style="width:50px" required>
                            <option value="1">I</option>
                            <option value="2">II</option>
                            <option value="3">III</option>
                            <option value="4">IV</option>
                            <option value="5">V</option>
                            <option value="6">VI</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm">Done</button>
                    </form>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h5>Compensation History</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Substitute</th>
                <th>Original</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Leave Day</th>
                <th>Leave Period</th>
                <th>Comp. Date</th>
                <th>Comp. Period</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $comps = $conn->query("SELECT c.*, e1.name as sub_name, e2.name as orig_name, cl.name as class_name, sb.code as subject_code
                               FROM compensations c
                               JOIN employees e1 ON c.substitute_employee_id = e1.id
                               JOIN employees e2 ON c.original_employee_id = e2.id
                               JOIN classes cl ON c.class_id = cl.id
                               JOIN subjects sb ON c.subject_id = sb.id
                               ORDER BY c.compensation_date DESC");
            while ($c = $comps->fetch_assoc()): ?>
            <tr>
                <td><?php echo $c['sub_name']; ?></td>
                <td><?php echo $c['orig_name']; ?></td>
                <td><?php echo $c['class_name']; ?></td>
                <td><?php echo $c['subject_code']; ?></td>
                <td><?php echo ['I','II','III','IV','V','VI'][$c['day_of_week']-1]; ?></td>
                <td><?php echo ['I','II','III','IV','V','VI'][$c['period_no']-1]; ?></td>
                <td><?php echo $c['compensation_date']; ?></td>
                <td><?php echo ['I','II','III','IV','V','VI'][$c['compensation_period']-1]; ?></td>
                <td><span class="badge bg-<?php echo $c['status']=='completed'?'success':'danger'; ?>"><?php echo ucfirst($c['status']); ?></span></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>