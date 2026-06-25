<?php
$stats = [
    'departments' => $conn->query("SELECT COUNT(*) as c FROM departments")->fetch_assoc()['c'],
    'employees' => $conn->query("SELECT COUNT(*) as c FROM employees WHERE is_active=1")->fetch_assoc()['c'],
    'classes' => $conn->query("SELECT COUNT(*) as c FROM classes")->fetch_assoc()['c'],
    'subjects' => $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'],
    'pending_leave' => $conn->query("SELECT COUNT(*) as c FROM leave_requests WHERE status='pending'")->fetch_assoc()['c'],
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
