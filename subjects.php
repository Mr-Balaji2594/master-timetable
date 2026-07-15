<?php

requireAdminOrHOD();
$msg = '';
$dept_scoped = isHOD() && !isPrincipal() && !isVicePrincipal();
$my_dept = $dept_scoped ? userDeptId() : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $dept_id = intval($_POST['department_id']);
        $credits = intval($_POST['credits']);
        $hours = intval($_POST['lecture_hours_per_week']);
        $year = sanitize($_POST['year']);
        $sem = intval($_POST['sem']);
        $sem_mode = sanitize($_POST['sem_mode']);
        $is_common = isset($_POST['is_common']) ? 1 : 0;
        $conn->query("INSERT INTO subjects (name, code, department_id, credits, lecture_hours_per_week, year, sem, sem_mode, is_common) VALUES ('$name', '$code', $dept_id, $credits, $hours, '$year', $sem, '$sem_mode', $is_common)");
        $msg = 'Subject added successfully';
    } elseif (isset($_POST['update'])) {
        $subject_id = (int)$_POST['subject_id'];
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $dept_id = intval($_POST['department_id']);
        $credits = intval($_POST['credits']);
        $hours = intval($_POST['lecture_hours_per_week']);
        $year = sanitize($_POST['year']);
        $sem = intval($_POST['sem']);
        $sem_mode = sanitize($_POST['sem_mode']);
        $is_common = isset($_POST['is_common']) ? 1 : 0;
        $conn->query("UPDATE subjects SET name='$name', code='$code', department_id=$dept_id, credits=$credits, lecture_hours_per_week=$hours, year='$year', sem=$sem, sem_mode='$sem_mode', is_common=$is_common WHERE id=$subject_id");
        $msg = 'Subject updated successfully';
    } elseif (isset($_POST['delete'])) {
        if (!isAdmin()) {
            $msg = 'Access denied';
        } else {
            $conn->query("DELETE FROM subjects WHERE id=" . intval($_POST['delete']));
            $msg = 'Subject deleted';
        }
    }
}

