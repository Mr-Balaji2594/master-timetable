<?php
if (isHOD()) requireAdminOrHOD();
$msg = '';
$edit_id = isset($_REQUEST['edit']) ? (int)$_REQUEST['edit'] : 0;
$my_dept = isHOD() ? userDeptId() : 0;

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
    }
}

$emp_where = isHOD() ? "WHERE e.department_id = $my_dept" : "";
$employees = $conn->query("SELECT e.*, d.name as dept_name FROM employees e JOIN departments d ON e.department_id = d.id $emp_where ORDER BY e.name");
$dept_where = isHOD() ? "WHERE id = $my_dept" : "";
$depts = $conn->query("SELECT * FROM departments $dept_where ORDER BY name");

$edit_staff = null;
if ($edit_id > 0) {
    $edit_result = $conn->query("SELECT * FROM employees WHERE id=$edit_id");
    $edit_staff = $edit_result->fetch_assoc();
}
?>
<?php if ($msg): ?>
<div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>

<?php if ($edit_staff): ?>
<div class="card" style="border: 2px solid #3498db;">
    <h5>Edit Staff</h5>
    <form method="POST" action="dashboard.php?page=employees" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="staff_id" value="<?php echo $edit_staff['id']; ?>">
        <div class="col-md-2">
            <input type="text" name="emp_id" class="form-control" value="<?php echo $edit_staff['emp_id']; ?>" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" value="<?php echo $edit_staff['name']; ?>" required>
        </div>
        <div class="col-md-2">
            <select name="department_id" class="form-select" required>
                <option value="">Dept</option>
                <?php 
                $dept_list = $conn->query("SELECT * FROM departments ORDER BY name");
                while ($d = $dept_list->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo ($edit_staff['department_id']==$d['id'])?'selected':''; ?>><?php echo $d['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="designation" class="form-control" value="<?php echo $edit_staff['designation']; ?>" required>
        </div>
        <div class="col-md-1">
            <select name="role" class="form-select">
                <option value="staff" <?php echo ($edit_staff['role']??'staff')=='staff'?'selected':''; ?>>Staff</option>
                <option value="hod" <?php echo ($edit_staff['role']??'')=='hod'?'selected':''; ?>>HOD</option>
                <option value="admin" <?php echo ($edit_staff['role']??'')=='admin'?'selected':''; ?>>Admin</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="password" name="password" class="form-control" placeholder="New Password (optional)">
        </div>
        <div class="col-md-1">
            <button type="submit" name="update" class="btn btn-primary">Update</button>
        </div>
    </form>
    <div class="mt-2">
        <a href="dashboard.php?page=employees" class="btn btn-secondary btn-sm">Cancel</a>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h5>Add New Staff</h5>
    <form method="POST" action="dashboard.php?page=employees" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-2">
            <input type="text" name="emp_id" class="form-control" placeholder="Emp ID" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Name" required>
        </div>
        <div class="col-md-2">
            <select name="department_id" class="form-select" required>
                <option value="">Dept</option>
                <?php while ($d = $depts->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="designation" class="form-control" placeholder="Designation" required>
        </div>
        <div class="col-md-1">
            <select name="role" class="form-select">
                <option value="staff">Staff</option>
                <option value="hod">HOD</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <div class="col-md-1">
            <button type="submit" name="add" class="btn btn-success">Add</button>
        </div>
    </form>
</div>

<div class="card">
    <h5>All Staff Members</h5>
    <table class="table">
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
                <td><?php echo $e['emp_id']; ?></td>
                <td><?php echo $e['name']; ?></td>
                <td><?php echo $e['dept_name']; ?></td>
                <td><?php echo $e['designation']; ?></td>
                <td><span class="badge bg-<?php echo ($e['role']??'staff')=='admin'?'danger':(($e['role']??'')=='hod'?'warning':'secondary'); ?>"><?php echo ucfirst($e['role']??'staff'); ?></span></td>
                <td><span class="badge bg-<?php echo $status_color; ?>"><?php echo $status_text; ?></span></td>
                <td>
                    <a href="dashboard.php?page=employees&edit=<?php echo $e['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <?php if ($e['id'] != $_SESSION['user_id']): ?>
                        <?php if ($e['is_active']): ?>
                        <form method="POST" action="dashboard.php?page=employees" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="delete" value="<?php echo $e['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Deactivate</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="dashboard.php?page=employees" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="activate" value="<?php echo $e['id']; ?>">
                            <button type="submit" class="btn btn-success btn-sm">Activate</button>
                        </form>
                        <form method="POST" action="dashboard.php?page=employees" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="remove" value="<?php echo $e['id']; ?>">
                            <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Delete permanently?')">Delete</button>
                        </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">(You)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>