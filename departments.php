<?php
if (isHOD()) requireAdminOrHOD();
$msg = '';
$edit_id = isset($_REQUEST['edit']) ? (int)$_REQUEST['edit'] : 0;
$my_dept = isHOD() ? userDeptId() : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $conn->query("INSERT INTO departments (name, code) VALUES ('$name', '$code')");
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
        $conn->query("UPDATE departments SET name='$name', code='$code' WHERE id=$dept_id");
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

$dept_where = isHOD() ? "WHERE id=$my_dept" : "";
$depts = $conn->query("SELECT * FROM departments $dept_where ORDER BY name");
$edit_dept = null;
if ($edit_id > 0) {
    if (isHOD() && $edit_id != $my_dept) redirect('dashboard.php?page=departments');
    $edit_result = $conn->query("SELECT * FROM departments WHERE id=$edit_id");
    $edit_dept = $edit_result->fetch_assoc();
}
?>
<script>
function showStaffFields() {
    var count = document.getElementById('staff_count').value;
    var container = document.getElementById('staff_list');
    var wrapper = document.getElementById('staff_fields');
    if (count > 0) {
        wrapper.style.display = 'block';
        var html = '';
        for (var i = 1; i <= count; i++) {
            html += '<div class="row g-3 mb-2">' +
                '<div class="col-md-1"><strong>Staff ' + i + '</strong></div>' +
                '<div class="col-md-4"><input type="text" name="staff_name_' + i + '" class="form-control" placeholder="Name"></div>' +
                '<div class="col-md-3"><input type="text" name="staff_desig_' + i + '" class="form-control" placeholder="Designation"></div>' +
            '</div>';
        }
        container.innerHTML = html;
    } else {
        wrapper.style.display = 'none';
    }
}
</script>
<?php if ($msg): ?>
<div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>

<?php if ($edit_dept): ?>
<div class="card" style="border: 2px solid #3498db;">
    <h5>Edit Department</h5>
    <form method="POST" action="dashboard.php?page=departments" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="dept_id" value="<?php echo $edit_dept['id']; ?>">
        <div class="col-md-4">
            <input type="text" name="name" class="form-control" value="<?php echo $edit_dept['name']; ?>" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="code" class="form-control" value="<?php echo $edit_dept['code']; ?>" required>
        </div>
        <div class="col-md-2">
            <button type="submit" name="update" class="btn btn-primary">Update</button>
        </div>
        <div class="col-md-2">
            <a href="dashboard.php?page=departments" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (isAdmin()): ?>
<div class="card">
    <h5>Add New Department</h5>
    <form method="POST" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Department Name" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="code" class="form-control" placeholder="Code (e.g. CS)" required>
        </div>
        <div class="col-md-2">
            <input type="number" name="staff_count" id="staff_count" class="form-control" placeholder="Staff Count" min="0" value="0" onchange="showStaffFields()">
        </div>
        <div class="col-md-2">
            <button type="submit" name="add" class="btn btn-success">Add</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div id="staff_fields" class="card" style="display:none;">
    <h5>Add Staff Members</h5>
    <div id="staff_list"></div>
</div>

<div class="card">
    <h5>All Departments</h5>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Code</th>
                <th>Staff Count</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($d = $depts->fetch_assoc()): ?>
            <tr>
                <td><?php echo $d['id']; ?></td>
                <td><?php echo $d['name']; ?></td>
                <td><?php echo $d['code']; ?></td>
                <td><?php echo (int)$conn->query("SELECT COUNT(*) as c FROM employees WHERE department_id=".$d['id'])->fetch_assoc()['c']; ?></td>
                <td>
                    <a href="dashboard.php?page=departments&edit=<?php echo $d['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <form method="POST" action="dashboard.php?page=departments" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete" value="<?php echo $d['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>