$subj_where = $dept_scoped ? "WHERE s.department_id = $my_dept" : "";
$subjects = $conn->query("SELECT s.*, d.name as dept_name FROM subjects s JOIN departments d ON s.department_id = d.id $subj_where ORDER BY d.name, s.name");
$depts_result = $conn->query("SELECT * FROM departments ORDER BY name");
$dept_list = [];
while ($d = $depts_result->fetch_assoc()) {
    $dept_list[] = $d;
}
?>
<?php if ($msg): ?>
<div class="alert alert-success alert-auto"><?= $msg ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-journal-bookmark-fill me-2" style="color:#667eea"></i>Subjects</h5>
        <button type="button" class="btn btn-success" data-modal="addSubjectModal" data-title="Add New Subject">
            <i class="bi bi-plus-lg"></i> Add Subject
        </button>
    </div>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="subjectsTable">
            <thead>
                <tr>
                    <th>Sub Code</th>
                    <th>Subject Name</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>Year</th>
                    <th>Sem</th>
                    <th>Mode</th>
                    <th>Credits</th>
                    <th>Hours/Week</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($s = $subjects->fetch_assoc()): ?>
                <tr>
                    <td><code><?= e($s['code']) ?></code></td>
                    <td><strong><?= e($s['name']) ?></strong></td>
                    <td><?= e($s['dept_name']) ?></td>
                    <td><?= $s['is_common'] ? '<span class="badge bg-purple" style="background:#8b5cf6">Common</span>' : '<span class="badge bg-secondary">Dept</span>' ?></td>
                    <td><span class="badge bg-info"><?= e($s['year']) ?></span></td>
                    <td><span class="badge bg-secondary"><?= (int)$s['sem'] ?></span></td>
                    <td><span
                            class="badge bg-<?= $s['sem_mode'] == 'odd' ? 'warning' : 'dark' ?>"><?= e($s['sem_mode']) ?></span>
                    </td>
                    <td><?= (int)$s['credits'] ?></td>
                    <td><?= (int)$s['lecture_hours_per_week'] ?></td>
                    <td>
                        <div class="btn-action-group">
                            <button type="button" class="btn btn-primary btn-action" data-modal="editSubjectModal"
                                data-title="Edit Subject - <?= e($s['name']) ?>" data-subject_id="<?= $s['id'] ?>"
                                data-name="<?= e($s['name']) ?>" data-code="<?= e($s['code']) ?>"
                                data-department_id="<?= $s['department_id'] ?>" data-credits="<?= (int)$s['credits'] ?>"
                                data-lecture_hours_per_week="<?= (int)$s['lecture_hours_per_week'] ?>"
                                data-year="<?= e($s['year']) ?>" data-sem="<?= (int)$s['sem'] ?>"
                                data-sem_mode="<?= e($s['sem_mode']) ?>"
                                data-is_common="<?= (int)$s['is_common'] ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <?php if (isAdmin()): ?>
                            <button type="button" class="btn btn-danger btn-action"
                                hx-post="dashboard.php?page=subjects"
                                hx-vals='<?= json_encode(['delete' => $s['id'], csrf_token_name() => csrf_token()]) ?>'
                                hx-target="#page-content-wrapper"
                                hx-confirm="Delete subject &quot;<?= e($s['name']) ?>&quot;?">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=subjects" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('addSubjectModal')}">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="name" class="form-control" required
                            placeholder="e.g. Java Programming">
                        <div class="invalid-feedback">Please enter subject name.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject Code</label>
                        <input type="text" name="code" class="form-control" required placeholder="e.g. 225C3A">
                        <div class="invalid-feedback">Please enter subject code.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Select Department</option>
                            <?php foreach ($dept_list as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a department.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Credits</label>
                        <input type="number" name="credits" class="form-control" required value="3" min="1">
                        <div class="invalid-feedback">Please enter credits.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hours/Week</label>
                        <input type="number" name="lecture_hours_per_week" class="form-control" required value="3"
                            min="1">
                        <div class="invalid-feedback">Please enter hours per week.</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_common" id="is_common_add" value="1">
                            <label class="form-check-label" for="is_common_add">
                                <i class="bi bi-globe2 me-1"></i>Common Paper (taught across multiple departments)
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select" required>
                                <option value="">Select</option>
                                <option value="I">I</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                            </select>
                            <div class="invalid-feedback">Please select year.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Semester</label>
                            <select name="sem" class="form-select" required>
                                <option value="">Select</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Please select semester.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sem Mode</label>
                            <select name="sem_mode" class="form-select" required>
                                <option value="">Select</option>
                                <option value="odd">Odd</option>
                                <option value="even">Even</option>
                            </select>
                            <div class="invalid-feedback">Please select sem mode.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" value="1" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add
                        Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=subjects" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('editSubjectModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="subject_id" data-fill="subject_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Subject Name</label>
                        <input type="text" name="name" class="form-control" data-fill="name" required>
                        <div class="invalid-feedback">Please enter subject name.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" data-fill="code" required>
                        <div class="invalid-feedback">Please enter subject code.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" data-fill="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($dept_list as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a department.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Credits</label>
                        <input type="number" name="credits" class="form-control" data-fill="credits" required min="1">
                        <div class="invalid-feedback">Please enter credits.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hours/Week</label>
                        <input type="number" name="lecture_hours_per_week" class="form-control"
                            data-fill="lecture_hours_per_week" required min="1">
                        <div class="invalid-feedback">Please enter hours per week.</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_common" id="is_common_edit" value="1" data-fill="is_common">
                            <label class="form-check-label" for="is_common_edit">
                                <i class="bi bi-globe2 me-1"></i>Common Paper (taught across multiple departments)
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Year</label>
                            <select name="year" class="form-select" data-fill="year" required>
                                <option value="">Select</option>
                                <option value="I">I</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                            </select>
                            <div class="invalid-feedback">Please select year.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Semester</label>
                            <select name="sem" class="form-select" data-fill="sem" required>
                                <option value="">Select</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Please select semester.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sem Mode</label>
                            <select name="sem_mode" class="form-select" data-fill="sem_mode" required>
                                <option value="">Select</option>
                                <option value="odd">Odd</option>
                                <option value="even">Even</option>
                            </select>
                            <div class="invalid-feedback">Please select sem mode.</div>
                        </div>
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