<?php
requireAdminOrHOD();
$msg = '';
$dept_scoped = isHOD() && !isPrincipal() && !isVicePrincipal();
$my_dept = $dept_scoped ? userDeptId() : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $branch_code = sanitize($_POST['branch_code']);
        $hod_id = intval($_POST['hod_id'] ?? 0);
        $conn->query("INSERT INTO departments (name, code, branch_code, hod_id) VALUES ('$name', '$code', '$branch_code', $hod_id)");
        $dept_id = $conn->insert_id;
        $staff_count = isset($_POST['staff_count']) ? (int)$_POST['staff_count'] : 0;
        for ($i = 1; $i <= $staff_count; $i++) {
            $s_name = isset($_POST['staff_name_' . $i]) ? sanitize($_POST['staff_name_' . $i]) : '';
            $s_desig = isset($_POST['staff_desig_' . $i]) ? sanitize($_POST['staff_desig_' . $i]) : '';
            if (!empty($s_name)) {
                $max_id = $conn->query("SELECT MAX(CAST(SUBSTRING(emp_id, 4) AS UNSIGNED)) as max_id FROM employees")->fetch_assoc();
                $new_num = ($max_id['max_id'] ?? 0) + 1;
                $emp_id = 'EMP' . str_pad($new_num, 4, '0', STR_PAD_LEFT);
                $pass = password_hash('123456', PASSWORD_DEFAULT);
                $conn->query("INSERT INTO employees (emp_id, department_id, name, designation, password) VALUES ('$emp_id', $dept_id, '$s_name', '$s_desig', '$pass')");
            }
        }
        $msg = 'Department added with ' . $staff_count . ' staff';
        $conn->query("UPDATE departments SET staff_count=$staff_count WHERE id=$dept_id");
    } elseif (isset($_POST['update'])) {
        $dept_id = (int)$_POST['dept_id'];
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $branch_code = sanitize($_POST['branch_code']);
        $hod_id = intval($_POST['hod_id'] ?? 0);
        $conn->query("UPDATE departments SET name='$name', code='$code', branch_code='$branch_code', hod_id=$hod_id WHERE id=$dept_id");
        $msg = 'Department updated';
    } elseif (isset($_POST['delete'])) {
        $conn->query("DELETE FROM departments WHERE id=" . intval($_POST['delete']));
        $msg = 'Department deleted';
    }
}

$update_depts = $conn->query("SELECT department_id, COUNT(*) as cnt FROM employees GROUP BY department_id");
while ($ud = $update_depts->fetch_assoc()) {
    $conn->query("UPDATE departments SET staff_count=" . (int)$ud['cnt'] . " WHERE id=" . $ud['department_id']);
}

$dept_where = $dept_scoped ? "WHERE d.id=$my_dept" : "";
$depts = $conn->query("SELECT d.*, e.name as hod_name FROM departments d LEFT JOIN employees e ON d.hod_id = e.id $dept_where ORDER BY d.name");
$hods = $conn->query("SELECT id, emp_id, name FROM employees WHERE is_active=1 AND role='hod' ORDER BY name");
?>
<?php if ($msg): ?>
<div class="alert alert-success alert-auto"><?= $msg ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-building me-2" style="color:#667eea"></i>Departments</h5>
        <?php if (isAdmin()): ?>
        <button type="button" class="btn btn-success" data-modal="addDeptModal" data-title="Add New Department">
            <i class="bi bi-plus-lg"></i> Add Department
        </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="deptsTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Branch Code</th>
                    <th>HOD</th>
                    <th>Staff</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($d = $depts->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= e($d['name']) ?></strong></td>
                    <td><code><?= e($d['code']) ?></code></td>
                    <td><code><?= e($d['branch_code'] ?? '') ?></code></td>
                    <td><?= e($d['hod_name'] ?? '-') ?></td>
                    <td><span
                            class="badge bg-info"><?= (int)$conn->query("SELECT COUNT(*) as c FROM employees WHERE department_id=" . $d['id'])->fetch_assoc()['c'] ?></span>
                    </td>
                    <td>
                        <div class="btn-action-group">
                            <?php if (isAdmin()): ?>
                            <button type="button" class="btn btn-primary btn-action" data-modal="editDeptModal"
                                data-title="Edit Department - <?= e($d['name']) ?>" data-dept_id="<?= $d['id'] ?>"
                                data-name="<?= e($d['name']) ?>" data-code="<?= e($d['code']) ?>"
                                data-branch_code="<?= e($d['branch_code'] ?? '') ?>"
                                data-hod_id="<?= (int)($d['hod_id'] ?? 0) ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger btn-action"
                                hx-post="dashboard.php?page=departments"
                                hx-vals='<?= json_encode(['delete' => $d['id'], csrf_token_name() => csrf_token()]) ?>'
                                hx-target="#page-content-wrapper"
                                hx-confirm="Delete department &quot;<?= e($d['name']) ?>&quot;?">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                            <?php else: ?>
                            <span class="text-muted">View only</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=departments" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('addDeptModal')}">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="name" class="form-control" required
                            placeholder="e.g. Computer Science">
                        <div class="invalid-feedback">Please enter department name.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" required placeholder="e.g. CS">
                            <div class="invalid-feedback">Please enter code.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch Code</label>
                            <input type="text" name="branch_code" class="form-control" placeholder="e.g. 17">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Head of Department (HOD)</label>
                        <select name="hod_id" class="form-select" placeholder="Select HOD">
                            <option value="">Select HOD</option>
                            <?php
                            $hods->data_seek(0);
                            while ($e = $hods->fetch_assoc()): ?>
                            <option value="<?= $e['id'] ?>"><?= e($e['name']) ?> (<?= e($e['emp_id']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" value="1" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add
                        Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDeptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=departments" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('editDeptModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="dept_id" data-fill="dept_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Department Name</label>
                        <input type="text" name="name" class="form-control" data-fill="name" required>
                        <div class="invalid-feedback">Please enter department name.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" data-fill="code" required>
                            <div class="invalid-feedback">Please enter code.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch Code</label>
                            <input type="text" name="branch_code" class="form-control" data-fill="branch_code"
                                placeholder="e.g. 17">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Head of Department (HOD)</label>
                        <select name="hod_id" class="form-select" data-fill="hod_id" placeholder="Select HOD">
                            <option value="">Select HOD</option>
                            <?php
                            $all_hods = $conn->query("SELECT id, emp_id, name FROM employees WHERE is_active=1 AND role='hod' ORDER BY name");
                            while ($e = $all_hods->fetch_assoc()): ?>
                            <option value="<?= $e['id'] ?>"><?= e($e['name']) ?> (<?= e($e['emp_id']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" value="1" class="btn btn-primary"><i
                            class="bi bi-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>