<?php
if (defined('CONFIG_LOADED')) return;
define('CONFIG_LOADED', true);

error_reporting(0);
ini_set('display_errors', 0);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'atasctkm_timetable');
define('SESSION_TIMEOUT', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
set_session_timeout();

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function sanitize($data) {
    global $conn;
    return trim($conn->real_escape_string($data));
}

function isLoggedIn() {
    return isset($_SESSION['emp_id']) && isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function isSuperAdmin() { return hasRole('super_admin'); }
function isAdmin() { return hasRole('admin') || isSuperAdmin(); }
function isPrincipal() { return hasRole('principal'); }
function isVicePrincipal() { return hasRole('vice_principal'); }
function isHOD() { return hasRole('hod'); }

function isAdminOrHOD() {
    return isAdmin() || isPrincipal() || isVicePrincipal() || isHOD();
}

function isManagement() {
    return isAdmin() || isPrincipal() || isVicePrincipal() || isHOD();
}

function userDeptId() { return $_SESSION['dept_id'] ?? 0; }

function requireRole($role) {
    if (!hasRole($role)) {
        if (!isLoggedIn()) redirect('index.php');
        http_response_code(403);
        die("Access denied.");
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        if (!isLoggedIn()) redirect('index.php');
        http_response_code(403);
        die("Access denied.");
    }
}
function requireSuperAdmin() {
    if (!isSuperAdmin()) {
        if (!isLoggedIn()) redirect('index.php');
        http_response_code(403);
        die("Access denied.");
    }
}
function requireAdminOrHOD() {
    if (!isAdminOrHOD()) {
        if (!isLoggedIn()) redirect('index.php');
        http_response_code(403);
        die("Access denied.");
    }
}

function sendSecurityHeaders() {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

function set_session_timeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        if (!defined('LOGIN_SKIP_TIMEOUT')) {
            header("Location: index.php?timeout=1");
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_token_name() {
    return 'csrf_token';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die("Session expired or invalid request. Please refresh and try again.");
    }
    return true;
}

function audit_log($action, $details = '') {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? 0;
    $emp_id = $_SESSION['emp_id'] ?? '';
    $action = $conn->real_escape_string($action);
    $details = $conn->real_escape_string(substr($details, 0, 500));
    $ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = $conn->real_escape_string(substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255));
    $conn->query("INSERT INTO audit_logs (user_id, emp_id, action, details, ip_address, user_agent)
                  VALUES ($user_id, '$emp_id', '$action', '$details', '$ip', '$ua')");
}

function check_login_attempts($emp_id) {
    global $conn;
    $emp_id = $conn->real_escape_string($emp_id);
    $cutoff = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_MINUTES * 60);
    $result = $conn->query("SELECT COUNT(*) as cnt FROM login_attempts 
                           WHERE emp_id='$emp_id' AND attempted_at > '$cutoff' AND success=0");
    $row = $result->fetch_assoc();
    return $row['cnt'] < MAX_LOGIN_ATTEMPTS;
}

function record_login_attempt($emp_id, $success) {
    global $conn;
    $emp_id = $conn->real_escape_string($emp_id);
    $ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
    $conn->query("INSERT INTO login_attempts (emp_id, ip_address, success)
                  VALUES ('$emp_id', '$ip', " . ($success ? 1 : 0) . ")");
}

function export_csv($filename, $headers, $data) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($data as $row) fputcsv($output, $row);
    fclose($output);
    exit;
}