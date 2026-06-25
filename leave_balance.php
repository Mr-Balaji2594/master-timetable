<?php
requireAdmin();
$msg = '';
$edit_id = $_GET['edit'] ?? 0;
$dept_filter = $_GET['dept_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_balance'])) {
        $emp_id = intval($_POST['emp_id']);
        $casual_limit = intval($_POST['casual_limit']);
        $medical_limit = intval($_POST['medical_limit']);
        $onduty_limit = intval($_POST['onduty_limit']);
        $permission_limit = intval($_POST['permission_limit']);
        $deputation_limit = intval($_POST['deputation_limit']);
        $casual = intval($_POST['casual_availed']);
        $medical = intval($_POST['medical_availed']);
        $onduty = intval($_POST['onduty_availed']);
        $permission = intval($_POST['permission_availed']);
        $deputation = intval($_POST['deputation_availed']);
        $conn->query("UPDATE employees SET 
                      casual_leave_limit=$casual_limit, medical_leave_limit=$medical_limit,
                      onduty_leave_limit=$onduty_limit, permission_limit=$permission_limit, deputation_limit=$deputation_limit,
                      casual_leave_availed=$casual, medical_leave_availed=$medical,
                      onduty_leave_availed=$onduty, permission_availed=$permission, deputation_availed=$deputation 
                      WHERE id=$emp_id");
        $msg = 'Leave balance updated successfully';
        audit_log('leave_balance_update', "Updated leave balance for employee ID $emp_id");
    }
}

$dept_where = $dept_filter ? "WHERE e.department_id = " . intval($dept_filter) : "";
$employees = $conn->query("SELECT e.*, d.name as dept_name FROM employees e JOIN departments d ON e.department_id = d.id $dept_where ORDER BY d.name, e.name");
$depts = $conn->query("SELECT * FROM departments ORDER BY name");

$edit_emp = null;
if ($edit_id) {
    $r = $conn->query("SELECT * FROM employees WHERE id=" . intval($edit_id));
    $edit_emp = $r->fetch_assoc();
}
?>
<?php if ($msg): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card mb-3">
    <h5><i class="bi bi-sliders me-2" style="color:#667eea"></i>Leave Balance Settings</h5>
    <form method="GET" class="row g-3">
        <input type="hidden" name="page" value="leave_balance">
        <div class="col-md-3">
            <select name="dept_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Departments</option>
                <?php while ($d = $depts->fetch_assoc()): ?>
                    <option value="<?= $d['id'] ?>" <?= $dept_filter==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($edit_emp): ?>
<div class="card mb-3" style="border:2px solid #667eea">
    <h5>Edit Leave Balance — <?= e($edit_emp['name']) ?> (<?= e($edit_emp['emp_id']) ?>)</h5>
    <form method="POST" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="emp_id" value="<?= $edit_emp['id'] ?>">
        <div class="col-md-4">
            <label class="form-label">Casual Leave <span class="text-muted">(Limit / Availed)</span></label>
            <div class="input-group">
                <input type="number" name="casual_limit" class="form-control" value="<?= $edit_emp['casual_leave_limit'] ?? 12 ?>" min="0">
                <span class="input-group-text">/</span>
                <input type="number" name="casual_availed" class="form-control" value="<?= $edit_emp['casual_leave_availed'] ?? 0 ?>" min="0">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Medical Leave <span class="text-muted">(Limit / Availed)</span></label>
            <div class="input-group">
                <input type="number" name="medical_limit" class="form-control" value="<?= $edit_emp['medical_leave_limit'] ?? 10 ?>" min="0">
                <span class="input-group-text">/</span>
                <input type="number" name="medical_availed" class="form-control" value="<?= $edit_emp['medical_leave_availed'] ?? 0 ?>" min="0">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">On-Duty Leave <span class="text-muted">(Limit / Availed)</span></label>
            <div class="input-group">
                <input type="number" name="onduty_limit" class="form-control" value="<?= $edit_emp['onduty_leave_limit'] ?? 5 ?>" min="0">
                <span class="input-group-text">/</span>
                <input type="number" name="onduty_availed" class="form-control" value="<?= $edit_emp['onduty_leave_availed'] ?? 0 ?>" min="0">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Permission <span class="text-muted">(Limit / Availed)</span></label>
            <div class="input-group">
                <input type="number" name="permission_limit" class="form-control" value="<?= $edit_emp['permission_limit'] ?? 5 ?>" min="0">
                <span class="input-group-text">/</span>
                <input type="number" name="permission_availed" class="form-control" value="<?= $edit_emp['permission_availed'] ?? 0 ?>" min="0">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">Deputation <span class="text-muted">(Limit / Availed)</span></label>
            <div class="input-group">
                <input type="number" name="deputation_limit" class="form-control" value="<?= $edit_emp['deputation_limit'] ?? 5 ?>" min="0">
                <span class="input-group-text">/</span>
                <input type="number" name="deputation_availed" class="form-control" value="<?= $edit_emp['deputation_availed'] ?? 0 ?>" min="0">
            </div>
        </div>
        <div class="col-12">
            <button type="submit" name="update_balance" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update Balance</button>
            <a href="dashboard.php?page=leave_balance" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h5>Staff Leave Balances</h5>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Casual</th>
                    <th>Medical</th>
                    <th>On-Duty</th>
                    <th>Permission</th>
                    <th>Deputation</th>
                    <th>Remaining</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($e = $employees->fetch_assoc()):
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
                    <td><?= $ca ?> / <?= $cl ?></td>
                    <td><?= $ma ?> / <?= $ml ?></td>
                    <td><?= $oa ?> / <?= $ol ?></td>
                    <td><?= $pa ?> / <?= $pl ?></td>
                    <td><?= $da ?> / <?= $dl ?></td>
                    <td><span class="badge" style="background:<?= $remaining > 0 ? '#10b981' : '#ef4444' ?>"><?= $remaining ?></span></td>
                    <td>
                        <a href="dashboard.php?page=leave_balance&edit=<?= $e['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
