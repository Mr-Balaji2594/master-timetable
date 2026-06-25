<?php
if (isHOD()) requireAdminOrHOD();
$msg = '';
$edit_id = isset($_REQUEST['edit']) ? (int)$_REQUEST['edit'] : 0;
$my_dept = isHOD() ? userDeptId() : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['name']);
        $dept_id = intval($_POST['department_id']);
        $batch_year = intval($_POST['batch_year']);
        $year = sanitize($_POST['year']);
        $conn->query("INSERT INTO classes (name, department_id, batch_year, year) VALUES ('$name', $dept_id, $batch_year, '$year')");
        $msg = 'Class added successfully';
    } elseif (isset($_POST['update'])) {
        $class_id = (int)$_POST['class_id'];
        $name = sanitize($_POST['name']);
        $dept_id = intval($_POST['department_id']);
        $batch_year = intval($_POST['batch_year']);
        $year = sanitize($_POST['year']);
        $conn->query("UPDATE classes SET name='$name', department_id=$dept_id, batch_year=$batch_year, year='$year' WHERE id=$class_id");
        $msg = 'Class updated successfully';
    } elseif (isset($_POST['delete'])) {
        $conn->query("DELETE FROM classes WHERE id=" . intval($_POST['delete']));
        $msg = 'Class deleted';
    }
}

$class_where = isHOD() ? "WHERE c.department_id = $my_dept" : "";
$classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id $class_where ORDER BY d.name, c.batch_year");
$dept_where = isHOD() ? "WHERE id = $my_dept" : "";
$depts = $conn->query("SELECT * FROM departments $dept_where ORDER BY name");

$edit_class = null;
if ($edit_id > 0) {
    $edit_result = $conn->query("SELECT * FROM classes WHERE id=$edit_id");
    $edit_class = $edit_result->fetch_assoc();
}
?>
<?php if ($msg): ?>
<div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>

<?php if ($edit_class): ?>
<div class="card" style="border: 2px solid #3498db;">
    <h5>Edit Class</h5>
    <form method="POST" action="dashboard.php?page=classes" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?php echo $edit_class['id']; ?>">
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" value="<?php echo $edit_class['name']; ?>" required>
        </div>
        <div class="col-md-3">
            <select name="department_id" class="form-select" required>
                <option value="">Dept</option>
                <?php 
                $dept_list = $conn->query("SELECT * FROM departments ORDER BY name");
                while ($d = $dept_list->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo ($edit_class['department_id']==$d['id'])?'selected':''; ?>><?php echo $d['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="batch_year" class="form-control" value="<?php echo $edit_class['batch_year']; ?>" required>
        </div>
        <div class="col-md-2">
            <select name="year" class="form-select" required>
                <option value="I" <?php echo ($edit_class['year']=='I')?'selected':''; ?>>I Year</option>
                <option value="II" <?php echo ($edit_class['year']=='II')?'selected':''; ?>>II Year</option>
                <option value="III" <?php echo ($edit_class['year']=='III')?'selected':''; ?>>III Year</option>
                <option value="IV" <?php echo ($edit_class['year']=='IV')?'selected':''; ?>>IV Year</option>
                <option value="V" <?php echo ($edit_class['year']=='V')?'selected':''; ?>>V Year</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" name="update" class="btn btn-primary">Update</button>
        </div>
    </form>
    <div class="mt-2">
        <a href="dashboard.php?page=classes" class="btn btn-secondary btn-sm">Cancel</a>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h5>Add New Class</h5>
    <form method="POST" action="dashboard.php?page=classes" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Class Name (e.g. CS-I-A)" required>
        </div>
        <div class="col-md-3">
            <select name="department_id" class="form-select" required>
                <option value="">Department</option>
                <?php while ($d = $depts->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="batch_year" class="form-control" placeholder="Year" value="<?php echo date('Y'); ?>" required>
        </div>
        <div class="col-md-2">
            <select name="year" class="form-select" required>
                <option value="">Select Year</option>
                <option value="I">I Year</option>
                <option value="II">II Year</option>
                <option value="III">III Year</option>
                <option value="IV">IV Year</option>
                <option value="V">V Year</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" name="add" class="btn btn-success">Add</button>
        </div>
    </form>
</div>

<div class="card">
    <h5>All Classes</h5>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Class Name</th>
                <th>Department</th>
                <th>Batch Year</th>
                <th>Year</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($c = $classes->fetch_assoc()): ?>
            <tr>
                <td><?php echo $c['id']; ?></td>
                <td><?php echo $c['name']; ?></td>
                <td><?php echo $c['dept_name']; ?></td>
                <td><?php echo $c['batch_year']; ?></td>
                <td><?php echo $c['year']; ?></td>
                <td>
                    <a href="dashboard.php?page=classes&edit=<?php echo $c['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <form method="POST" action="dashboard.php?page=classes" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete" value="<?php echo $c['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>