<?php
requireAdminOrHOD();
$msg = '';
$my_dept = userDeptId();
$dept_scoped = isHOD() && !isPrincipal() && !isVicePrincipal();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['allocate'])) {
        $subject_id = intval($_POST['subject_id']);
        $employee_id = intval($_POST['employee_id']);
        $day = intval($_POST['day_of_week']);
        $period = intval($_POST['period_no']);
        $semester = sanitize($_POST['semester']);
        $room = sanitize($_POST['room_no']);
        $class_ids = $_POST['class_ids'] ?? [];

        if (empty($class_ids)) {
            $msg = 'Please select at least one class';
        } else {
            $check = $conn->query("SELECT id FROM timetable WHERE class_id={$class_ids[0]} AND day_of_week=$day AND period_no=$period AND semester='$semester'");
            if ($check->num_rows > 0) {
                $msg = 'Slot already exists for the first selected class at this day/period';
            } else {
                $group_id = count($class_ids) > 1 ? $conn->query("SELECT COALESCE(MAX(combined_group_id), 0) + 1 as next_id FROM timetable")->fetch_assoc()['next_id'] : 0;
                $inserted = 0;
                foreach ($class_ids as $cid) {
                    $cid = intval($cid);
                    $cg = $group_id ?: 0;
                    $conn->query("INSERT INTO timetable (class_id, subject_id, employee_id, day_of_week, period_no, semester, room_no, combined_group_id) 
                                  VALUES ($cid, $subject_id, $employee_id, $day, $period, '$semester', '$room', $cg)");
                    $inserted++;
                }
                $msg = "Allocated $inserted class(es) for common paper";
                audit_log('common_allocate', "Allocated subject ID $subject_id to $inserted classes, day=$day period=$period");
            }
        }
    } elseif (isset($_POST['delete_allocation'])) {
        $slot_id = intval($_POST['delete_allocation']);
        $row = $conn->query("SELECT combined_group_id FROM timetable WHERE id=$slot_id")->fetch_assoc();
        if ($row && $row['combined_group_id']) {
            $conn->query("DELETE FROM timetable WHERE combined_group_id={$row['combined_group_id']}");
            $msg = 'Common paper allocation removed (all linked classes)';
        } else {
            $conn->query("DELETE FROM timetable WHERE id=$slot_id");
            $msg = 'Slot removed';
        }
        audit_log('common_deallocate', "Removed slot ID $slot_id");
    }
}

$common_subjects = $conn->query("SELECT s.*, d.name as dept_name FROM subjects s JOIN departments d ON s.department_id = d.id WHERE s.is_common = 1 ORDER BY s.name");
$selected_subject = intval($_GET['subject_id'] ?? ($_POST['subject_id'] ?? 0));

