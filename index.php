<?php
define('LOGIN_SKIP_TIMEOUT', true);
require 'config.php';

if (isLoggedIn()) redirect('dashboard.php');

$error = '';
if (isset($_GET['timeout'])) $error = 'Session timed out due to inactivity. Please login again.';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_id = sanitize($_POST['emp_id']);
    $dept_id = intval($_POST['dept_id']);
    $password = $_POST['password'];

    if (!check_login_attempts($emp_id)) {
        $error = 'Too many failed attempts. Please try again after ' . LOGIN_LOCKOUT_MINUTES . ' minutes.';
    } else {
        $stmt = $conn->prepare("SELECT e.id, e.emp_id, e.name, e.password, e.role, d.id as dept_id, d.name as dept_name 
                              FROM employees e JOIN departments d ON e.department_id = d.id 
                              WHERE e.emp_id = ? AND e.department_id = ? AND e.is_active = 1");
        $stmt->bind_param("si", $emp_id, $dept_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['emp_id'] = $row['emp_id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['dept_id'] = $row['dept_id'];
                $_SESSION['dept_name'] = $row['dept_name'];
                $_SESSION['is_admin'] = ($row['role'] === 'admin');
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                record_login_attempt($emp_id, true);
                audit_log('login', "User {$row['emp_id']} logged in");
                redirect('dashboard.php');
            } else {
                record_login_attempt($emp_id, false);
                $error = 'Invalid password';
            }
        } else {
            $error = 'Invalid Employee ID or Department';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Master Timetable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <div style="font-size:48px;color:#667eea;margin-bottom:8px"><i class="bi bi-calendar-range"></i></div>
                <h3>Master Timetable</h3>
                <p class="text-muted" style="font-size:14px">College Timetable Management System</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-person-badge me-1"></i> Employee ID</label>
                    <input type="text" name="emp_id" class="form-control" required placeholder="Enter Employee ID" autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-building me-1"></i> Department</label>
                    <select name="dept_id" class="form-select" required>
                        <option value="">Select Department</option>
                        <?php
                        $depts = $conn->query("SELECT id, name FROM departments ORDER BY name");
                        while ($d = $depts->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-lock me-1"></i> Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter Password" autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>
