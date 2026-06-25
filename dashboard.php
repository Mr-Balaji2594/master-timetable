<?php
require 'config.php';
sendSecurityHeaders();
if (!isLoggedIn()) redirect('index.php');

$page = $_GET['page'] ?? 'dashboard';

$allowed_pages = ['departments','employees','classes','subjects','timetable','leave','substitution','workload','lesson_plan','lesson_report','bulk_upload','users','change_password','audit_log','leave_balance'];
if (!in_array($page, $allowed_pages)) $page = 'dashboard';

verify_csrf();

if (isset($_GET['logout'])) {
    audit_log('logout', "User {$_SESSION['emp_id']} logged out");
    session_destroy();
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Timetable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="brand">Master Timetable</div>
        <div class="brand-sub">College Management System</div>
        <a href="dashboard.php" class="<?= $page=='dashboard'?'active':'' ?>">
            <i class="bi bi-speedometer2 icon"></i> Dashboard
        </a>
        <?php if (isAdmin()): ?>
        <div class="sidebar-section">Administration</div>
        <a href="dashboard.php?page=departments" class="<?= $page=='departments'?'active':'' ?>">
            <i class="bi bi-building icon"></i> Departments
        </a>
        <a href="dashboard.php?page=employees" class="<?= $page=='employees'?'active':'' ?>">
            <i class="bi bi-people icon"></i> Staff
        </a>
        <a href="dashboard.php?page=classes" class="<?= $page=='classes'?'active':'' ?>">
            <i class="bi bi-mortarboard icon"></i> Classes
        </a>
        <a href="dashboard.php?page=subjects" class="<?= $page=='subjects'?'active':'' ?>">
            <i class="bi bi-book icon"></i> Subjects
        </a>
        <a href="dashboard.php?page=bulk_upload" class="<?= $page=='bulk_upload'?'active':'' ?>">
            <i class="bi bi-upload icon"></i> Bulk Upload
        </a>
        <a href="dashboard.php?page=users" class="<?= $page=='users'?'active':'' ?>">
            <i class="bi bi-person-gear icon"></i> Users
        </a>
        <a href="dashboard.php?page=leave_balance" class="<?= $page=='leave_balance'?'active':'' ?>">
            <i class="bi bi-sliders icon"></i> Leave Balance
        </a>
        <?php elseif (isHOD()): ?>
        <div class="sidebar-section">Department Management</div>
        <a href="dashboard.php?page=departments" class="<?= $page=='departments'?'active':'' ?>">
            <i class="bi bi-building icon"></i> Departments
        </a>
        <a href="dashboard.php?page=employees" class="<?= $page=='employees'?'active':'' ?>">
            <i class="bi bi-people icon"></i> Staff
        </a>
        <a href="dashboard.php?page=classes" class="<?= $page=='classes'?'active':'' ?>">
            <i class="bi bi-mortarboard icon"></i> Classes
        </a>
        <a href="dashboard.php?page=subjects" class="<?= $page=='subjects'?'active':'' ?>">
            <i class="bi bi-book icon"></i> Subjects
        </a>
        <?php endif; ?>
        <div class="sidebar-section">Operations</div>
        <a href="dashboard.php?page=timetable" class="<?= $page=='timetable'?'active':'' ?>">
            <i class="bi bi-calendar-week icon"></i> Timetable
        </a>
        <a href="dashboard.php?page=leave" class="<?= $page=='leave'?'active':'' ?>">
            <i class="bi bi-file-text icon"></i> Leave
        </a>
        <a href="dashboard.php?page=substitution" class="<?= $page=='substitution'?'active':'' ?>">
            <i class="bi bi-arrow-repeat icon"></i> Substitution
        </a>
        <a href="dashboard.php?page=workload" class="<?= $page=='workload'?'active':'' ?>">
            <i class="bi bi-bar-chart icon"></i> Workload
        </a>
        <a href="dashboard.php?page=lesson_plan" class="<?= $page=='lesson_plan'?'active':'' ?>">
            <i class="bi bi-journal-text icon"></i> Lesson Plan
        </a>
        <a href="dashboard.php?page=lesson_report" class="<?= $page=='lesson_report'?'active':'' ?>">
            <i class="bi bi-clipboard-data icon"></i> Lesson Report
        </a>
        <a href="dashboard.php?page=change_password" class="<?= $page=='change_password'?'active':'' ?>">
            <i class="bi bi-key icon"></i> Change Password
        </a>
        <?php if (isAdmin()): ?>
        <a href="dashboard.php?page=audit_log" class="<?= $page=='audit_log'?'active':'' ?>">
            <i class="bi bi-clock-history icon"></i> Audit Log
        </a>
        <?php endif; ?>
        <a href="dashboard.php?logout=1" class="logout-link">
            <i class="bi bi-box-arrow-right icon"></i> Logout
        </a>
    </div>

    <div class="main-content">
        <div class="navbar">
            <h4><i class="bi bi-<?= page_icon($page) ?> me-2"></i><?= ucfirst(str_replace('_', ' ', $page)) ?></h4>
            <div class="user-badges">
                <span class="text-muted me-2" style="font-size:14px"><?= e($_SESSION['name']) ?></span>
                <span class="badge" style="background:#667eea"><?= e($_SESSION['dept_name']) ?></span>
                <span class="badge" style="background:<?= isAdmin()?'#ef4444':(isHOD()?'#f59e0b':'#94a3b8') ?>"><?= ucfirst($_SESSION['role']??'staff') ?></span>
            </div>
        </div>

        <?php
        switch($page) {
            case 'departments': include 'departments.php'; break;
            case 'employees': include 'employees.php'; break;
            case 'classes': include 'classes.php'; break;
            case 'subjects': include 'subjects.php'; break;
            case 'timetable': include 'timetable.php'; break;
            case 'leave': include 'leave.php'; break;
            case 'substitution': include 'substitution.php'; break;
            case 'workload': include 'workload.php'; break;
            case 'lesson_plan': include 'lesson_plan.php'; break;
            case 'lesson_report': include 'lesson_report.php'; break;
            case 'bulk_upload': include 'bulk_upload.php'; break;
            case 'users': include 'users.php'; break;
            case 'change_password': include 'change_password.php'; break;
            case 'audit_log': include 'audit_log.php'; break;
            case 'leave_balance': include 'leave_balance.php'; break;
            default: include 'home.php';
        }
        ?>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.querySelector('.sidebar-overlay').classList.toggle('show');
    }
    document.querySelectorAll('.sidebar a').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('open');
                document.querySelector('.sidebar-overlay').classList.remove('show');
            }
        });
    });
    </script>
</body>
</html>
<?php
function page_icon($p) {
    $icons = [
        'dashboard'=>'house-door','departments'=>'building','employees'=>'people',
        'classes'=>'mortarboard','subjects'=>'book',        'timetable'=>'calendar-week',
        'leave'=>'file-text','substitution'=>'arrow-repeat','workload'=>'bar-chart',
        'lesson_plan'=>'journal-text','lesson_report'=>'clipboard-data',
        'bulk_upload'=>'upload','users'=>'person-gear',
        'change_password'=>'key','audit_log'=>'clock-history',
        'leave_balance'=>'sliders'
    ];
    return $icons[$p] ?? 'circle';
}
?>
