<?php
requireAdminOrHOD();
$msg = '';
$dept_scoped = isHOD() && !isPrincipal() && !isVicePrincipal();
$my_dept = $dept_scoped ? userDeptId() : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['name']);
        $dept_id = intval($_POST['department_id']);
        $batch_year = sanitize($_POST['batch_year']);
        $year = sanitize($_POST['year']);
        $conn->query("INSERT INTO classes (name, department_id, batch_year, year) VALUES ('$name', $dept_id, '$batch_year', '$year')");
        $msg = 'Class added successfully';
    } elseif (isset($_POST['update'])) {
        $class_id = (int)$_POST['class_id'];
        $name = sanitize($_POST['name']);
        $dept_id = intval($_POST['department_id']);
        $batch_year = sanitize($_POST['batch_year']);
        $year = sanitize($_POST['year']);
        $conn->query("UPDATE classes SET name='$name', department_id=$dept_id, batch_year='$batch_year', year='$year' WHERE id=$class_id");
        $msg = 'Class updated successfully';
    } elseif (isset($_POST['delete'])) {
        $conn->query("DELETE FROM classes WHERE id=" . intval($_POST['delete']));
        $msg = 'Class deleted';
    }
}

$class_where = $dept_scoped ? "WHERE c.department_id = $my_dept" : "";
$classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id $class_where ORDER BY d.name, c.batch_year");
$dept_where = $dept_scoped ? "WHERE id = $my_dept" : "";
$depts = $conn->query("SELECT * FROM departments $dept_where ORDER BY name");
?>
<?php if ($msg): ?>
<div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-mortarboard me-2" style="color:#667eea"></i>Classes</h5>
        <?php if (isAdmin()): ?>
        <button type="button" class="btn btn-success" data-modal="addClassModal" data-title="Add New Class">
            <i class="bi bi-plus-lg"></i> Add Class
        </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="classesTable">
            <thead>
                <tr>
                    <th>Room No</th>
                    <th>Department</th>
                    <th>Batch Year</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $classes->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= e($c['name']) ?></strong></td>
                    <td><?= e($c['dept_name']) ?></td>
                    <td><?= $c['batch_year'] ?></td>
                    <td><span class="badge bg-info"><?= e($c['year']) ?></span></td>
                    <td>
                        <?php if (isAdmin()): ?>
                        <div class="btn-action-group">
                            <button type="button" class="btn btn-primary btn-action" data-modal="editClassModal"
                                data-title="Edit - <?= e($c['name']) ?>" data-class_id="<?= $c['id'] ?>"
                                data-name="<?= e($c['name']) ?>" data-department_id="<?= $c['department_id'] ?>"
                                data-batch_year="<?= $c['batch_year'] ?>" data-year="<?= e($c['year']) ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger btn-action"
                                hx-post="dashboard.php?page=classes"
                                hx-vals='<?= json_encode(['delete' => $c['id'], csrf_token_name() => csrf_token()]) ?>'
                                hx-target="#page-content-wrapper"
                                hx-confirm="Delete class &quot;<?= e($c['name']) ?>&quot;?">
                                <i class="bi bi-trash"></i> Delete
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

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=classes" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('addClassModal')}">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Room No</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. CS-I-A">
                        <div class="invalid-feedback">Please enter class name.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Select Department</option>
                            <?php $depts->data_seek(0);
                            while ($d = $depts->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="invalid-feedback">Please select a department.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Year</label>
                        <input type="text" name="batch_year" class="form-control" required placeholder="e.g. 2024-2027">
                        <div class="invalid-feedback">Please enter batch year.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select" required>
                            <option value="">Select Year</option>
                            <option value="I">I Year</option>
                            <option value="II">II Year</option>
                            <option value="III">III Year</option>
                        </select>
                        <div class="invalid-feedback">Please select a year.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" value="1" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add
                        Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<?php $edit_depts = $conn->query("SELECT * FROM departments ORDER BY name"); ?>
<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=classes" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('editClassModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="class_id" data-fill="class_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Room No</label>
                        <input type="text" name="name" class="form-control" data-fill="name" required>
                        <div class="invalid-feedback">Please enter room no.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" data-fill="department_id" required>
                            <option value="">Select Department</option>
                            <?php while ($d = $edit_depts->fetch_assoc()): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="invalid-feedback">Please select a department.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Year</label>
                        <input type="text" name="batch_year" class="form-control" data-fill="batch_year" required
                            placeholder="e.g. 2024-2027">
                        <div class="invalid-feedback">Please enter batch year.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select" data-fill="year" required>
                            <option value="">Select Year</option>
                            <option value="I">I Year</option>
                            <option value="II">II Year</option>
                            <option value="III">III Year</option>
                        </select>
                        <div class="invalid-feedback">Please select a year.</div>
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