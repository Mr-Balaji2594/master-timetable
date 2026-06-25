<?php
$msg = '';
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$can_approve = $is_admin || isHOD();
$my_dept = userDeptId();
$show_all = $is_admin || isHOD();

if ($show_all) {
    $dept_where = isHOD() ? "WHERE e.department_id = $my_dept" : "";
    $employees_list = $conn->query("SELECT e.*, d.name as dept_name FROM employees e JOIN departments d ON e.department_id = d.id $dept_where ORDER BY d.name, e.name");
} else {
    $employee = $conn->query("SELECT * FROM employees WHERE id=$user_id")->fetch_assoc();
    $casual_limit = $employee['casual_leave_limit'] ?? 12;
    $medical_limit = $employee['medical_leave_limit'] ?? 10;
    $onduty_limit = $employee['onduty_leave_limit'] ?? 5;
    $permission_limit = $employee['permission_limit'] ?? 5;
    $deputation_limit = $employee['deputation_limit'] ?? 5;
    $casual_availed = $employee['casual_leave_availed'] ?? 0;
    $medical_availed = $employee['medical_leave_availed'] ?? 0;
    $onduty_availed = $employee['onduty_leave_availed'] ?? 0;
    $permission_availed = $employee['permission_availed'] ?? 0;
    $deputation_availed = $employee['deputation_availed'] ?? 0;
    $casual_balance = $casual_limit - $casual_availed;
    $medical_balance = $medical_limit - $medical_availed;
    $onduty_balance = $onduty_limit - $onduty_availed;
    $permission_balance = $permission_limit - $permission_availed;
    $deputation_balance = $deputation_limit - $deputation_availed;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['apply_leave'])) {
        $leave_date = sanitize($_POST['leave_date']);
        $nature = sanitize($_POST['nature']);
        $days = intval($_POST['days']);
        $reason = sanitize($_POST['reason']);
        
        $conn->query("INSERT INTO leave_requests (employee_id, leave_date, nature, days, reason) VALUES ($user_id, '$leave_date', '$nature', $days, '$reason')");
        $msg = 'Leave applied successfully';
    } elseif (isset($_POST['approve'])) {
        $leave_id = intval($_POST['approve']);
        $leave = $conn->query("SELECT * FROM leave_requests WHERE id=$leave_id")->fetch_assoc();
        $nature = $leave['nature'];
        $days = $leave['days'];
        
        if ($nature == 'casual') {
            $conn->query("UPDATE employees SET casual_leave_availed = casual_leave_availed + $days WHERE id=" . $leave['employee_id']);
        } elseif ($nature == 'medical') {
            $conn->query("UPDATE employees SET medical_leave_availed = medical_leave_availed + $days WHERE id=" . $leave['employee_id']);
        } elseif ($nature == 'onduty') {
            $conn->query("UPDATE employees SET onduty_leave_availed = onduty_leave_availed + $days WHERE id=" . $leave['employee_id']);
        } elseif ($nature == 'permission') {
            $conn->query("UPDATE employees SET permission_availed = permission_availed + $days WHERE id=" . $leave['employee_id']);
        } elseif ($nature == 'deputation') {
            $conn->query("UPDATE employees SET deputation_availed = deputation_availed + $days WHERE id=" . $leave['employee_id']);
        }
        
        $conn->query("UPDATE leave_requests SET status='approved' WHERE id=$leave_id");
        $msg = 'Leave approved and balance updated';
    } elseif (isset($_POST['reject'])) {
        $conn->query("UPDATE leave_requests SET status='rejected' WHERE id=" . intval($_POST['reject']));
        $msg = 'Leave rejected';
    }
}

