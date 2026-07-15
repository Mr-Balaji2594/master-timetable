<?php
requireAdminOrHOD();
$msg = '';
$dept_scoped = isHOD() && !isPrincipal() && !isVicePrincipal();
$my_dept = $dept_scoped ? userDeptId() : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $emp_id = sanitize($_POST['emp_id']);
        $name = sanitize($_POST['name']);
        $dept_id = intval($_POST['department_id']);
        $designation = sanitize($_POST['designation']);
        $role = isset($_POST['role']) ? sanitize($_POST['role']) : 'staff';
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $conn->query("INSERT INTO employees (emp_id, name, department_id, designation, role, password) VALUES ('$emp_id', '$name', $dept_id, '$designation', '$role', '$password')");
        $msg = 'Staff added successfully';
    } elseif (isset($_POST['update'])) {
        $staff_id = (int)$_POST['staff_id'];
        $emp_id = sanitize($_POST['emp_id']);
        $name = sanitize($_POST['name']);
        $dept_id = intval($_POST['department_id']);
        $designation = sanitize($_POST['designation']);
        $role = isset($_POST['role']) ? sanitize($_POST['role']) : 'staff';
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $conn->query("UPDATE employees SET emp_id='$emp_id', name='$name', department_id=$dept_id, designation='$designation', role='$role', password='$password' WHERE id=$staff_id");
        } else {
            $conn->query("UPDATE employees SET emp_id='$emp_id', name='$name', department_id=$dept_id, designation='$designation', role='$role' WHERE id=$staff_id");
        }
        $msg = 'Staff updated successfully';
    } elseif (isset($_POST['delete'])) {
        $conn->query("UPDATE employees SET is_active=0 WHERE id=" . intval($_POST['delete']));
        $msg = 'Staff deactivated';
    } elseif (isset($_POST['activate'])) {
        $conn->query("UPDATE employees SET is_active=1 WHERE id=" . intval($_POST['activate']));
        $msg = 'Staff activated';
    } elseif (isset($_POST['remove'])) {
        $conn->query("DELETE FROM employees WHERE id=" . intval($_POST['remove']));
        $msg = 'Staff deleted permanently';
    } elseif (isset($_POST['reset_password'])) {
        $uid = intval($_POST['user_id']);
        $newpass = password_hash('123456', PASSWORD_DEFAULT);
        $conn->query("UPDATE employees SET password='$newpass' WHERE id=$uid");
        $msg = 'Password reset to 123456';
        audit_log('password_reset', "Admin reset password for user ID $uid");
    } elseif (isset($_POST['change_role'])) {
        $uid = intval($_POST['user_id']);
        $new_role = sanitize($_POST['new_role']);
        $cur = $conn->query("SELECT role FROM employees WHERE id=$uid")->fetch_assoc();
        if ($cur && $cur['role'] !== 'super_admin' || isSuperAdmin()) {
            $conn->query("UPDATE employees SET role='$new_role' WHERE id=$uid");
            $msg = 'Role changed to ' . ucwords(str_replace('_', ' ', $new_role));
            audit_log('role_change', "Changed user ID $uid to $new_role");
        } else {
            $msg = 'Cannot change super_admin role';
        }
    }
}

