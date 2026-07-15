<?php
requireAdmin();
$action_filter = $_GET['action'] ?? '';
$user_filter = $_GET['user_id'] ?? 0;

$where = [];
if ($action_filter) $where[] = "action = '" . $conn->real_escape_string($action_filter) . "'";
if ($user_filter) $where[] = "user_id = " . intval($user_filter);
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$logs = $conn->query("SELECT * FROM audit_logs $where_sql ORDER BY created_at DESC LIMIT 500");

$actions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
?>
<div class="card mb-3">
    <h5><i class="bi bi-clock-history me-2" style="color:#667eea"></i>Audit Log</h5>
    <form method="GET" class="row g-3" hx-get="dashboard.php" hx-target="#page-content-wrapper" hx-push-url="true">
        <input type="hidden" name="page" value="audit_log">
        <div class="col-md-4">
            <select name="action" class="form-select">
                <option value="">All Actions</option>
                <?php while ($a = $actions->fetch_assoc()): ?>
                    <option value="<?= e($a['action']) ?>" <?= $action_filter==$a['action']?'selected':'' ?>><?= e($a['action']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive-dt">
        <table class="table table-dt" id="auditLogTable" data-sort="false">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td style="white-space:nowrap"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></td>
                    <td><code><?= e($log['emp_id']) ?></code></td>
                    <td><span class="badge" style="background:#667eea"><?= e($log['action']) ?></span></td>
                    <td><?= e($log['details']) ?: '-' ?></td>
                    <td><code><?= e($log['ip_address']) ?></code></td>
                </tr>
                <?php endwhile; ?>
                <?php if ($logs->num_rows == 0): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No log entries found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
