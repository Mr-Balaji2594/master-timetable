<?php
$msg = '';
$is_hx = !empty($_SERVER['HTTP_HX_REQUEST']);
$my_dept = userDeptId();
$my_user_id = $_SESSION['user_id'] ?? 0;
$is_admin_or_management = isAdmin() || isPrincipal() || isVicePrincipal();
$is_hod = isHOD();
$is_staff = !$is_admin_or_management && !$is_hod;

$emp_id_filter = $_GET['emp_id'] ?? '';
$day_filter = $_GET['day'] ?? 0;
$class_filter = $_GET['class_id'] ?? 0;
$status_filter = $_GET['status'] ?? '';

$check = $conn->query("SHOW COLUMNS FROM lesson_plans LIKE 'status'");
if ($check->num_rows === 0) {
    $conn->query("ALTER TABLE lesson_plans
                  ADD COLUMN status ENUM('pending_hod','pending_principal','approved','rejected') DEFAULT 'pending_hod' AFTER plan_date,
                  ADD COLUMN hod_approved_by INT NULL AFTER status,
                  ADD COLUMN hod_approved_at TIMESTAMP NULL AFTER hod_approved_by,
                  ADD COLUMN principal_approved_by INT NULL AFTER hod_approved_at,
                  ADD COLUMN principal_approved_at TIMESTAMP NULL AFTER principal_approved_by");
} else {
    $chk = $conn->query("SHOW COLUMNS FROM lesson_plans LIKE 'principal_approved_by'");
    if ($chk->num_rows === 0) {
        $conn->query("ALTER TABLE lesson_plans
                      ADD COLUMN principal_approved_by INT NULL AFTER hod_approved_at,
                      ADD COLUMN principal_approved_at TIMESTAMP NULL AFTER principal_approved_by");
        $conn->query("ALTER TABLE lesson_plans MODIFY COLUMN status ENUM('pending_hod','pending_principal','approved','rejected') DEFAULT 'pending_hod'");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_plan'])) {
        $target_emp_id = intval($_POST['employee_id']);
        if ($is_staff && $target_emp_id != $my_user_id) {
            $msg = 'You can only add lesson plans for yourself.';
        } else {
            $subject_id = intval($_POST['subject_id']);
            $class_id = intval($_POST['class_id']);
            $day = intval($_POST['day']);
            $period = intval($_POST['period']);
            $topic = sanitize($_POST['topic']);
            $description = sanitize($_POST['description']);
            $unit = sanitize($_POST['unit']);
            $plan_date = sanitize($_POST['plan_date']);
            $semester = sanitize($_POST['semester']);
            $status = $is_staff ? "'pending_hod'" : ($is_hod ? "'pending_principal'" : "'approved'");

            $conn->query("INSERT INTO lesson_plans (employee_id, class_id, subject_id, day, period, semester, topic, description, unit, plan_date, status)
                          VALUES ($target_emp_id, $class_id, $subject_id, $day, $period, '$semester', '$topic', '$description', '$unit', '$plan_date', $status)");
            $msg = 'Lesson plan added successfully';
        }
    } elseif (isset($_POST['edit_plan'])) {
        $plan_id = intval($_POST['edit_plan']);
        $check = $conn->query("SELECT employee_id FROM lesson_plans WHERE id=$plan_id");
        $row = $check->fetch_assoc();
        if (!$row) {
            $msg = 'Lesson plan not found.';
        } elseif ($is_staff && $row['employee_id'] != $my_user_id) {
            $msg = 'You can only edit your own lesson plans.';
        } else {
            $subject_id = intval($_POST['subject_id']);
            $class_id = intval($_POST['class_id']);
            $day = intval($_POST['day']);
            $period = intval($_POST['period']);
            $topic = sanitize($_POST['topic']);
            $description = sanitize($_POST['description']);
            $unit = sanitize($_POST['unit']);
            $plan_date = sanitize($_POST['plan_date']);
            $semester = sanitize($_POST['semester']);

            $new_status = $is_hod ? "'pending_principal'" : "'pending_hod'";
            $conn->query("UPDATE lesson_plans SET
                          class_id=$class_id, subject_id=$subject_id, day=$day, period=$period,
                          semester='$semester', topic='$topic', description='$description',
                          unit='$unit', plan_date='$plan_date', status=$new_status,
                          hod_approved_by=NULL, hod_approved_at=NULL,
                          principal_approved_by=NULL, principal_approved_at=NULL
                          WHERE id=$plan_id");
            $msg = 'Lesson plan updated and sent for approval';
        }
    } elseif (isset($_POST['hod_approve'])) {
        $plan_id = intval($_POST['hod_approve']);
        $plan = $conn->query("SELECT p.*, e.department_id FROM lesson_plans p JOIN employees e ON p.employee_id = e.id WHERE p.id=$plan_id")->fetch_assoc();
        if ($plan && $is_hod && $plan['department_id'] == $my_dept) {
            $conn->query("UPDATE lesson_plans SET status='pending_principal', hod_approved_by=$my_user_id, hod_approved_at=NOW() WHERE id=$plan_id");
            $msg = 'Lesson plan forwarded to Principal';
        }
    } elseif (isset($_POST['hod_reject'])) {
        $plan_id = intval($_POST['hod_reject']);
        $plan = $conn->query("SELECT p.*, e.department_id FROM lesson_plans p JOIN employees e ON p.employee_id = e.id WHERE p.id=$plan_id")->fetch_assoc();
        if ($plan && $is_hod && $plan['department_id'] == $my_dept) {
            $conn->query("UPDATE lesson_plans SET status='rejected' WHERE id=$plan_id");
            $msg = 'Lesson plan rejected';
        }
    } elseif (isset($_POST['principal_approve'])) {
        $plan_id = intval($_POST['principal_approve']);
        $plan = $conn->query("SELECT * FROM lesson_plans WHERE id=$plan_id")->fetch_assoc();
        if ($plan && (isPrincipal() || isVicePrincipal())) {
            $conn->query("UPDATE lesson_plans SET status='approved', principal_approved_by=$my_user_id, principal_approved_at=NOW() WHERE id=$plan_id");
            $msg = 'Lesson plan approved';
        }
    } elseif (isset($_POST['principal_reject'])) {
        $plan_id = intval($_POST['principal_reject']);
        $plan = $conn->query("SELECT * FROM lesson_plans WHERE id=$plan_id")->fetch_assoc();
        if ($plan && (isPrincipal() || isVicePrincipal())) {
            $conn->query("UPDATE lesson_plans SET status='rejected' WHERE id=$plan_id");
            $msg = 'Lesson plan rejected';
        }
    }


}

if ($is_admin_or_management) {
    $emp_where = "";
    $class_where = "";
    $dept_filter = "";
    $scope_where = "";
} elseif ($is_hod) {
    $emp_where = "AND department_id = $my_dept";
    $class_where = "WHERE c.department_id = $my_dept";
    $dept_filter = "WHERE department_id = $my_dept";
    $scope_where = "AND e.department_id = $my_dept";
} else {
    $emp_where = "AND id = $my_user_id";
    $class_where = "";
    $dept_filter = "";
    $scope_where = "AND p.employee_id = $my_user_id";
}

$employees = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_where ORDER BY name");

$where = [];
if ($is_staff) {
    $where[] = "p.employee_id = $my_user_id";
} elseif ($is_hod) {
    $where[] = "e.department_id = $my_dept";
}
if ($emp_id_filter) $where[] = "p.employee_id = " . intval($emp_id_filter);
if ($day_filter) $where[] = "p.day = " . intval($day_filter);
if ($class_filter) $where[] = "p.class_id = " . intval($class_filter);
if ($status_filter) $where[] = "p.status = '" . sanitize($status_filter) . "'";
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$plans_result = $conn->query("SELECT p.*, e.name as emp_name, e.emp_id, c.name as class_name, d.name as dept_name, s.code as subject_code, s.name as subject_name
                              FROM lesson_plans p
                              JOIN employees e ON p.employee_id = e.id
                              JOIN classes c ON p.class_id = c.id
                              JOIN departments d ON c.department_id = d.id
                              JOIN subjects s ON p.subject_id = s.id
                              $where_sql
                              ORDER BY p.created_at DESC");
$plans = [];
if ($plans_result) {
    while ($row = $plans_result->fetch_assoc()) {
        $plans[] = $row;
    }
}
?>
<style>
@media print {
    body * {
        visibility: hidden;
    }

    #print-area,
    #print-area * {
        visibility: visible;
    }

    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    #print-area .btn {
        display: none;
    }

    .sidebar,
    .navbar,
    .card:not(#print-area) {
        display: none !important;
    }
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
<div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header-tabs">
        <h5><i class="bi bi-journal-text me-2" style="color:#667eea"></i>Lesson Plans</h5>
        <button type="button" class="btn btn-success" data-modal="addLessonPlanModal" data-title="Add Lesson Plan">
            <i class="bi bi-plus-lg"></i> Add Lesson Plan
        </button>
    </div>
</div>

<div id="lesson-plans-content">
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3" hx-get="dashboard.php" hx-target="#lesson-plans-content"
                hx-push-url="true" hx-trigger="submit">
                <input type="hidden" name="page" value="lesson_plan">
                <div class="col-md-2">
                    <select name="emp_id" class="form-select">
                        <option value="">All Employees</option>
                        <?php
                        if ($employees) {
                            $employees->data_seek(0);
                            while ($e = $employees->fetch_assoc()): ?>
                        <option value="<?= $e['id'] ?>" <?= $emp_id_filter==$e['id']?'selected':'' ?>>
                            <?= e($e['name']) ?> (<?= $e['emp_id'] ?>)</option>
                        <?php endwhile;
                        } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="day" class="form-select">
                        <option value="">All Days</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?= $i ?>" <?= $day_filter==$i?'selected':'' ?>>
                            <?= ['I','II','III','IV','V','VI'][$i-1] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="class_id" class="form-select">
                        <option value="">All Classes</option>
                        <?php
                        $all_classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id ORDER BY d.name, c.name");
                        while ($c = $all_classes->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= $class_filter==$c['id']?'selected':'' ?>>
                            <?= e($c['name']) ?> - <?= e($c['dept_name']) ?> - <?= e($c['year']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending_hod" <?= $status_filter=='pending_hod'?'selected':'' ?>>Pending HOD
                        </option>
                        <option value="pending_principal" <?= $status_filter=='pending_principal'?'selected':'' ?>>
                            Pending Principal</option>
                        <option value="approved" <?= $status_filter=='approved'?'selected':'' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter=='rejected'?'selected':'' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-success" onclick="printLessonPlan()"><i
                            class="bi bi-printer"></i> Print</button>
                    <a href="export_pdf.php?type=lesson_plan&emp_id=<?= $emp_id_filter ?>&day=<?= $day_filter ?>&class_id=<?= $class_filter ?>&status=<?= $status_filter ?>"
                        class="btn btn-danger" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card" id="print-area">
        <div class="card-header-tabs">
            <h5><i class="bi bi-table me-2" style="color:#667eea"></i>Lesson Plan Details</h5>
        </div>
        <div class="table-responsive-dt">
            <table class="table table-dt" id="lessonPlanTable">
                <thead>
                    <tr>
                        <th>Sem</th>
                        <th>Day</th>
                        <th>Period</th>
                        <?php if (!$is_staff): ?>
                        <th>Emp ID</th>
                        <th>Employee</th>
                        <?php endif; ?>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Topic</th>
                        <th>Unit</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $p): ?>
                    <?php
                    $status_badge = 'secondary';
                    $status_label = $p['status'] ?? 'pending_hod';
                    if ($p['status'] == 'pending_hod') {
                        $status_badge = 'warning';
                        $status_label = 'Pending HOD';
                    } elseif ($p['status'] == 'pending_principal') {
                        $status_badge = 'info';
                        $status_label = 'Pending Principal';
                    } elseif ($p['status'] == 'approved') {
                        $status_badge = 'success';
                        $status_label = 'Approved';
                    } elseif ($p['status'] == 'rejected') {
                        $status_badge = 'danger';
                        $status_label = 'Rejected';
                    }
                    ?>
                    <tr>
                        <td><span
                                class="badge bg-<?= $p['semester']=='odd'?'info':'secondary' ?>"><?= ucfirst($p['semester'] ?? 'N/A') ?></span>
                        </td>
                        <td><?= ['I','II','III','IV','V','VI'][$p['day']-1] ?></td>
                        <td><?= $p['period'] ?></td>
                        <?php if (!$is_staff): ?>
                        <td><?= e($p['emp_id']) ?></td>
                        <td><?= e($p['emp_name']) ?></td>
                        <?php endif; ?>
                        <td><?= e($p['class_name']) ?></td>
                        <td><?= e($p['subject_code']) ?></td>
                        <td><?= e($p['topic']) ?></td>
                        <td><?= e($p['unit'] ?: '-') ?></td>
                        <td><?= $p['plan_date'] ? e($p['plan_date']) : '-' ?></td>
                        <td><span class="badge bg-<?= $status_badge ?>"><?= $status_label ?></span></td>
                        <td>
                            <div class="btn-action-group">
                                <?php if ($is_hod && $p['status'] == 'pending_hod' && $p['employee_id'] != $my_user_id): ?>
                                <form method="POST" style="display:inline" hx-post="dashboard.php?page=lesson_plan" hx-target="#page-content-wrapper" onsubmit="return confirm(<?= e(json_encode('Forward lesson plan of '.$p['emp_name'].' to Principal?')) ?>)">
                                    <input type="hidden" name="hod_approve" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-success btn-action"><i class="bi bi-check-lg"></i> Forward</button>
                                </form>
                                <form method="POST" style="display:inline" hx-post="dashboard.php?page=lesson_plan" hx-target="#page-content-wrapper" onsubmit="return confirm(<?= e(json_encode('Reject lesson plan of '.$p['emp_name'].'?')) ?>)">
                                    <input type="hidden" name="hod_reject" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-danger btn-action"><i class="bi bi-x-lg"></i> Reject</button>
                                </form>
                                <?php elseif ((isPrincipal() || isVicePrincipal()) && $p['status'] == 'pending_principal'): ?>
                                <form method="POST" style="display:inline" hx-post="dashboard.php?page=lesson_plan" hx-target="#page-content-wrapper" onsubmit="return confirm(<?= e(json_encode('Approve lesson plan for '.$p['emp_name'].'?')) ?>)">
                                    <input type="hidden" name="principal_approve" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-success btn-action"><i class="bi bi-check-lg"></i> Approve</button>
                                </form>
                                <form method="POST" style="display:inline" hx-post="dashboard.php?page=lesson_plan" hx-target="#page-content-wrapper" onsubmit="return confirm(<?= e(json_encode('Reject lesson plan for '.$p['emp_name'].'?')) ?>)">
                                    <input type="hidden" name="principal_reject" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-danger btn-action"><i class="bi bi-x-lg"></i> Reject</button>
                                </form>
                                <?php elseif ($p['employee_id'] == $my_user_id && $p['status'] == 'pending_hod'): ?>
                                <button type="button" class="btn btn-primary btn-action"
                                    onclick="editLessonPlan(<?= $p['id'] ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Lesson Plan Modal -->
<div class="modal fade" id="addLessonPlanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Lesson Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="">Select Semester</option>
                                <option value="odd">Odd</option>
                                <option value="even">Even</option>
                            </select>
                            <div class="invalid-feedback">Please select semester.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Employee</label>
                            <?php if ($is_staff): ?>
                            <?php $me = $conn->query("SELECT id, name, emp_id FROM employees WHERE id=$my_user_id")->fetch_assoc(); ?>
                            <input type="hidden" name="employee_id" id="modalEmployeeSelect"
                                value="<?= $me['id'] ?? '' ?>">
                            <input type="text" class="form-control"
                                value="<?= $me ? e($me['name']).' ('.e($me['emp_id']).')' : '' ?>" disabled>
                            <?php else: ?>
                            <select name="employee_id" id="modalEmployeeSelect" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php
                                    if ($employees) {
                                        $employees->data_seek(0);
                                        while ($e = $employees->fetch_assoc()): ?>
                                <option value="<?= $e['id'] ?>"><?= e($e['name']) ?> (<?= $e['emp_id'] ?>)</option>
                                <?php endwhile;
                                    } ?>
                            </select>
                            <?php endif; ?>
                            <div class="invalid-feedback">Please select an employee.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Class</label>
                            <select name="class_id" id="modalClassSelect" class="form-select" required>
                                <option value="">Select Employee first</option>
                            </select>
                            <div class="invalid-feedback">Please select a class.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" id="modalSubjectSelect" class="form-select" required>
                                <option value="">Select Employee first</option>
                            </select>
                            <div class="invalid-feedback">Please select a subject.</div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Day</label>
                            <select name="day" class="form-select" required>
                                <option value="">Day</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= ['I','II','III','IV','V','VI'][$i-1] ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Please select a day.</div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Period</label>
                            <select name="period" class="form-select" required>
                                <option value="">Period</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Please select a period.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Topic</label>
                            <input type="text" name="topic" class="form-control" required
                                placeholder="e.g. Introduction to Arrays">
                            <div class="invalid-feedback">Please enter a topic.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" placeholder="Brief description"
                                rows="2"></textarea>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Unit</label>
                            <select name="unit" class="form-select">
                                <option value="">Select Unit</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="Unit <?= $i ?>">Unit <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="plan_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_plan" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add
                        Lesson Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Lesson Plan Modal -->
<div class="modal fade" id="editLessonPlanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Lesson Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="edit_plan" id="editPlanId">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" id="editSemester" class="form-select" required>
                                <option value="">Select Semester</option>
                                <option value="odd">Odd</option>
                                <option value="even">Even</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Employee</label>
                            <input type="text" id="editEmployeeName" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Class</label>
                            <select name="class_id" id="editClassSelect" class="form-select" required>
                                <option value="">Select Employee first</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" id="editSubjectSelect" class="form-select" required>
                                <option value="">Select Employee first</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Day</label>
                            <select name="day" id="editDay" class="form-select" required>
                                <option value="">Day</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= ['I','II','III','IV','V','VI'][$i-1] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Period</label>
                            <select name="period" id="editPeriod" class="form-select" required>
                                <option value="">Period</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Topic</label>
                            <input type="text" name="topic" id="editTopic" class="form-control" required
                                placeholder="e.g. Introduction to Arrays">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editDescription" class="form-control"
                                placeholder="Brief description" rows="2"></textarea>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Unit</label>
                            <select name="unit" id="editUnit" class="form-select">
                                <option value="">Select Unit</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="Unit <?= $i ?>">Unit <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="plan_date" id="editPlanDate" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Lesson
                        Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var lessonPlansData = <?= json_encode($plans) ?>;

function loadAssignedSubjects(empId, selectEl) {
    if (!empId) {
        selectEl.innerHTML = '<option value="">Select Employee first</option>';
        return Promise.resolve();
    }
    selectEl.innerHTML = '<option value="">Loading...</option>';
    return fetch('api/get_employee_subjects.php?employee_id=' + empId)
        .then(function(r) {
            return r.json();
        })
        .then(function(data) {
            var html = '<option value="">Select Subject</option>';
            data.forEach(function(s) {
                html += '<option value="' + s.id + '">' + s.code + ' - ' + s.name + '</option>';
            });
            selectEl.innerHTML = html;
        })
        .catch(function() {
            selectEl.innerHTML = '<option value="">Error loading subjects</option>';
        });
}

function loadEmployeeClasses(empId, selectEl) {
    if (!empId) {
        selectEl.innerHTML = '<option value="">Select Employee first</option>';
        return Promise.resolve();
    }
    selectEl.innerHTML = '<option value="">Loading...</option>';
    return fetch('api/get_employee_classes.php?employee_id=' + empId)
        .then(function(r) {
            return r.json();
        })
        .then(function(data) {
            var html = '<option value="">Select Class</option>';
            data.forEach(function(c) {
                var label = c.name + ' - ' + c.dept_name + ' - ' + c.year;
                if (c.rooms) {
                    label += ' - Room ' + c.rooms;
                }
                html += '<option value="' + c.id + '">' + label + '</option>';
            });
            selectEl.innerHTML = html;
        })
        .catch(function() {
            selectEl.innerHTML = '<option value="">Error loading classes</option>';
        });
}
document.getElementById('modalEmployeeSelect').addEventListener('change', function() {
    loadEmployeeClasses(this.value, document.getElementById('modalClassSelect'));
    loadAssignedSubjects(this.value, document.getElementById('modalSubjectSelect'));
});

function editLessonPlan(planId) {
    var plan = lessonPlansData.find(function(p) {
        return p.id == planId;
    });
    if (!plan) return;

    document.getElementById('editPlanId').value = plan.id;
    document.getElementById('editEmployeeName').value = plan.emp_name + ' (' + plan.emp_id + ')';

    var $classSel = document.getElementById('editClassSelect');
    var $subjSel = document.getElementById('editSubjectSelect');

    Promise.all([
        loadEmployeeClasses(plan.employee_id, $classSel),
        loadAssignedSubjects(plan.employee_id, $subjSel)
    ]).then(function() {
        var modalEl = document.getElementById('editLessonPlanModal');
        var modal = new bootstrap.Modal(modalEl);
        $(modalEl).on('shown.bs.modal', function() {
            $('#editSemester').val(plan.semester).trigger('change');
            $('#editDay').val(plan.day).trigger('change');
            $('#editPeriod').val(plan.period).trigger('change');
            $('#editUnit').val(plan.unit || '').trigger('change');
            $('#editClassSelect').val(plan.class_id).trigger('change');
            $('#editSubjectSelect').val(plan.subject_id).trigger('change');
            $('#editTopic').val(plan.topic);
            $('#editDescription').val(plan.description || '');
            $('#editPlanDate').val(plan.plan_date || '');
        });
        modal.show();
    });
}

<?php if ($is_staff || $is_hod): ?>
<?php if ($is_hod): ?>
document.getElementById('modalEmployeeSelect').value = <?= $my_user_id ?>;
<?php endif; ?>
loadEmployeeClasses(<?= $my_user_id ?>, document.getElementById('modalClassSelect'));
loadAssignedSubjects(<?= $my_user_id ?>, document.getElementById('modalSubjectSelect'));
<?php endif; ?>
</script>