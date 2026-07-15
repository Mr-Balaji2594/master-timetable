<?php
require 'config.php';
sendSecurityHeaders();
if (!isLoggedIn()) redirect('index.php');

$page = $_GET['page'] ?? 'dashboard';

$allowed_pages = ['departments','employees','classes','subjects','timetable','leave','substitution','workload','lesson_plan','lesson_report','bulk_upload','change_password','audit_log','leave_balance','staff_subjects','common_paper_allocation'];
if (!in_array($page, $allowed_pages)) $page = 'dashboard';

verify_csrf();

if (isset($_GET['logout'])) {
    audit_log('logout', "User {$_SESSION['emp_id']} logged out");
    session_destroy();
    redirect('index.php');
}

if (!empty($_SERVER['HTTP_HX_REQUEST'])) {
    $page = $_GET['page'] ?? 'dashboard';
    if (!in_array($page, $allowed_pages)) $page = 'dashboard';
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
        case 'change_password': include 'change_password.php'; break;
        case 'audit_log': include 'audit_log.php'; break;
        case 'leave_balance': include 'leave_balance.php'; break;
        case 'staff_subjects': include 'staff_subjects.php'; break;
        case 'common_paper_allocation': include 'common_paper_allocation.php'; break;
        default: include 'home.php';
    }
    exit;
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.13.4/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
    .dataTables_filter input { background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat 12px center !important; padding-left: 36px !important; }
    </style>
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
        <?php if (isAdmin() || isPrincipal() || isVicePrincipal()): ?>
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
        <a href="dashboard.php?page=staff_subjects" class="<?= $page=='staff_subjects'?'active':'' ?>">
            <i class="bi bi-diagram-3 icon"></i> Staff Subjects
        </a>
        <?php if (isAdmin()): ?>
        <a href="dashboard.php?page=bulk_upload" class="<?= $page=='bulk_upload'?'active':'' ?>">
            <i class="bi bi-upload icon"></i> Bulk Upload
        </a>
        <a href="dashboard.php?page=leave_balance" class="<?= $page=='leave_balance'?'active':'' ?>">
            <i class="bi bi-sliders icon"></i> Leave Balance
        </a>
        <?php endif; ?>
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
        <a href="dashboard.php?page=staff_subjects" class="<?= $page=='staff_subjects'?'active':'' ?>">
            <i class="bi bi-diagram-3 icon"></i> Staff Subjects
        </a>
        <?php else: ?>
        <div class="sidebar-section">My Subjects</div>
        <a href="dashboard.php?page=staff_subjects" class="<?= $page=='staff_subjects'?'active':'' ?>">
            <i class="bi bi-diagram-3 icon"></i> My Subjects
        </a>
        <?php endif; ?>
        <div class="sidebar-section">Operations</div>
        <a href="dashboard.php?page=timetable" class="<?= $page=='timetable'?'active':'' ?>">
            <i class="bi bi-calendar-week icon"></i> Timetable
        </a>
        <a href="dashboard.php?page=common_paper_allocation" class="<?= $page=='common_paper_allocation'?'active':'' ?>">
            <i class="bi bi-globe2 icon"></i> Common Papers
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
                <span class="badge" style="background:<?= isSuperAdmin()?'#dc2626':(isAdmin()?'#ef4444':(isPrincipal()?'#8b5cf6':(isVicePrincipal()?'#a78bfa':(isHOD()?'#f59e0b':'#94a3b8')))) ?>"><?= ucwords(str_replace('_', ' ', $_SESSION['role']??'staff')) ?></span>
            </div>
        </div>

        <div id="page-content-wrapper">
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
            case 'change_password': include 'change_password.php'; break;
            case 'audit_log': include 'audit_log.php'; break;
            case 'leave_balance': include 'leave_balance.php'; break;
            case 'staff_subjects': include 'staff_subjects.php'; break;
            case 'common_paper_allocation': include 'common_paper_allocation.php'; break;
            default: include 'home.php';
        }
        ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.13.4/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.13.4/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.13.4/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.13.4/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.13.4/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>
    <script src="js/app.js"></script>
    <script>
    window.csrfToken = '<?= csrf_token() ?>';
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
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            document.querySelectorAll('.alert-success').forEach(function(el) {
                el.classList.add('alert-auto');
            });
        }, 100);
    });
    // ── HTMX modal cleanup: hide modals before request, remove orphaned backdrops ──
    document.addEventListener('htmx:beforeRequest', function() {
        try {
            document.querySelectorAll('.modal.show').forEach(function(el) {
                if (typeof bootstrap !== 'undefined') {
                    var m = bootstrap.Modal.getInstance(el);
                    if (m) m.hide();
                }
            });
        } catch(e) {}
    });
    document.addEventListener('htmx:beforeSwap', function() {
        document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });
    document.addEventListener('htmx:afterSettle', function() {
        document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        if (typeof initDataTables === 'function') initDataTables();
        if (typeof initSelect2 === 'function') initSelect2();
        document.querySelectorAll('.alert-auto').forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() { alert.remove(); }, 500);
            }, 4000);
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
        'bulk_upload'=>'upload','staff_subjects'=>'diagram-3',
        'change_password'=>'key','audit_log'=>'clock-history',
        'leave_balance'=>'sliders','common_paper_allocation'=>'globe2'
    ];
    return $icons[$p] ?? 'circle';
}
?>