$staff_where = $dept_scoped ? "AND department_id = $my_dept" : "";
$staff_list = $conn->query("SELECT id, emp_id, name FROM employees WHERE is_active=1 $staff_where ORDER BY name");
?>
<?php if ($msg): ?>
<div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card mb-3">
    <h5><i class="bi bi-globe2 me-2" style="color:#8b5cf6"></i>Common Paper Allocation</h5>
    <p class="text-muted small">Allocate language/common subjects (Tamil, English, etc.) across multiple classes at once.</p>
    <form method="GET" class="row g-3" hx-get="dashboard.php" hx-target="#page-content-wrapper" hx-push-url="true">
        <input type="hidden" name="page" value="common_paper_allocation">
        <div class="col-md-4">
            <label class="form-label">Select Common Subject</label>
            <select name="subject_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Choose a common paper --</option>
                <?php while ($cs = $common_subjects->fetch_assoc()): ?>
                <option value="<?= $cs['id'] ?>" <?= $selected_subject == $cs['id'] ? 'selected' : '' ?>>
                    <?= e($cs['code']) ?> - <?= e($cs['name']) ?> (<?= e($cs['dept_name']) ?>)
                </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($selected_subject > 0):
    $subject = $conn->query("SELECT s.*, d.name as dept_name FROM subjects s JOIN departments d ON s.department_id = d.id WHERE s.id = $selected_subject")->fetch_assoc();
    if (!$subject) { echo '<div class="alert alert-danger">Subject not found</div>'; }
    else:
    $sem_filter = $subject['sem_mode'] ? "AND sem_mode='{$subject['sem_mode']}'" : "";
    $classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id WHERE c.year='{$subject['year']}' $sem_filter ORDER BY d.name, c.name");
    ?>
    <div class="row">
        <div class="col-md-5">
            <div class="card mb-3">
                <h6>New Allocation</h6>
                <form method="POST" class="needs-validation" novalidate
                      hx-post="dashboard.php?page=common_paper_allocation" hx-target="#page-content-wrapper">
                    <?= csrf_field() ?>
                    <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                    <div class="mb-3">
                        <label class="form-label">Classes <small class="text-muted">(select one or more)</small></label>
                        <select name="class_ids[]" class="form-select no-select2" multiple required size="8">
                            <?php
                            $allocated_classes = [];
                            $existing = $conn->query("SELECT DISTINCT class_id FROM timetable WHERE subject_id = $selected_subject");
                            while ($ex = $existing->fetch_assoc()) {
                                $allocated_classes[$ex['class_id']] = true;
                            }
                            while ($c = $classes->fetch_assoc()):
                                $disabled = isset($allocated_classes[$c['id']]) ? 'disabled' : '';
                            ?>
                            <option value="<?= $c['id'] ?>" <?= $disabled ?>>
                                <?= e($c['name']) ?> - <?= e($c['dept_name']) ?><?= $disabled ? ' (allocated)' : '' ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple. Greyed = already allocated.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teacher</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Teacher</option>
                            <?php $staff_list->data_seek(0); while ($st = $staff_list->fetch_assoc()): ?>
                            <option value="<?= $st['id'] ?>"><?= e($st['name']) ?> (<?= e($st['emp_id']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <div class="invalid-feedback">Please select a teacher.</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Day</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="">Day</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= ['I','II','III','IV','V','VI'][$i-1] ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Select day.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Period</label>
                            <select name="period_no" class="form-select" required>
                                <option value="">Period</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Select period.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="">Sem</option>
                                <option value="odd">Odd</option>
                                <option value="even">Even</option>
                            </select>
                            <div class="invalid-feedback">Select semester.</div>
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">Room No</label>
                        <input type="text" name="room_no" class="form-control" placeholder="e.g. Hall-1">
                    </div>
                    <button type="submit" name="allocate" value="1" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg me-1"></i>Allocate to Selected Classes
                    </button>
                    <small class="text-muted d-block mt-1">Multiple classes will be linked as a combined group.</small>
                </form>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <h6>Current Allocations — <?= e($subject['name']) ?> (<?= e($subject['code']) ?>)</h6>
                <?php
                $allocations = $conn->query("SELECT t.*, c.name as class_name, d.name as dept_name, e.name as emp_name, e.emp_id
                    FROM timetable t
                    JOIN classes c ON t.class_id = c.id
                    JOIN departments d ON c.department_id = d.id
                    JOIN employees e ON t.employee_id = e.id
                    WHERE t.subject_id = $selected_subject
                    ORDER BY t.day_of_week, t.period_no, d.name");
                if ($allocations->num_rows > 0):
                $day_names = ['I','II','III','IV','V','VI'];
                ?>
                <div class="table-responsive-dt">
                    <table class="table table-dt" id="commonAllocTable" data-sort="false">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Period</th>
                                <th>Class</th>
                                <th>Dept</th>
                                <th>Teacher</th>
                                <th>Sem</th>
                                <th>Room</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $shown_groups = [];
                            while ($a = $allocations->fetch_assoc()):
                                $grp_key = $a['combined_group_id'] ?: 'solo_' . $a['id'];
                                if (in_array($grp_key, $shown_groups)) continue;
                                $shown_groups[] = $grp_key;
                                $group_slots = $conn->query("SELECT t.*, c.name as class_name, d.name as dept_name
                                    FROM timetable t
                                    JOIN classes c ON t.class_id = c.id
                                    JOIN departments d ON c.department_id = d.id
                                    WHERE t.subject_id = $selected_subject
                                    AND t.day_of_week = {$a['day_of_week']}
                                    AND t.period_no = {$a['period_no']}
                                    AND t.semester = '{$a['semester']}'
                                    " . ($a['combined_group_id'] ? "AND t.combined_group_id = {$a['combined_group_id']}" : "AND t.id = {$a['id']}"));
                                $class_list = [];
                                while ($gs = $group_slots->fetch_assoc()) {
                                    $class_list[] = e($gs['class_name']) . ' (' . e($gs['dept_name']) . ')';
                                }
                            ?>
                            <tr>
                                <td><?= $day_names[$a['day_of_week']-1] ?></td>
                                <td><?= $a['period_no'] ?></td>
                                <td><?= implode('<br>', $class_list) ?></td>
                                <td><?= e($a['dept_name']) ?></td>
                                <td><?= e($a['emp_name']) ?></td>
                                <td><?= e($a['semester']) ?></td>
                                <td><?= e($a['room_no'] ?: '-') ?></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm"
                                        hx-post="dashboard.php?page=common_paper_allocation"
                                        hx-vals='<?= json_encode(['delete_allocation' => $a['id'], csrf_token_name() => csrf_token()]) ?>'
                                        hx-target="#page-content-wrapper"
                                        hx-confirm="Remove this allocation (all linked classes)?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">No allocations yet for this subject.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php else: ?>
<div class="card">
    <div class="text-center py-5 text-muted">
        <i class="bi bi-globe2" style="font-size:3rem;display:block;margin-bottom:12px;color:#cbd5e1"></i>
        Select a common paper above to view and manage its class allocations.
    </div>
</div>
<?php endif; ?>
