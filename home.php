<?php
$user_id = $_SESSION['user_id'];
$my_dept = userDeptId();

if (isAdmin() || isPrincipal() || isVicePrincipal()):
    $stats = [
        'departments' => $conn->query("SELECT COUNT(*) as c FROM departments")->fetch_assoc()['c'],
        'employees' => $conn->query("SELECT COUNT(*) as c FROM employees WHERE is_active=1")->fetch_assoc()['c'],
        'classes' => $conn->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'],
        'subjects' => $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'],
        'pending_leave' => $conn->query("SELECT COUNT(*) as c FROM leave_requests WHERE status IN ('pending_hod','pending_principal')")->fetch_assoc()['c'],
        'pending_sub' => $conn->query("SELECT COUNT(*) as c FROM substitution_duties WHERE status='pending'")->fetch_assoc()['c']
    ];
?>
<div class="row g-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-icon"><i class="bi bi-building"></i></div>
            <h2><?= $stats['departments'] ?></h2>
            <p>Departments</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
            <div class="stats-icon"><i class="bi bi-people"></i></div>
            <h2><?= $stats['employees'] ?></h2>
            <p>Active Staff</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
            <div class="stats-icon"><i class="bi bi-mortarboard"></i></div>
            <h2><?= $stats['classes'] ?></h2>
            <p>Classes</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
            <div class="stats-icon"><i class="bi bi-book"></i></div>
            <h2><?= $stats['subjects'] ?></h2>
            <p>Subjects</p>
        </div>
    </div>
</div>
<div class="row g-4 mt-2">
    <div class="col-md-6">
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bi bi-file-text me-2" style="color:#667eea"></i>Pending Leave Requests</h5>
                    <p class="text-muted mb-0"><?= $stats['pending_leave'] ?> request<?= $stats['pending_leave'] != 1 ? 's' : '' ?> awaiting approval</p>
                </div>
                <span class="stats-icon" style="font-size:36px;color:#f59e0b"><?= $stats['pending_leave'] ?></span>
            </div>
            <a href="dashboard.php?page=leave" class="btn btn-primary btn-sm mt-3">View All <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bi bi-arrow-repeat me-2" style="color:#10b981"></i>Pending Substitutions</h5>
                    <p class="text-muted mb-0"><?= $stats['pending_sub'] ?> dut<?= $stats['pending_sub'] != 1 ? 'ies' : 'y' ?> awaiting action</p>
                </div>
                <span class="stats-icon" style="font-size:36px;color:#f59e0b"><?= $stats['pending_sub'] ?></span>
            </div>
            <a href="dashboard.php?page=substitution" class="btn btn-primary btn-sm mt-3">View All <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>

<?php elseif (isHOD()):
    $dept_stats = [
        'employees' => $conn->query("SELECT COUNT(*) as c FROM employees WHERE is_active=1 AND department_id=$my_dept")->fetch_assoc()['c'],
        'classes' => $conn->query("SELECT COUNT(*) as c FROM classes WHERE department_id=$my_dept")->fetch_assoc()['c'],
        'subjects' => $conn->query("SELECT COUNT(*) as c FROM subjects WHERE department_id=$my_dept")->fetch_assoc()['c'],
        'pending_leave' => $conn->query("SELECT COUNT(*) as c FROM leave_requests l JOIN employees e ON l.employee_id=e.id WHERE l.status IN ('pending_hod','pending_principal') AND e.department_id=$my_dept")->fetch_assoc()['c'],
        'pending_sub' => $conn->query("SELECT COUNT(*) as c FROM substitution_duties sub JOIN employees o ON sub.original_employee_id=o.id WHERE sub.status='pending' AND o.department_id=$my_dept")->fetch_assoc()['c']
    ];
?>
<div class="row g-4">
    <div class="col-md-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
            <div class="stats-icon"><i class="bi bi-people"></i></div>
            <h2><?= $dept_stats['employees'] ?></h2>
            <p>Department Staff</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
            <div class="stats-icon"><i class="bi bi-mortarboard"></i></div>
            <h2><?= $dept_stats['classes'] ?></h2>
            <p>Department Classes</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
            <div class="stats-icon"><i class="bi bi-book"></i></div>
            <h2><?= $dept_stats['subjects'] ?></h2>
            <p>Department Subjects</p>
        </div>
    </div>
