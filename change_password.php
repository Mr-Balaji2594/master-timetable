<?php
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $result = $conn->query("SELECT password FROM employees WHERE id=" . intval($_SESSION['user_id']));
    $row = $result->fetch_assoc();

    if (!password_verify($current, $row['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($new !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $uid = intval($_SESSION['user_id']);
        $conn->query("UPDATE employees SET password='$hash' WHERE id=$uid");
        $msg = 'Password changed successfully';
        audit_log('password_change', "User {$_SESSION['emp_id']} changed their password");
    }
}
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <h5><i class="bi bi-key me-2" style="color:#667eea"></i>Change Password</h5>
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $msg ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" hx-post="dashboard.php?page=change_password" hx-target="#page-content-wrapper">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="current_password" class="form-control" required placeholder="Enter current password" autocomplete="current-password">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters" minlength="6" autocomplete="new-password">
                    </div>
                    <small class="text-muted">At least 6 characters</small>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter new password" autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" name="change_password" value="1" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-2"></i>Update Password
                </button>
            </form>
        </div>
    </div>
</div>
