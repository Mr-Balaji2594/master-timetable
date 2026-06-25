<?php
if (isHOD()) requireAdminOrHOD();
$msg = '';
$edit_id = isset($_REQUEST['edit']) ? (int)$_REQUEST['edit'] : 0;
$my_dept = isHOD() ? userDeptId() : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $dept_id = intval($_POST['department_id']);
        $credits = intval($_POST['credits']);
        $hours = intval($_POST['lecture_hours_per_week']);
        $conn->query("INSERT INTO subjects (name, code, department_id, credits, lecture_hours_per_week) VALUES ('$name', '$code', $dept_id, $credits, $hours)");
        $msg = 'Subject added successfully';
    } elseif (isset($_POST['update'])) {
        $subject_id = (int)$_POST['subject_id'];
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $dept_id = intval($_POST['department_id']);
        $credits = intval($_POST['credits']);
        $hours = intval($_POST['lecture_hours_per_week']);
        $conn->query("UPDATE subjects SET name='$name', code='$code', department_id=$dept_id, credits=$credits, lecture_hours_per_week=$hours WHERE id=$subject_id");
        $msg = 'Subject updated successfully';
    } elseif (isset($_POST['delete'])) {
        $conn->query("DELETE FROM subjects WHERE id=" . intval($_POST['delete']));
        $msg = 'Subject deleted';
    }
}

$subj_where = isHOD() ? "WHERE s.department_id = $my_dept" : "";
$subjects = $conn->query("SELECT s.*, d.name as dept_name FROM subjects s JOIN departments d ON s.department_id = d.id $subj_where ORDER BY d.name, s.name");
$dept_where = isHOD() ? "WHERE id = $my_dept" : "";
$depts = $conn->query("SELECT * FROM departments $dept_where ORDER BY name");

$edit_subject = null;
if ($edit_id > 0) {
    $edit_result = $conn->query("SELECT * FROM subjects WHERE id=$edit_id");
    $edit_subject = $edit_result->fetch_assoc();
}
?>
<?php if ($msg): ?>
<div class="alert alert-success"><?php echo $msg; ?></div>
<?php endif; ?>

<?php if ($edit_subject): ?>
<div class="card" style="border: 2px solid #3498db;">
    <h5>Edit Subject</h5>
    <form method="POST" action="dashboard.php?page=subjects" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="subject_id" value="<?php echo $edit_subject['id']; ?>">
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" value="<?php echo $edit_subject['name']; ?>" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="code" class="form-control" value="<?php echo $edit_subject['code']; ?>" required>
        </div>
        <div class="col-md-2">
            <select name="department_id" class="form-select" required>
                <option value="">Dept</option>
                <?php 
                $dept_list = $conn->query("SELECT * FROM departments ORDER BY name");
                while ($d = $dept_list->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo ($edit_subject['department_id']==$d['id'])?'selected':''; ?>><?php echo $d['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="credits" class="form-control" value="<?php echo $edit_subject['credits']; ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="lecture_hours_per_week" class="form-control" value="<?php echo $edit_subject['lecture_hours_per_week']; ?>">
        </div>
        <div class="col-md-1">
            <button type="submit" name="update" class="btn btn-primary">Update</button>
        </div>
    </form>
    <div class="mt-2">
        <a href="dashboard.php?page=subjects" class="btn btn-secondary btn-sm">Cancel</a>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h5>Add New Subject</h5>
    <form method="POST" action="dashboard.php?page=subjects" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Subject Name" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="code" class="form-control" placeholder="Code (e.g. CS301)" required>
        </div>
        <div class="col-md-2">
            <select name="department_id" class="form-select" required>
                <option value="">Department</option>
                <?php while ($d = $depts->fetch_assoc()): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo $d['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" name="credits" class="form-control" placeholder="Credits" value="3">
        </div>
        <div class="col-md-2">
            <input type="number" name="lecture_hours_per_week" class="form-control" placeholder="Hours/Week" value="3">
        </div>
        <div class="col-md-1">
            <button type="submit" name="add" class="btn btn-success">Add</button>
        </div>
    </form>
</div>

<div class="card">
    <h5>All Subjects</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Subject Name</th>
                <th>Department</th>
                <th>Credits</th>
                <th>Hours/Week</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($s = $subjects->fetch_assoc()): ?>
            <tr>
                <td><?php echo $s['code']; ?></td>
                <td><?php echo $s['name']; ?></td>
                <td><?php echo $s['dept_name']; ?></td>
                <td><?php echo $s['credits']; ?></td>
                <td><?php echo $s['lecture_hours_per_week']; ?></td>
                <td>
                    <a href="dashboard.php?page=subjects&edit=<?php echo $s['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <form method="POST" action="dashboard.php?page=subjects" style="display:inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete" value="<?php echo $s['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>