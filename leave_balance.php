<?php
requireAdmin();
$msg = '';
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
    } elseif (isset($_POST['reset_availed'])) {
        $emp_id = intval($_POST['reset_availed']);
        $conn->query("UPDATE employees SET casual_leave_availed=0, medical_leave_availed=0, onduty_leave_availed=0, permission_availed=0, deputation_availed=0 WHERE id=$emp_id");
        $msg = 'Availed leaves reset to 0 for selected staff.';
        audit_log('leave_balance_reset', "Reset availed leaves for employee ID $emp_id");
    } elseif (isset($_POST['bulk_reset_availed'])) {
        $conn->query("UPDATE employees SET casual_leave_availed=0, medical_leave_availed=0, onduty_leave_availed=0, permission_availed=0, deputation_availed=0");
        $msg = 'All availed leaves reset to 0 for new academic year.';
        audit_log('leave_balance_bulk_reset', 'Bulk reset all availed leaves');
    }
}

$dept_where = $dept_filter ? "AND e.department_id = " . intval($dept_filter) : "";
$employees = $conn->query("SELECT e.*, d.name as dept_name FROM employees e JOIN departments d ON e.department_id = d.id WHERE e.role NOT IN ('admin','super_admin','principal','vice_principal') $dept_where ORDER BY d.name, e.name");
$depts = $conn->query("SELECT * FROM departments ORDER BY name");