$emp_where = $dept_scoped ? "WHERE e.department_id = $my_dept" : "";
$employees = $conn->query("SELECT e.*, d.name as dept_name FROM employees e JOIN departments d ON e.department_id = d.id $emp_where ORDER BY e.name");
$dept_where = $dept_scoped ? "WHERE id = $my_dept" : "";
$depts = $conn->query("SELECT * FROM departments $dept_where ORDER BY name");
?>
<?php if ($msg): ?>
<div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-people me-2" style="color:#667eea"></i>All Staff Members</h5>
        <?php if (isAdmin()): ?>
        <button type="button" class="btn btn-success" data-modal="addEmployeeModal" data-title="Add New Staff">
            <i class="bi bi-plus-lg"></i> Add Staff
        </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="employeesTable">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($e = $employees->fetch_assoc()):
                    $status_color = $e['is_active'] ? 'success' : 'danger';
                    $status_text = $e['is_active'] ? 'Active' : 'Inactive';
                ?>
                <tr>
                    <td><code><?= e($e['emp_id']) ?></code></td>
                    <td><?= e($e['name']) ?></td>
                    <td><?= e($e['dept_name']) ?></td>
                    <td><?= e($e['designation']) ?></td>
                    <td><span class="badge bg-<?= $e['role']==='super_admin'?'dark':($e['role']==='admin'?'danger':($e['role']==='principal'?'purple':($e['role']==='vice_principal'?'purple':($e['role']==='hod'?'warning':'secondary')))) ?>" style="background:<?= $e['role']==='principal'?'#8b5cf6':($e['role']==='vice_principal'?'#a78bfa':'') ?> !important"><?= ucwords(str_replace('_', ' ', $e['role']??'staff')) ?></span></td>
                    <td><span class="badge bg-<?= $status_color ?>"><?= $status_text ?></span></td>
                    <td>
                        <?php if (isAdmin()): ?>
                        <div class="btn-action-group">
                            <button type="button" class="btn btn-primary btn-action" data-modal="editEmployeeModal"
                                data-title="Edit - <?= e($e['name']) ?>"
                                data-staff_id="<?= $e['id'] ?>"
                                data-emp_id="<?= e($e['emp_id']) ?>"
                                data-name="<?= e($e['name']) ?>"
                                data-department_id="<?= $e['department_id'] ?>"
                                data-designation="<?= e($e['designation']) ?>"
                                data-role="<?= e($e['role']??'staff') ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <?php if ($e['id'] != $_SESSION['user_id']): ?>
                                <?php if ($e['is_active']): ?>
                                <button type="button" class="btn btn-danger btn-action"
                                    hx-post="dashboard.php?page=employees"
                                    hx-vals='<?= json_encode(['delete' => $e['id'], csrf_token_name() => csrf_token()]) ?>'
                                    hx-target="#page-content-wrapper"
                                    hx-confirm="Deactivate <?= e($e['name']) ?>?">
                                    <i class="bi bi-person-x"></i> Deactivate
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-success btn-action"
                                    hx-post="dashboard.php?page=employees"
                                    hx-vals='<?= json_encode(['activate' => $e['id'], csrf_token_name() => csrf_token()]) ?>'
                                    hx-target="#page-content-wrapper"
                                    hx-confirm="Activate <?= e($e['name']) ?>?">
                                    <i class="bi bi-person-check"></i> Activate
                                </button>
                                <button type="button" class="btn btn-warning btn-action"
                                    hx-post="dashboard.php?page=employees"
                                    hx-vals='<?= json_encode(['remove' => $e['id'], csrf_token_name() => csrf_token()]) ?>'
                                    hx-target="#page-content-wrapper"
                                    hx-confirm="Delete <?= e($e['name']) ?> permanently?">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">(You)</span>
                            <?php endif; ?>
                            <button type="button" class="btn btn-warning btn-action" data-modal="resetPasswordModal"
                                data-title="Reset Password - <?= e($e['name']) ?>"
                                data-user_id="<?= $e['id'] ?>"
                                data-emp_name="<?= e($e['name']) ?>">
                                <i class="bi bi-key"></i> Reset
                            </button>
                            <button type="button" class="btn btn-info btn-action" data-modal="changeRoleModal"
                                data-title="Change Role - <?= e($e['name']) ?>"
                                data-user_id="<?= $e['id'] ?>"
                                data-emp_name="<?= e($e['name']) ?>"
                                data-current_role="<?= e($e['role']??'staff') ?>">
                                <i class="bi bi-arrow-repeat"></i> Role
                            </button>
                        </div>
                        <?php else: ?>
                        <span class="text-muted">View only</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=employees" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('addEmployeeModal')}">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" name="emp_id" class="form-control" required placeholder="e.g. EMP0001">
                            <div class="invalid-feedback">Please enter employee ID.</div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe">
                            <div class="invalid-feedback">Please enter full name.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php $depts->data_seek(0); while ($d = $depts->fetch_assoc()): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a department.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation</label>
                            <select name="designation" class="form-select" required>
                                <option value="">Select Designation</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Head of Department">Head of Department</option>
                            </select>
                            <div class="invalid-feedback">Please select a designation.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="staff">Staff</option>
                                <option value="hod">HOD</option>
                                <option value="principal">Principal</option>
                                <option value="vice_principal">Vice Principal</option>
                                <?php if (isAdmin()): ?>
                                <option value="admin">Admin</option>
                                <?php endif; ?>
                                <?php if (isSuperAdmin()): ?>
                                <option value="super_admin">Super Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Enter password">
                            <div class="invalid-feedback">Password is required.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" value="1" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=employees" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('editEmployeeModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="staff_id" data-fill="staff_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" name="emp_id" class="form-control" data-fill="emp_id" required>
                            <div class="invalid-feedback">Please enter employee ID.</div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" data-fill="name" required>
                            <div class="invalid-feedback">Please enter full name.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select" data-fill="department_id" required>
                                <option value="">Select Department</option>
                                <?php $dept_edit_list = $conn->query("SELECT * FROM departments ORDER BY name"); while ($d = $dept_edit_list->fetch_assoc()): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Please select a department.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation</label>
                            <select name="designation" class="form-select" data-fill="designation" required>
                                <option value="">Select Designation</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Head of Department">Head of Department</option>
                            </select>
                            <div class="invalid-feedback">Please select a designation.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" data-fill="role">
                                <option value="staff">Staff</option>
                                <option value="hod">HOD</option>
                                <option value="principal">Principal</option>
                                <option value="vice_principal">Vice Principal</option>
                                <?php if (isAdmin()): ?>
                                <option value="admin">Admin</option>
                                <?php endif; ?>
                                <?php if (isSuperAdmin()): ?>
                                <option value="super_admin">Super Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">New Password (optional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" value="1" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form"
                  hx-post="dashboard.php?page=employees" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('resetPasswordModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" data-fill="user_id">
                <div class="modal-body">
                    <p>Reset password for <strong data-fill="emp_name" id="resetName"></strong> to default <code>123456</code>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reset_password" value="1" class="btn btn-warning"><i class="bi bi-key me-1"></i>Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Role Modal -->
<div class="modal fade" id="changeRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form"
                  hx-post="dashboard.php?page=employees" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('changeRoleModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" data-fill="user_id">
                <div class="modal-body">
                    <p>Change role for <strong data-fill="emp_name"></strong></p>
                    <select name="new_role" class="form-select">
                        <option value="staff">Staff</option>
                        <option value="hod">HOD</option>
                        <option value="principal">Principal</option>
                        <option value="vice_principal">Vice Principal</option>
                        <?php if (isAdmin()): ?>
                        <option value="admin">Admin</option>
                        <?php endif; ?>
                        <?php if (isSuperAdmin()): ?>
                        <option value="super_admin">Super Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_role" value="1" class="btn btn-info"><i class="bi bi-arrow-repeat me-1"></i>Change Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