$leave_where = isHOD() ? "WHERE e.department_id = $my_dept" : "";
$leaves = $conn->query("SELECT l.*, e.name as emp_name, e.emp_id 
                       FROM leave_requests l 
                       JOIN employees e ON l.employee_id = e.id 
                       $leave_where
                       ORDER BY l.applied_at DESC");
?>
<?php if ($show_all): ?>
<div class="card">
    <h5><i class="bi bi-people me-2"></i>Staff Leave Balances</h5>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Casual</th>
                    <th>Medical</th>
                    <th>On Duty</th>
                    <th>Permission</th>
                    <th>Deputation</th>
                    <th>Remaining</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($e = $employees_list->fetch_assoc()):
                    $cl = $e['casual_leave_limit'] ?? 12;
                    $ml = $e['medical_leave_limit'] ?? 10;
                    $ol = $e['onduty_leave_limit'] ?? 5;
                    $pl = $e['permission_limit'] ?? 5;
                    $dl = $e['deputation_limit'] ?? 5;
                    $ca = $e['casual_leave_availed'] ?? 0;
                    $ma = $e['medical_leave_availed'] ?? 0;
                    $oa = $e['onduty_leave_availed'] ?? 0;
                    $pa = $e['permission_availed'] ?? 0;
                    $da = $e['deputation_availed'] ?? 0;
                    $remaining = ($cl - $ca) + ($ml - $ma) + ($ol - $oa) + ($pl - $pa) + ($dl - $da);
                ?>
                <tr>
                    <td><code><?= e($e['emp_id']) ?></code></td>
                    <td><?= e($e['name']) ?></td>
                    <td><?= e($e['dept_name']) ?></td>
                    <td><span class="badge bg-info"><?= $ca ?> / <?= $cl ?></span></td>
                    <td><span class="badge bg-danger"><?= $ma ?> / <?= $ml ?></span></td>
                    <td><span class="badge bg-success"><?= $oa ?> / <?= $ol ?></span></td>
                    <td><span class="badge bg-warning"><?= $pa ?> / <?= $pl ?></span></td>
                    <td><span class="badge" style="background:#9b59b6"><?= $da ?> / <?= $dl ?></span></td>
                    <td><span class="badge" style="background:<?= $remaining > 0 ? '#10b981' : '#ef4444' ?>"><?= $remaining ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <h5>Leave Balance</h5>
    <div class="row">
        <div class="col-md-2">
            <div class="stats-card" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                <h6>Casual Leave</h6>
                <h4><?php echo $casual_balance; ?> / <?php echo $casual_limit; ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <h6>Medical Leave</h6>
                <h4><?php echo $medical_balance; ?> / <?php echo $medical_limit; ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card" style="background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%);">
                <h6>On Duty</h6>
                <h4><?php echo $onduty_balance; ?> / <?php echo $onduty_limit; ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card" style="background: linear-gradient(135deg, #f39c12 0%, #d68910 100%);">
                <h6>Permission</h6>
                <h4><?php echo $permission_balance; ?> / <?php echo $permission_limit; ?></h4>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                <h6>Deputation</h6>
                <h4><?php echo $deputation_balance; ?> / <?php echo $deputation_limit; ?></h4>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h5>Apply for Leave</h5>
    <form method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-2">
            <select name="nature" class="form-select" required>
                <option value="">Nature</option>
                <option value="casual">Casual Leave</option>
                <option value="medical">Medical Leave</option>
                <option value="onduty">On Duty</option>
                <option value="permission">Permission</option>
                <option value="deputation">Deputation</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="leave_date" class="form-control" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="days" class="form-control" placeholder="No. of Days" min="1" value="1" required>
        </div>
        <div class="col-md-4">
            <input type="text" name="reason" class="form-control" placeholder="Reason" required>
        </div>
        <div class="col-md-2">
            <button type="submit" name="apply_leave" class="btn btn-primary">Apply Leave</button>
        </div>
    </form>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>

<div class="card">
    <h5>Leave Requests</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Nature</th>
                <th>Emp ID</th>
                <th>Employee</th>
                <th>Date</th>
                <th>Days</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($l = $leaves->fetch_assoc()): ?>
            <tr>
                <td><span class="badge bg-<?php echo $l['nature']=='casual'?'info':($l['nature']=='medical'?'danger':($l['nature']=='onduty'?'success':'warning')); ?>"><?php echo ucfirst($l['nature']); ?></span></td>
                <td><?php echo $l['emp_id']; ?></td>
                <td><?php echo $l['emp_name']; ?></td>
                <td><?php echo $l['leave_date']; ?></td>
                <td><?php echo $l['days']; ?></td>
                <td><?php echo $l['reason']; ?></td>
                <td><span class="badge bg-<?php echo $l['status']=='pending'?'warning':($l['status']=='approved'?'success':'danger'); ?>"><?php echo ucfirst($l['status']); ?></span></td>
                <td>
                    <?php if ($can_approve && $l['status'] == 'pending'): ?>
                    <form method="POST" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="approve" value="<?php echo $l['id']; ?>">
                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="POST" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="reject" value="<?php echo $l['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>