// Summary stats
$stats = $conn->query("SELECT 
    COUNT(*) as total_staff,
    SUM(COALESCE(casual_leave_limit,12) - COALESCE(casual_leave_availed,0)) as total_casual_rem,
    SUM(COALESCE(medical_leave_limit,10) - COALESCE(medical_leave_availed,0)) as total_medical_rem,
    SUM(COALESCE(onduty_leave_limit,5) - COALESCE(onduty_leave_availed,0)) as total_onduty_rem,
    SUM(COALESCE(permission_limit,5) - COALESCE(permission_availed,0)) as total_permission_rem,
    SUM(COALESCE(deputation_limit,5) - COALESCE(deputation_availed,0)) as total_deputation_rem
FROM employees WHERE role NOT IN ('admin','super_admin','principal','vice_principal')")->fetch_assoc();
$staff_exhausted = $conn->query("SELECT COUNT(*) as c FROM employees WHERE role NOT IN ('admin','super_admin','principal','vice_principal') AND
    (casual_leave_limit - casual_leave_availed) <= 0 AND
    (medical_leave_limit - medical_leave_availed) <= 0 AND
    (onduty_leave_limit - onduty_leave_availed) <= 0 AND
    (permission_limit - permission_availed) <= 0 AND
    (deputation_limit - deputation_availed) <= 0")->fetch_assoc()['c'];
?>
<?php if ($msg): ?>
    <div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>

<div class="row mb-3 g-3">
    <?php
        $total_rem = (int)$stats['total_casual_rem'] + (int)$stats['total_medical_rem'] + (int)$stats['total_onduty_rem'] + (int)$stats['total_permission_rem'] + (int)$stats['total_deputation_rem'];
        $all_count = (int)$stats['total_staff'];
    ?>
    <div class="col-md-3 col-6">
        <div class="card text-center py-3 h-100 border-0 shadow-sm" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff">
            <div class="small opacity-75">Total Staff</div>
            <div class="fs-3 fw-bold"><?= number_format($all_count) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-center py-3 h-100 border-0 shadow-sm" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff">
            <div class="small opacity-75">Leaves Remaining</div>
            <div class="fs-3 fw-bold"><?= number_format($total_rem) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-center py-3 h-100 border-0 shadow-sm" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff">
            <div class="small opacity-75">Exhausted Staff</div>
            <div class="fs-3 fw-bold"><?= $staff_exhausted ?></div>
            <div class="small opacity-75"><?= $all_count > 0 ? round(($staff_exhausted/$all_count)*100) : 0 ?>% of total</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card text-center py-3 h-100 border-0 shadow-sm" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff">
            <div class="small opacity-75">Bulk Reset</div>
            <button type="button" class="btn btn-sm btn-light mt-2"
                hx-post="dashboard.php?page=leave_balance"
                hx-vals='<?= json_encode(['bulk_reset_availed' => 1, csrf_token_name() => csrf_token()]) ?>'
                hx-target="#page-content-wrapper"
                hx-confirm="Reset ALL availed leaves to 0 for new academic year? This cannot be undone.">
                <i class="bi bi-arrow-counterclockwise"></i> Reset All
            </button>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header-tabs">
        <h5><i class="bi bi-funnel me-2" style="color:#667eea"></i>Filter by Department</h5>
        <form method="GET" class="d-flex align-items-center gap-2" hx-get="dashboard.php" hx-target="#page-content-wrapper" hx-push-url="true">
            <input type="hidden" name="page" value="leave_balance">
            <select name="dept_id" class="form-select form-select-sm no-select2" style="width:auto">
                <option value="">All Departments</option>
                <?php mysqli_data_seek($depts, 0); while ($d = $depts->fetch_assoc()): ?>
                    <option value="<?= $d['id'] ?>" <?= $dept_filter==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>
</div>

<div class="modal fade" id="editBalanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Leave Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=leave_balance" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('editBalanceModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="emp_id" data-fill="emp_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Casual Leave <span class="text-muted">(Limit / Availed)</span></label>
                            <div class="input-group">
                                <input type="number" name="casual_limit" class="form-control" data-fill="casual_limit" min="0" required>
                                <span class="input-group-text">/</span>
                                <input type="number" name="casual_availed" class="form-control" data-fill="casual_availed" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Medical Leave <span class="text-muted">(Limit / Availed)</span></label>
                            <div class="input-group">
                                <input type="number" name="medical_limit" class="form-control" data-fill="medical_limit" min="0" required>
                                <span class="input-group-text">/</span>
                                <input type="number" name="medical_availed" class="form-control" data-fill="medical_availed" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">On-Duty Leave <span class="text-muted">(Limit / Availed)</span></label>
                            <div class="input-group">
                                <input type="number" name="onduty_limit" class="form-control" data-fill="onduty_limit" min="0" required>
                                <span class="input-group-text">/</span>
                                <input type="number" name="onduty_availed" class="form-control" data-fill="onduty_availed" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Permission <span class="text-muted">(Limit / Availed)</span></label>
                            <div class="input-group">
                                <input type="number" name="permission_limit" class="form-control" data-fill="permission_limit" min="0" required>
                                <span class="input-group-text">/</span>
                                <input type="number" name="permission_availed" class="form-control" data-fill="permission_availed" min="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Deputation <span class="text-muted">(Limit / Availed)</span></label>
                            <div class="input-group">
                                <input type="number" name="deputation_limit" class="form-control" data-fill="deputation_limit" min="0" required>
                                <span class="input-group-text">/</span>
                                <input type="number" name="deputation_availed" class="form-control" data-fill="deputation_availed" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_balance" value="1" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-sliders me-2" style="color:#667eea"></i>Staff Leave Balances</h5>
    </div>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="leaveBalanceTable">
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
                    $cr = $cl - $ca; $mr = $ml - $ma; $or = $ol - $oa; $pr = $pl - $pa; $dr = $dl - $da;
                    $total_rem = $cr + $mr + $or + $pr + $dr;
                    $c_pct = $cl > 0 ? round(($ca / $cl) * 100) : 0;
                    $m_pct = $ml > 0 ? round(($ma / $ml) * 100) : 0;
                    $o_pct = $ol > 0 ? round(($oa / $ol) * 100) : 0;
                    $p_pct = $pl > 0 ? round(($pa / $pl) * 100) : 0;
                    $d_pct = $dl > 0 ? round(($da / $dl) * 100) : 0;
                ?>
                <tr>
                    <td><code><?= e($e['emp_id']) ?></code></td>
                    <td><?= e($e['name']) ?></td>
                    <td><?= e($e['dept_name']) ?></td>
                    <td>
                        <?= $ca ?>/<?= $cl ?>
                        <div class="progress" style="height:4px;margin-top:2px"><div class="progress-bar bg-<?= $c_pct >= 80 ? 'danger' : ($c_pct >= 50 ? 'warning' : 'success') ?>" style="width:<?= $c_pct ?>%"></div></div>
                        <small class="text-muted"><?= $cr ?> left</small>
                    </td>
                    <td>
                        <?= $ma ?>/<?= $ml ?>
                        <div class="progress" style="height:4px;margin-top:2px"><div class="progress-bar bg-<?= $m_pct >= 80 ? 'danger' : ($m_pct >= 50 ? 'warning' : 'success') ?>" style="width:<?= $m_pct ?>%"></div></div>
                        <small class="text-muted"><?= $mr ?> left</small>
                    </td>
                    <td>
                        <?= $oa ?>/<?= $ol ?>
                        <div class="progress" style="height:4px;margin-top:2px"><div class="progress-bar bg-<?= $o_pct >= 80 ? 'danger' : ($o_pct >= 50 ? 'warning' : 'success') ?>" style="width:<?= $o_pct ?>%"></div></div>
                        <small class="text-muted"><?= $or ?> left</small>
                    </td>
                    <td>
                        <?= $pa ?>/<?= $pl ?>
                        <div class="progress" style="height:4px;margin-top:2px"><div class="progress-bar bg-<?= $p_pct >= 80 ? 'danger' : ($p_pct >= 50 ? 'warning' : 'success') ?>" style="width:<?= $p_pct ?>%"></div></div>
                        <small class="text-muted"><?= $pr ?> left</small>
                    </td>
                    <td>
                        <?= $da ?>/<?= $dl ?>
                        <div class="progress" style="height:4px;margin-top:2px"><div class="progress-bar bg-<?= $d_pct >= 80 ? 'danger' : ($d_pct >= 50 ? 'warning' : 'success') ?>" style="width:<?= $d_pct ?>%"></div></div>
                        <small class="text-muted"><?= $dr ?> left</small>
                    </td>
                    <td><span class="badge" style="background:<?= $total_rem > 0 ? '#10b981' : '#ef4444' ?>"><?= $total_rem ?></span></td>
                    <td>
                        <div class="btn-action-group">
                            <button type="button" class="btn btn-primary btn-action"
                                data-modal="editBalanceModal"
                                data-title="Edit Leave Balance - <?= e($e['name']) ?>"
                                data-emp_id="<?= $e['id'] ?>"
                                data-casual_limit="<?= $cl ?>"
                                data-casual_availed="<?= $ca ?>"
                                data-medical_limit="<?= $ml ?>"
                                data-medical_availed="<?= $ma ?>"
                                data-onduty_limit="<?= $ol ?>"
                                data-onduty_availed="<?= $oa ?>"
                                data-permission_limit="<?= $pl ?>"
                                data-permission_availed="<?= $pa ?>"
                                data-deputation_limit="<?= $dl ?>"
                                data-deputation_availed="<?= $da ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-warning btn-action"
                                hx-post="dashboard.php?page=leave_balance"
                                hx-vals='<?= json_encode(['reset_availed' => $e['id'], csrf_token_name() => csrf_token()]) ?>'
                                hx-target="#page-content-wrapper"
                                hx-confirm="Reset availed leaves to 0 for <?= e($e['name']) ?>?">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
