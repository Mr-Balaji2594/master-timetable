<?php
$msg = '';
$is_admin = isAdmin();
$can_manage = isAdmin() || isHOD() || isPrincipal() || isVicePrincipal();
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
    } elseif (isset($_POST['edit_substitution'])) {
        $sub_id = intval($_POST['substitution_id']);
        $new_substitute_id = intval($_POST['substitute_employee_id']);
        $conn->query("UPDATE substitution_duties SET substitute_employee_id=$new_substitute_id WHERE id=$sub_id");
        $msg = 'Substitution updated';
    } elseif (isset($_POST['cancel'])) {
        $conn->query("UPDATE substitution_duties SET status='cancelled' WHERE id=" . intval($_POST['cancel']));
        $msg = 'Substitution cancelled';
    }
}

$dept_scoped = isHOD() && !isPrincipal() && !isVicePrincipal();
$sub_where = $dept_scoped ? "WHERE (o.department_id = $my_dept OR s.department_id = $my_dept)" : "";
$subs = $conn->query("SELECT sub.*, 
                       o.name as original_name, o.emp_id as original_emp_id,
                       s.name as substitute_name, s.emp_id as substitute_emp_id, s.department_id as sub_dept_id,
                       c.name as class_name, sub2.name as subject_name,
                       sub2.code as subject_code
                       FROM substitution_duties sub
                       JOIN employees o ON sub.original_employee_id = o.id
                       JOIN employees s ON sub.substitute_employee_id = s.id
                       JOIN classes c ON sub.class_id = c.id
                       JOIN subjects sub2 ON sub.subject_id = sub2.id
                       $sub_where
                       ORDER BY sub.created_at DESC");

$staff_only = !$can_manage;
$emp_leave_where = "role NOT IN ('admin','super_admin','principal','vice_principal')";
if ($staff_only) {
    $emp_leave_where .= " AND id = $user_id";
} elseif ($dept_scoped) {
    $emp_leave_where .= " AND department_id = $my_dept";
}
$emps_leave = $conn->query("SELECT * FROM employees WHERE is_active=1 AND $emp_leave_where ORDER BY name");

$emps_sub = $conn->query("SELECT e.*, GROUP_CONCAT(es.subject_id) as subject_ids
                          FROM employees e
                          LEFT JOIN employee_subjects es ON e.id = es.employee_id
                          WHERE e.is_active=1 AND e.role NOT IN ('admin','super_admin','principal','vice_principal')
                          GROUP BY e.id ORDER BY e.name");

$emp_dropdown_where = $dept_scoped ? "AND department_id = $my_dept" : "";
$emp_dropdown_where .= " AND role NOT IN ('admin','super_admin','principal','vice_principal')";
$class_where = $dept_scoped ? "WHERE c.department_id = $my_dept" : "";
$classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id $class_where ORDER BY d.name");
$subj_where = $dept_scoped ? "WHERE department_id = $my_dept" : "";

$depts = $conn->query("SELECT * FROM departments ORDER BY name");
?>
<?php if ($msg): ?>
<div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-arrow-left-right me-2" style="color:#667eea"></i>Substitution Duties</h5>
        <button type="button" class="btn btn-success" data-modal="assignSubstitutionModal" data-title="Assign Substitution Duty">
            <i class="bi bi-plus-lg"></i> Assign
        </button>
    </div>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="substitutionTable">
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
                    <?php if ($can_manage): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($s = $subs->fetch_assoc()): ?>
                <tr>
                    <td><?= $s['leave_date'] ?></td>
                    <td><?= e($s['original_name']) ?></td>
                    <td><?= e($s['substitute_name']) ?></td>
                    <td><?= e($s['class_name']) ?></td>
                    <td><?= e($s['subject_code']) ?></td>
                    <td><?= ['I','II','III','IV','V','VI'][$s['day_of_week']-1] ?></td>
                    <td><?= ['I','II','III','IV','V','VI'][$s['period_no']-1] ?></td>
                    <td><span class="badge bg-<?= $s['status']=='assigned'?'warning':($s['status']=='completed'?'success':'danger') ?>"><?= ucfirst($s['status']) ?></span></td>
                    <td><?= $s['compensation_hours'] ? ['I','II','III','IV','V','VI'][$s['compensation_hours']-1] : '-' ?></td>
                    <?php if ($can_manage): ?>
                    <td>
                        <?php if ($s['status'] == 'assigned'): ?>
                        <div class="btn-action-group">
                            <button type="button" class="btn btn-warning btn-action" data-modal="editSubstitutionModal"
                                data-title="Edit Substitute"
                                data-sub-id="<?= $s['id'] ?>"
                                data-sub-name="<?= e($s['substitute_name']) ?>"
                                data-sub-emp-id="<?= e($s['substitute_emp_id']) ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger btn-action"
                                hx-post="dashboard.php?page=substitution"
                                hx-vals='<?= json_encode(['cancel' => $s['id'], csrf_token_name() => csrf_token()]) ?>'
                                hx-target="#page-content-wrapper"
                                hx-confirm="Cancel this substitution?"><i class="bi bi-x-lg"></i> Cancel</button>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$comp_where = $dept_scoped ? "WHERE (e1.department_id = $my_dept OR e2.department_id = $my_dept)" : "";
if (!isAdmin() && !isHOD() && !isPrincipal() && !isVicePrincipal()) {
    $comp_where = "WHERE (c.substitute_employee_id = $user_id OR c.original_employee_id = $user_id)";
}

$completed_comps = $conn->query("SELECT c.*, e1.name as sub_name, e1.emp_id as sub_emp_id, d1.name as sub_dept,
                                  e2.name as orig_name, e2.emp_id as orig_emp_id, d2.name as orig_dept,
                                  cl.name as class_name, sb.code as subject_code
                               FROM compensations c
                               JOIN employees e1 ON c.substitute_employee_id = e1.id
                               JOIN departments d1 ON e1.department_id = d1.id
                               JOIN employees e2 ON c.original_employee_id = e2.id
                               JOIN departments d2 ON e2.department_id = d2.id
                               JOIN classes cl ON c.class_id = cl.id
                               JOIN subjects sb ON c.subject_id = sb.id
                               $comp_where AND c.status='completed'
                               ORDER BY c.compensation_date DESC");

$pending_comps = $conn->query("SELECT c.*, e1.name as sub_name, e1.emp_id as sub_emp_id, d1.name as sub_dept,
                                e2.name as orig_name, e2.emp_id as orig_emp_id, d2.name as orig_dept,
                                cl.name as class_name, sb.code as subject_code
                               FROM compensations c
                               JOIN employees e1 ON c.substitute_employee_id = e1.id
                               JOIN departments d1 ON e1.department_id = d1.id
                               JOIN employees e2 ON c.original_employee_id = e2.id
                               JOIN departments d2 ON e2.department_id = d2.id
                               JOIN classes cl ON c.class_id = cl.id
                               JOIN subjects sb ON c.subject_id = sb.id
                               $comp_where AND c.status='pending'
                               ORDER BY c.created_at DESC");
?>
<ul class="nav nav-tabs mb-3" id="compTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">Completed Compensation</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">Pending Compensation</button>
    </li>
</ul>
<div class="tab-content" id="compTabsContent">
    <div class="tab-pane fade show active" id="completed" role="tabpanel">
        <div class="table-responsive-dt">
            <table class="table table-dt" id="completedCompTable">
                <thead>
                    <tr>
                        <th>Substitute</th>
                        <th>Dept</th>
                        <th>Original</th>
                        <th>Dept</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Leave Day</th>
                        <th>Leave Period</th>
                        <th>Comp. Date</th>
                        <th>Comp. Period</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $completed_comps->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($c['sub_name']) ?> (<?= e($c['sub_emp_id']) ?>)</td>
                        <td><?= e($c['sub_dept']) ?></td>
                        <td><?= e($c['orig_name']) ?> (<?= e($c['orig_emp_id']) ?>)</td>
                        <td><?= e($c['orig_dept']) ?></td>
                        <td><?= e($c['class_name']) ?></td>
                        <td><?= e($c['subject_code']) ?></td>
                        <td><?= ['I','II','III','IV','V','VI'][$c['day_of_week']-1] ?></td>
                        <td><?= ['I','II','III','IV','V','VI'][$c['period_no']-1] ?></td>
                        <td><?= $c['compensation_date'] ?></td>
                        <td><?= ['I','II','III','IV','V','VI'][$c['compensation_period']-1] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="tab-pane fade" id="pending" role="tabpanel">
        <div class="table-responsive-dt">
            <table class="table table-dt" id="pendingCompTable">
                <thead>
                    <tr>
                        <th>Substitute</th>
                        <th>Dept</th>
                        <th>Original</th>
                        <th>Dept</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Leave Day</th>
                        <th>Leave Period</th>
                        <th>Comp. Date</th>
                        <th>Comp. Period</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $pending_comps->fetch_assoc()): ?>
                    <tr>
                        <td><?= e($c['sub_name']) ?> (<?= e($c['sub_emp_id']) ?>)</td>
                        <td><?= e($c['sub_dept']) ?></td>
                        <td><?= e($c['orig_name']) ?> (<?= e($c['orig_emp_id']) ?>)</td>
                        <td><?= e($c['orig_dept']) ?></td>
                        <td><?= e($c['class_name']) ?></td>
                        <td><?= e($c['subject_code']) ?></td>
                        <td><?= ['I','II','III','IV','V','VI'][$c['day_of_week']-1] ?></td>
                        <td><?= ['I','II','III','IV','V','VI'][$c['period_no']-1] ?></td>
                        <td><?= $c['compensation_date'] ?></td>
                        <td><?= ['I','II','III','IV','V','VI'][$c['compensation_period']-1] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editSubstitutionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Substitute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=substitution" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('editSubstitutionModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="substitution_id" id="edit_sub_id" data-fill="sub-id">
                <div class="modal-body">
                    <p>Current substitute: <strong id="edit_current_sub"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Substitute Employee</label>
                        <select name="substitute_employee_id" id="edit_substitute_id" class="form-select" required>
                            <option value="">Select Substitute</option>
                            <?php 
                            $emps_edit = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_dropdown_where ORDER BY name");
                            while ($e = $emps_edit->fetch_assoc()): ?>
                            <option value="<?= $e['id'] ?>"><?= e($e['name']) ?> (<?= e($e['emp_id']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <div class="invalid-feedback">Please select a substitute.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_substitution" value="1" class="btn btn-warning"><i class="bi bi-pencil me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="assignSubstitutionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Substitution Duty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=substitution" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('assignSubstitutionModal')}">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee on Leave</label>
                            <select name="original_employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php 
                                $emps_leave->data_seek(0);
                                while ($e = $emps_leave->fetch_assoc()): ?>
                                <option value="<?= $e['id'] ?>"><?= e($e['name']) ?> (<?= e($e['emp_id']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select an employee.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Substitute Employee</label>
                            <select name="substitute_employee_id" id="substitute_employee_id" class="form-select" required>
                                <option value="">Select Substitute</option>
                                <?php 
                                while ($e = $emps_sub->fetch_assoc()): ?>
                                <option value="<?= $e['id'] ?>" data-subjects="<?= $e['subject_ids'] ?>"><?= e($e['name']) ?> (<?= e($e['emp_id']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a substitute.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">Select Class</option>
                                <?php $classes->data_seek(0); while ($c = $classes->fetch_assoc()): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a class.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" id="subject_id" class="form-select" required onchange="filterSubstitute()">
                                <option value="">Select Subject</option>
                                <?php
                                $subs2 = $conn->query("SELECT * FROM subjects $subj_where ORDER BY name");
                                while ($s = $subs2->fetch_assoc()): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['code']) ?> - <?= e($s['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a subject.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Day</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="">Select Day</option>
                                <option value="1">I</option>
                                <option value="2">II</option>
                                <option value="3">III</option>
                                <option value="4">IV</option>
                                <option value="5">V</option>
                                <option value="6">VI</option>
                            </select>
                            <div class="invalid-feedback">Please select a day.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Period</label>
                            <select name="period_no" class="form-select" required>
                                <option value="">Select Period</option>
                                <option value="1">I</option>
                                <option value="2">II</option>
                                <option value="3">III</option>
                                <option value="4">IV</option>
                                <option value="5">V</option>
                                <option value="6">VI</option>
                            </select>
                            <div class="invalid-feedback">Please select a period.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Leave Date</label>
                            <input type="date" name="leave_date" class="form-control" required>
                            <div class="invalid-feedback">Please enter leave date.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_substitution" value="1" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function(e) {
    var btn = e.target && typeof e.target.closest === 'function' ? e.target.closest('[data-modal="editSubstitutionModal"]') : null;
    if (!btn) return;
    var name = btn.getAttribute('data-sub-name') || '';
    var empId = btn.getAttribute('data-sub-emp-id') || '';
    document.getElementById('edit_current_sub').textContent = name + ' (' + empId + ')';
});

function filterSubstitute() {
    var subjectId = document.getElementById('subject_id').value;
    var options = document.querySelectorAll('#substitute_employee_id option');
    options.forEach(function(opt) {
        if (opt.value === '') return;
        var subjects = opt.getAttribute('data-subjects') || '';
        if (!subjectId) {
            opt.style.display = '';
        } else if (subjects && subjects.split(',').indexOf(subjectId) !== -1) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}
</script>
