<?php
$msg = '';
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();
$can_apply = !isAdmin();
$my_dept = userDeptId();
$show_all = $is_admin || isHOD() || isPrincipal() || isVicePrincipal();

if ($show_all) {
    $dept_where = isHOD() ? "AND e.department_id = $my_dept" : "";
    $employees_list = $conn->query("SELECT e.*, d.name as dept_name FROM employees e JOIN departments d ON e.department_id = d.id WHERE e.role NOT IN ('admin','super_admin','principal','vice_principal') $dept_where ORDER BY d.name, e.name");
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
        $due_date = sanitize($_POST['due_date']);
        $nature = sanitize($_POST['nature']);
        $days = intval($_POST['days']);
        $reason = sanitize($_POST['reason']);
        
        if ($due_date && $leave_date) {
            $d1 = new DateTime($leave_date);
            $d2 = new DateTime($due_date);
            $days = $d1->diff($d2)->days + 1;
        }
        
        $initial_status = isHOD() ? 'pending_principal' : 'pending_hod';
        $conn->query("INSERT INTO leave_requests (employee_id, leave_date, due_date, nature, days, reason, status) VALUES ($user_id, '$leave_date', '$due_date', '$nature', $days, '$reason', '$initial_status')");
        $msg = 'Leave applied successfully';
        
    } elseif (isset($_POST['hod_approve'])) {
        $leave_id = intval($_POST['hod_approve']);
        $leave = $conn->query("SELECT l.*, e.department_id FROM leave_requests l JOIN employees e ON l.employee_id = e.id WHERE l.id=$leave_id")->fetch_assoc();
        if ($leave && isHOD() && $leave['department_id'] == userDeptId()) {
            $conn->query("UPDATE leave_requests SET status='pending_principal', hod_approved_by=$user_id, hod_approved_at=NOW() WHERE id=$leave_id");
            $msg = 'Leave forwarded to Principal';
        }
    } elseif (isset($_POST['hod_reject'])) {
        $leave_id = intval($_POST['hod_reject']);
        $leave = $conn->query("SELECT l.*, e.department_id FROM leave_requests l JOIN employees e ON l.employee_id = e.id WHERE l.id=$leave_id")->fetch_assoc();
        if ($leave && isHOD() && $leave['department_id'] == userDeptId()) {
            $conn->query("UPDATE leave_requests SET status='rejected' WHERE id=$leave_id");
            $msg = 'Leave rejected';
        }
        
    } elseif (isset($_POST['principal_approve'])) {
        $leave_id = intval($_POST['principal_approve']);
        $leave = $conn->query("SELECT * FROM leave_requests WHERE id=$leave_id")->fetch_assoc();
        if ($leave && (isPrincipal() || isVicePrincipal())) {
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
            
            $conn->query("UPDATE leave_requests SET status='approved', principal_approved_by=$user_id, principal_approved_at=NOW() WHERE id=$leave_id");
            $msg = 'Leave approved and balance updated';
        }
    } elseif (isset($_POST['principal_reject'])) {
        $leave_id = intval($_POST['principal_reject']);
        $leave = $conn->query("SELECT * FROM leave_requests WHERE id=$leave_id")->fetch_assoc();
        if ($leave && (isPrincipal() || isVicePrincipal())) {
            $conn->query("UPDATE leave_requests SET status='rejected' WHERE id=$leave_id");
            $msg = 'Leave rejected';
        }
    }
}

