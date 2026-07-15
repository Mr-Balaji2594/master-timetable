<?php
$msg = '';
$my_dept = userDeptId();
$dept_scoped = isHOD() && !isPrincipal() && !isVicePrincipal();
$staff_only = !isAdmin() && !isPrincipal() && !isVicePrincipal() && !isHOD();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['assign'])) {
        $employee_id = intval($_POST['employee_id']);
        $subject_id = intval($_POST['subject_id']);
        $check = $conn->query("SELECT id FROM employee_subjects WHERE employee_id=$employee_id AND subject_id=$subject_id");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO employee_subjects (employee_id, subject_id) VALUES ($employee_id, $subject_id)");
            $msg = 'Subject assigned successfully';
            audit_log('subject_assign', "Assigned subject ID $subject_id to employee ID $employee_id");
        } else {
            $msg = 'This subject is already assigned to the staff member';
        }
    } elseif (isset($_POST['remove'])) {
        if (!isAdmin()) { $msg = 'Access denied'; }
        else {
            $conn->query("DELETE FROM employee_subjects WHERE id=" . intval($_POST['remove']));
            $msg = 'Mapping removed';
            audit_log('subject_unassign', "Removed mapping ID " . intval($_POST['remove']));
        }
    } elseif (isset($_POST['edit'])) {
        if (!isAdmin() && !isHOD()) { $msg = 'Access denied'; }
        else {
            $map_id = intval($_POST['map_id']);
            $employee_id = intval($_POST['employee_id']);
            $subject_id = intval($_POST['subject_id']);
            $check = $conn->query("SELECT id FROM employee_subjects WHERE employee_id=$employee_id AND subject_id=$subject_id AND id!=$map_id");
            if ($check->num_rows == 0) {
                $conn->query("UPDATE employee_subjects SET employee_id=$employee_id, subject_id=$subject_id WHERE id=$map_id");
                $msg = 'Mapping updated';
                audit_log('subject_edit', "Updated mapping ID $map_id");
            } else {
                $msg = 'This mapping already exists';
            }
        }
    }
}

$emp_where_parts = [];
if ($dept_scoped) $emp_where_parts[] = "e.department_id = $my_dept";
if ($staff_only) $emp_where_parts[] = "e.id = " . intval($_SESSION['user_id']);
$emp_where_sql = $emp_where_parts ? "WHERE " . implode(" AND ", $emp_where_parts) : "";
$employees = $conn->query("SELECT e.id, e.emp_id, e.name, d.name as dept_name FROM employees e JOIN departments d ON e.department_id = d.id $emp_where_sql ORDER BY d.name, e.name");

$subjects = $conn->query("SELECT s.id, s.name, s.code, d.name as dept_name, s.is_common FROM subjects s JOIN departments d ON s.department_id = d.id ORDER BY s.is_common DESC, d.name, s.name");

$mappings = $conn->query("SELECT es.*, e.emp_id, e.name as emp_name, d.name as emp_dept, s.name as subj_name, s.code as subj_code, sd.name as subj_dept, s.is_common
    FROM employee_subjects es
    JOIN employees e ON es.employee_id = e.id
    JOIN departments d ON e.department_id = d.id
    JOIN subjects s ON es.subject_id = s.id
    JOIN departments sd ON s.department_id = sd.id
    $emp_where_sql
    ORDER BY d.name, e.name, s.name");
?>
<?php if ($msg): ?>
<div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-diagram-3 me-2" style="color:#667eea"></i>Staff Subject Mapping</h5>
        <?php if (isAdmin() || isHOD()): ?>
        <button type="button" class="btn btn-success" data-modal="assignSubjectModal" data-title="Assign Subject to Staff">
            <i class="bi bi-plus-lg"></i> Assign Subject
        </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive-dt">
        <table class="table table-dt" id="staffSubjectsTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Staff Dept</th>
                    <th>Subject</th>
                    <th>Subject Dept</th>
                    <th>Type</th>
                    <?php if (isAdmin() || isHOD()): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = $mappings->fetch_assoc()): ?>
                <tr>
                    <td><code><?= e($m['emp_id']) ?></code> - <?= e($m['emp_name']) ?></td>
                    <td><?= e($m['emp_dept']) ?></td>
                    <td><code><?= e($m['subj_code']) ?></code> - <?= e($m['subj_name']) ?> <?= $m['is_common'] ? '<span class="badge bg-purple" style="background:#8b5cf6;font-size:9px">Common</span>' : '' ?></td>
                    <td><?= e($m['subj_dept']) ?></td>
                    <td><?= $m['is_common'] ? '<span class="badge bg-purple" style="background:#8b5cf6">Common</span>' : '<span class="badge bg-secondary">Dept</span>' ?></td>
                    <?php if (isAdmin() || isHOD()): ?>
                    <td>
                        <div class="btn-action-group">
                            <button type="button" class="btn btn-primary btn-action" data-modal="editMappingModal"
                                data-title="Edit Mapping"
                                data-map_id="<?= $m['id'] ?>"
                                data-employee_id="<?= $m['employee_id'] ?>"
                                data-subject_id="<?= $m['subject_id'] ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <?php if (isAdmin()): ?>
                            <button type="button" class="btn btn-danger btn-action"
                                hx-post="dashboard.php?page=staff_subjects"
                                hx-vals='<?= json_encode(['remove' => $m['id'], csrf_token_name() => csrf_token()]) ?>'
                                hx-target="#page-content-wrapper"
                                hx-confirm="Remove &quot;<?= e($m['subj_name']) ?>&quot; from <?= e($m['emp_name']) ?>?">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Subject Modal -->
<div class="modal fade" id="assignSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Subject to Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form"
                  hx-post="dashboard.php?page=staff_subjects" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('assignSubjectModal')}">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Staff Member</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Staff</option>
                            <?php $employees->data_seek(0); while ($e = $employees->fetch_assoc()): ?>
                            <option value="<?= $e['id'] ?>"><?= e($e['emp_id']) ?> - <?= e($e['name']) ?> (<?= e($e['dept_name']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Select Subject</option>
                            <?php $subjects->data_seek(0); while ($s = $subjects->fetch_assoc()): ?>
                             <option value="<?= $s['id'] ?>"><?= e($s['code']) ?> - <?= e($s['name']) ?> (<?= e($s['dept_name']) ?>)<?= $s['is_common'] ? ' ★Common' : '' ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign" value="1" class="btn btn-success"><i class="bi bi-link me-1"></i>Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Mapping Modal -->
<div class="modal fade" id="editMappingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Mapping</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form"
                  hx-post="dashboard.php?page=staff_subjects" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('editMappingModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="map_id" data-fill="map_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Staff Member</label>
                        <select name="employee_id" class="form-select" data-fill="employee_id" required>
                            <option value="">Select Staff</option>
                            <?php $employees->data_seek(0); while ($e = $employees->fetch_assoc()): ?>
                            <option value="<?= $e['id'] ?>"><?= e($e['emp_id']) ?> - <?= e($e['name']) ?> (<?= e($e['dept_name']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" class="form-select" data-fill="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php $subjects->data_seek(0); while ($s = $subjects->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['code']) ?> - <?= e($s['name']) ?> (<?= e($s['dept_name']) ?>)<?= $s['is_common'] ? ' ★Common' : '' ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit" value="1" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