</div>
<div class="row g-4 mt-2">
    <div class="col-md-6">
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bi bi-file-text me-2" style="color:#667eea"></i>Pending Leave</h5>
                    <p class="text-muted mb-0"><?= $dept_stats['pending_leave'] ?> request<?= $dept_stats['pending_leave'] != 1 ? 's' : '' ?> in your dept</p>
                </div>
                <span class="stats-icon" style="font-size:36px;color:#f59e0b"><?= $dept_stats['pending_leave'] ?></span>
            </div>
            <a href="dashboard.php?page=leave" class="btn btn-primary btn-sm mt-3">View <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bi bi-arrow-repeat me-2" style="color:#10b981"></i>Pending Substitutions</h5>
                    <p class="text-muted mb-0"><?= $dept_stats['pending_sub'] ?> dut<?= $dept_stats['pending_sub'] != 1 ? 'ies' : 'y' ?> in your dept</p>
                </div>
                <span class="stats-icon" style="font-size:36px;color:#f59e0b"><?= $dept_stats['pending_sub'] ?></span>
            </div>
            <a href="dashboard.php?page=substitution" class="btn btn-primary btn-sm mt-3">View <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>

<?php else:
    $my_leave = $conn->query("SELECT status, COUNT(*) as c FROM leave_requests WHERE employee_id=$user_id GROUP BY status");
    $leave_stats = ['pending_hod'=>0, 'pending_principal'=>0, 'approved'=>0, 'rejected'=>0];
    while ($r = $my_leave->fetch_assoc()) $leave_stats[$r['status']] = $r['c'];

    $my_subs = $conn->query("SELECT COUNT(*) as c FROM substitution_duties WHERE substitute_employee_id=$user_id AND status='pending'")->fetch_assoc()['c'];

    $employee = $conn->query("SELECT * FROM employees WHERE id=$user_id")->fetch_assoc();
?>
<div class="row g-4">
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #a8edea, #fed6e3);">
            <div class="stats-icon"><i class="bi bi-person-badge"></i></div>
            <h2><?= e($employee['emp_id']) ?></h2>
            <p><?= e($employee['name']) ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
            <div class="stats-icon"><i class="bi bi-file-text"></i></div>
            <h2><?= ($leave_stats['pending_hod'] ?? 0) + ($leave_stats['pending_principal'] ?? 0) ?></h2>
            <p>Pending Leaves</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
            <div class="stats-icon"><i class="bi bi-check-circle"></i></div>
            <h2><?= $leave_stats['approved'] ?></h2>
            <p>Approved Leaves</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
            <div class="stats-icon"><i class="bi bi-arrow-repeat"></i></div>
            <h2><?= $my_subs ?></h2>
            <p>Pending Substitutions</p>
        </div>
    </div>
</div>
<div class="row g-4 mt-2">
    <div class="col-md-4">
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bi bi-calendar-week me-2" style="color:#667eea"></i>My Timetable</h5>
                    <p class="text-muted mb-0">View your weekly schedule</p>
                </div>
            </div>
            <a href="dashboard.php?page=timetable" class="btn btn-primary btn-sm mt-3">View <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bi bi-file-text me-2" style="color:#f59e0b"></i>Apply Leave</h5>
                    <p class="text-muted mb-0">Casual: <?= (int)($employee['casual_leave_limit']??12) ?> | Medical: <?= (int)($employee['medical_leave_limit']??10) ?></p>
                </div>
            </div>
            <a href="dashboard.php?page=leave" class="btn btn-primary btn-sm mt-3">Apply <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1"><i class="bi bi-journal-text me-2" style="color:#10b981"></i>My Lesson Plans</h5>
                    <p class="text-muted mb-0">Manage your lesson plans</p>
                </div>
            </div>
            <a href="dashboard.php?page=lesson_plan" class="btn btn-primary btn-sm mt-3">View <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>
<?php endif; ?>