$leave_where = isHOD() ? "WHERE e.department_id = $my_dept" : "";
$leaves = $conn->query("SELECT l.*, e.name as emp_name, e.emp_id, e.department_id 
                       FROM leave_requests l 
                       JOIN employees e ON l.employee_id = e.id 
                       $leave_where
                       ORDER BY l.applied_at DESC");
?>
<?php if ($msg): ?>
<div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>

<?php if ($show_all): ?>
<div class="card">
    <h5><i class="bi bi-people me-2"></i>Staff Leave Balances</h5>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="leaveBalancesTable">
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

<?php if ($can_apply): ?>
<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-calendar-check me-2" style="color:#667eea"></i>Apply for Leave</h5>
        <button type="button" class="btn btn-success" data-modal="applyLeaveModal" data-title="Apply for Leave">
            <i class="bi bi-plus-lg"></i> Apply for Leave
        </button>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="applyLeaveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply for Leave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=leave" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('applyLeaveModal')}">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Leave Nature</label>
                        <select name="nature" class="form-select" required>
                            <option value="">Select Nature</option>
                            <option value="casual">Casual Leave</option>
                            <option value="medical">Medical Leave</option>
                            <option value="onduty">On Duty</option>
                            <option value="permission">Permission</option>
                            <option value="deputation">Deputation</option>
                        </select>
                        <div class="invalid-feedback">Please select leave nature.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Leave Date</label>
                            <input type="date" name="leave_date" id="leave_date" class="form-control" required onchange="calcDays()">
                            <div class="invalid-feedback">Please select a date.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control" required onchange="calcDays()">
                            <div class="invalid-feedback">Please select a due date.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Number of Days</label>
                        <input type="number" name="days" id="days" class="form-control" placeholder="Auto-calculated" min="1" value="1" readonly style="background:#f0f0f0">
                        <div class="invalid-feedback">Please enter number of days.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="Reason" required>
                        <div class="invalid-feedback">Please enter a reason.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="apply_leave" value="1" class="btn btn-success"><i class="bi bi-send me-1"></i>Apply Leave</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <h5>Leave Requests</h5>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="leaveRequestsTable">
            <thead>
                <tr>
                    <th>Nature</th>
                    <th>Emp ID</th>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Due Date</th>
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
                    <td><?= e($l['emp_id']) ?></td>
                    <td><?= e($l['emp_name']) ?></td>
                    <td><?= e($l['leave_date']) ?></td>
                    <td><?= e($l['due_date'] ?? '-') ?></td>
                    <td><?= $l['days'] ?></td>
                    <td><?= e($l['reason']) ?></td>
                    <td>
                        <?php
                        $status_badge = 'secondary';
                        $status_label = $l['status'];
                        if ($l['status'] == 'pending_hod') {
                            $status_badge = 'warning';
                            $status_label = 'Pending HOD';
                        } elseif ($l['status'] == 'pending_principal') {
                            $status_badge = 'info';
                            $status_label = 'Pending Principal';
                        } elseif ($l['status'] == 'approved') {
                            $status_badge = 'success';
                            $status_label = 'Approved';
                        } elseif ($l['status'] == 'rejected') {
                            $status_badge = 'danger';
                            $status_label = 'Rejected';
                        }
                        ?>
                        <span class="badge bg-<?= $status_badge ?>"><?= $status_label ?></span>
                    </td>
                    <td>
                        <?php if (isHOD() && $l['status'] == 'pending_hod' && $l['department_id'] == $my_dept): ?>
                        <button type="button" class="btn btn-success btn-sm"
                            hx-post="dashboard.php?page=leave"
                            hx-vals='<?= json_encode(['hod_approve' => $l['id'], csrf_token_name() => csrf_token()]) ?>'
                            hx-target="#page-content-wrapper"
                            hx-confirm="Forward leave of <?= e($l['emp_name']) ?> to Principal?">Forward</button>
                        <button type="button" class="btn btn-danger btn-sm"
                            hx-post="dashboard.php?page=leave"
                            hx-vals='<?= json_encode(['hod_reject' => $l['id'], csrf_token_name() => csrf_token()]) ?>'
                            hx-target="#page-content-wrapper"
                            hx-confirm="Reject leave of <?= e($l['emp_name']) ?>?">Reject</button>
                        <?php elseif ((isPrincipal() || isVicePrincipal()) && $l['status'] == 'pending_principal'): ?>
                        <button type="button" class="btn btn-success btn-sm"
                            hx-post="dashboard.php?page=leave"
                            hx-vals='<?= json_encode(['principal_approve' => $l['id'], csrf_token_name() => csrf_token()]) ?>'
                            hx-target="#page-content-wrapper"
                            hx-confirm="Approve leave for <?= e($l['emp_name']) ?>?">Approve</button>
                        <button type="button" class="btn btn-danger btn-sm"
                            hx-post="dashboard.php?page=leave"
                            hx-vals='<?= json_encode(['principal_reject' => $l['id'], csrf_token_name() => csrf_token()]) ?>'
                            hx-target="#page-content-wrapper"
                            hx-confirm="Reject leave for <?= e($l['emp_name']) ?>?">Reject</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function calcDays() {
    const start = document.getElementById('leave_date');
    const end = document.getElementById('due_date');
    const daysInput = document.getElementById('days');
    if (start.value && end.value) {
        const d1 = new Date(start.value);
        const d2 = new Date(end.value);
        if (d2 >= d1) {
            const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
            daysInput.value = diff;
        } else {
            daysInput.value = 1;
        }
    }
}
</script>
