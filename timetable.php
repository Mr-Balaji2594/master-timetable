<?php
$msg = '';
$view = $_GET['view'] ?? 'staff';
$selected_semester = $_GET['semester'] ?? '';
$selected_employee = $_GET['employee_id'] ?? 0;
$selected_dept = $_GET['dept_id'] ?? 0;
$my_dept = userDeptId();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_slot'])) {
        $class_id = intval($_POST['class_id']);
        $subject_id = intval($_POST['subject_id']);
        $employee_id = intval($_POST['employee_id']);
        $day = intval($_POST['day_of_week']);
        $period = intval($_POST['period_no']);
        $room = sanitize($_POST['room_no']);
        $semester = sanitize($_POST['semester']);
        $combined_class_id = intval($_POST['combined_class_id'] ?? 0);

        $check = $conn->query("SELECT id FROM timetable WHERE class_id=$class_id AND day_of_week=$day AND period_no=$period AND semester='$semester'");
        if ($check->num_rows > 0) {
            $msg = 'Slot already exists for this class at this day/period';
        } elseif ($combined_class_id > 0) {
            $check2 = $conn->query("SELECT id FROM timetable WHERE class_id=$combined_class_id AND day_of_week=$day AND period_no=$period AND semester='$semester'");
            if ($check2->num_rows > 0) {
                $msg = 'Slot already exists for the combined class at this day/period';
            } else {
                $max = $conn->query("SELECT COALESCE(MAX(combined_group_id), 0) + 1 as next_id FROM timetable")->fetch_assoc();
                $group_id = $max['next_id'];
                $conn->query("INSERT INTO timetable (class_id, subject_id, employee_id, day_of_week, period_no, semester, room_no, combined_group_id) 
                              VALUES ($class_id, $subject_id, $employee_id, $day, $period, '$semester', '$room', $group_id)");
                $conn->query("INSERT INTO timetable (class_id, subject_id, employee_id, day_of_week, period_no, semester, room_no, combined_group_id) 
                              VALUES ($combined_class_id, $subject_id, $employee_id, $day, $period, '$semester', '$room', $group_id)");
                $msg = 'Combined timetable slot added';
            }
        } else {
            $conn->query("INSERT INTO timetable (class_id, subject_id, employee_id, day_of_week, period_no, semester, room_no) 
                          VALUES ($class_id, $subject_id, $employee_id, $day, $period, '$semester', '$room')");
            $msg = 'Timetable slot added';
        }
    } elseif (isset($_POST['delete_slot'])) {
        $slot_id = intval($_POST['delete_slot']);
        if (!isAdminOrHOD()) {
            $check = $conn->query("SELECT id FROM timetable WHERE id=$slot_id AND employee_id=" . intval($_SESSION['user_id']));
            if ($check->num_rows == 0) {
                $msg = 'You can only delete your own slots';
            }
        }
        if (empty($msg)) {
            $row = $conn->query("SELECT combined_group_id FROM timetable WHERE id=$slot_id")->fetch_assoc();
            if ($row && $row['combined_group_id']) {
                $conn->query("DELETE FROM timetable WHERE combined_group_id={$row['combined_group_id']}");
            } else {
                $conn->query("DELETE FROM timetable WHERE id=$slot_id");
            }
            $msg = 'Slot deleted';
        }
    }
}

if ($view === 'dept' && !isAdminOrHOD()) $view = 'staff';

$depts = $conn->query("SELECT * FROM departments ORDER BY name");

$day_names = ['I', 'II', 'III', 'IV', 'V', 'VI'];

function subject_color($subject_id) {
    $colors = [
        ['bg' => '#dbeafe', 'text' => '#1e40af'],
        ['bg' => '#dcfce7', 'text' => '#166534'],
        ['bg' => '#fef3c7', 'text' => '#92400e'],
        ['bg' => '#fce7f3', 'text' => '#9d174d'],
        ['bg' => '#e0e7ff', 'text' => '#3730a3'],
        ['bg' => '#ccfbf1', 'text' => '#115e59'],
        ['bg' => '#fff7ed', 'text' => '#9a3412'],
        ['bg' => '#f3e8ff', 'text' => '#6b21a8'],
        ['bg' => '#fef2f2', 'text' => '#991b1b'],
        ['bg' => '#e5f4fd', 'text' => '#0c4a6e'],
    ];
    return $colors[$subject_id % count($colors)];
}

function render_timetable_grid($result, $show_delete = false) {
    global $day_names;
    $timetable = [];
    $subject_ids = [];
    while ($row = $result->fetch_assoc()) {
        $timetable[$row['day_of_week']][$row['period_no']][] = $row;
        $subject_ids[$row['subject_id']] = $row['subject_code'];
    }
    ?>
    <div class="timetable-grid">
        <div class="timetable-cell heading corner">Day</div>
        <div class="timetable-cell heading period-header"><span class="period-num">1</span></div>
        <div class="timetable-cell heading period-header"><span class="period-num">2</span></div>
        <div class="timetable-cell heading period-header"><span class="period-num">3</span></div>
        <div class="timetable-cell heading period-header"><span class="period-num">4</span></div>
        <div class="timetable-cell heading period-header"><span class="period-num">5</span></div>
        <div class="timetable-cell heading period-header"><span class="period-num">6</span></div>
        <?php for ($d = 1; $d <= 6; $d++): ?>
            <div class="timetable-cell day-label"><?= $day_names[$d-1] ?></div>
            <?php for ($p = 1; $p <= 6; $p++): ?>
                <div class="timetable-cell">
                    <?php if (isset($timetable[$d][$p])): ?>
                        <?php foreach ($timetable[$d][$p] as $slot):
                            $sc = subject_color($slot['subject_id']);
                        ?>
                        <div class="slot-item" style="background:<?= $sc['bg'] ?>;border-left:3px solid <?= $sc['text'] ?>">
                            <div class="slot-subject" style="color:<?= $sc['text'] ?>"><?= $slot['subject_code'] ?></div>
                            <?php if (!empty($slot['subject_name'])): ?>
                                <div class="slot-subject-name"><?= htmlspecialchars($slot['subject_name']) ?></div>
                            <?php endif; ?>
                            <div class="slot-details">
                                <span class="slot-staff"><?= $slot['emp_name'] ?></span>
                                <?php if (!empty($slot['class_name'])): ?>
                                    <span class="slot-class"><?= htmlspecialchars($slot['class_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($slot['room_no'])): ?>
                                    <span class="slot-room"><?= htmlspecialchars($slot['room_no']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($slot['combined_class_name'])): ?>
                                <span class="combined-badge">+<?= htmlspecialchars($slot['combined_class_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($show_delete): ?>
                                <button type="button" class="btn btn-danger btn-sm mt-1"
                                    hx-post="dashboard.php?page=timetable"
                                    hx-vals='<?= json_encode(['delete_slot' => $slot['id'], csrf_token_name() => csrf_token()]) ?>'
                                    hx-target="#page-content-wrapper"
                                    hx-confirm="Delete slot?">X</button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="empty-slot"></span>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        <?php endfor; ?>
    </div>
    <?php if (!empty($subject_ids)): ?>
    <div class="timetable-legend">
        <span class="legend-title">Subjects:</span>
        <?php foreach ($subject_ids as $sid => $sc):
            $scolor = subject_color($sid); ?>
            <span class="legend-item" style="background:<?= $scolor['bg'] ?>;color:<?= $scolor['text'] ?>"><?= $sc ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif;
}
?>

<style>
.nav-tabs .nav-link { cursor: pointer; }
</style>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $view=='staff'?'active':'' ?>" hx-get="dashboard.php?page=timetable&view=staff" hx-target="#page-content-wrapper" hx-push-url="true">Staff Timetable</a>
    </li>
    <?php if (isAdminOrHOD()): ?>
    <li class="nav-item">
        <a class="nav-link <?= $view=='dept'?'active':'' ?>" hx-get="dashboard.php?page=timetable&view=dept" hx-target="#page-content-wrapper" hx-push-url="true">Department Timetable</a>
    </li>
    <?php endif; ?>
</ul>

<?php if ($msg): ?>
    <div class="alert alert-success alert-auto"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($view == 'staff'): ?>

<?php
if ($selected_employee == 0 && !isAdminOrHOD()) {
    $selected_employee = intval($_SESSION['user_id']);
}
$my_profile = ($selected_employee == intval($_SESSION['user_id']));
$my_dept_for_staff = $my_dept;
?>

<div class="card mb-3">
    <h5>Staff Timetable</h5>
    <form method="GET" class="row g-3" hx-get="dashboard.php" hx-target="#page-content-wrapper" hx-push-url="true">
        <input type="hidden" name="page" value="timetable">
        <input type="hidden" name="view" value="staff">
        <div class="col-md-3">
            <select name="employee_id" class="form-select" onchange="this.form.submit()">
                <option value="">Select Staff</option>
                <?php
$emp_where = !isAdmin() && !isPrincipal() && !isVicePrincipal() ? "AND department_id = $my_dept AND role='staff'" : "";
$emps = $conn->query("SELECT * FROM employees WHERE is_active=1 $emp_where ORDER BY name");
                while ($e = $emps->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>" <?= $selected_employee==$e['id']?'selected':'' ?>><?= $e['name'] ?> (<?= $e['emp_id'] ?>)<?= $e['id']==$_SESSION['user_id']?' (Me)':'' ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="semester" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="odd" <?= $selected_semester=='odd'?'selected':'' ?>>Odd</option>
                <option value="even" <?= $selected_semester=='even'?'selected':'' ?>>Even</option>
            </select>
        </div>
    </form>
</div>

<?php if ($selected_employee > 0):
    $emp_name_row = $conn->query("SELECT name FROM employees WHERE id=$selected_employee")->fetch_assoc();
    $emp_name = $emp_name_row['name'] ?? 'Staff';
    $sem_filter = $selected_semester ? " AND t.semester='$selected_semester'" : "";
    $result = $conn->query("SELECT t.*, s.name as subject_name, s.code as subject_code, 
                                   e.name as emp_name, e.emp_id, c.name as class_name,
                                   cc.name as combined_class_name
                           FROM timetable t 
                           JOIN subjects s ON t.subject_id = s.id 
                           JOIN employees e ON t.employee_id = e.id 
                           JOIN classes c ON t.class_id = c.id
                           LEFT JOIN timetable tc ON t.combined_group_id = tc.combined_group_id AND tc.class_id != t.class_id
                           LEFT JOIN classes cc ON tc.class_id = cc.id
                           WHERE t.employee_id = $selected_employee $sem_filter
                           ORDER BY t.day_of_week, t.period_no");
    ?>
    <div class="card mb-3" id="print-area">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Weekly Timetable - <?= htmlspecialchars($emp_name) ?></h5>
            <div class="d-flex gap-2">
                <?php if ($my_profile || isAdminOrHOD()): ?>
                <button type="button" class="btn btn-success btn-sm" data-modal="addSlotModal" data-title="Add Timetable Slot" data-employee_id="<?= $selected_employee ?>">
                    <i class="bi bi-plus-lg"></i> Add Slot
                </button>
                <?php endif; ?>
                <a href="export_pdf.php?type=timetable_staff&employee_id=<?= $selected_employee ?>&semester=<?= $selected_semester ?>" class="btn btn-info btn-sm" target="_blank"><i class="bi bi-filetype-pdf"></i> PDF</a>
            </div>
        </div>
        <?php render_timetable_grid($result, true); ?>
    </div>

    <?php
    $class_list = [];
    $result = $conn->query("SELECT DISTINCT c.name FROM timetable t 
                           JOIN classes c ON t.class_id = c.id
                           WHERE t.employee_id = $selected_employee $sem_filter
                           ORDER BY c.name");
    while ($row = $result->fetch_assoc()) {
        $class_list[] = $row['name'];
    }
    if ($class_list): ?>
    <div class="card">
        <h5>Classes Assigned</h5>
        <ul>
            <?php foreach ($class_list as $cn): ?>
                <li><?= $cn ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php if ($view == 'dept'): ?>

<div class="card mb-3">
    <h5>Department Timetable</h5>
    <form method="GET" class="row g-3" hx-get="dashboard.php" hx-target="#page-content-wrapper" hx-push-url="true">
        <input type="hidden" name="page" value="timetable">
        <input type="hidden" name="view" value="dept">
        <?php if (!isHOD()): ?>
        <div class="col-md-3">
            <select name="dept_id" class="form-select" onchange="this.form.submit()">
                <option value="">Select Department</option>
                <?php
                $depts->data_seek(0);
                while ($d = $depts->fetch_assoc()): ?>
                    <option value="<?= $d['id'] ?>" <?= ($selected_dept ?: $my_dept)==$d['id']?'selected':'' ?>><?= $d['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <?php else:
            $selected_dept = $my_dept;
        ?>
        <div class="col-md-3">
            <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['dept_name'] ?? '') ?>" disabled>
        </div>
        <?php endif; ?>
        <div class="col-md-2">
            <select name="semester" class="form-select" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="odd" <?= $selected_semester=='odd'?'selected':'' ?>>Odd</option>
                <option value="even" <?= $selected_semester=='even'?'selected':'' ?>>Even</option>
            </select>
        </div>
    </form>
</div>

<?php
$dept_id = $selected_dept ?: $my_dept;
    if ($dept_id > 0):
    $sem_filter = $selected_semester ? " AND t.semester='$selected_semester'" : "";
    ?>
    <div class="text-end mb-3">
        <a href="export_pdf.php?type=timetable_dept&dept_id=<?= $dept_id ?>&semester=<?= $selected_semester ?>" class="btn btn-info" target="_blank"><i class="bi bi-filetype-pdf"></i> Download Full Department Timetable (PDF)</a>
    </div>
    <div id="print-area">
    <?php
    $dept_classes = $conn->query("SELECT * FROM classes WHERE department_id = $dept_id ORDER BY name");
    $dept_classes = $conn->query("SELECT * FROM classes WHERE department_id = $dept_id ORDER BY name");
    while ($dc = $dept_classes->fetch_assoc()):
        $class_result = $conn->query("SELECT t.*, s.name as subject_name, s.code as subject_code, e.name as emp_name, e.emp_id,
                                             cc.name as combined_class_name
                                     FROM timetable t 
                                     JOIN subjects s ON t.subject_id = s.id 
                                     JOIN employees e ON t.employee_id = e.id 
                                     LEFT JOIN timetable tc ON t.combined_group_id = tc.combined_group_id AND tc.class_id != t.class_id
                                     LEFT JOIN classes cc ON tc.class_id = cc.id
                                     WHERE t.class_id = {$dc['id']} $sem_filter
                                     ORDER BY t.day_of_week, t.period_no");
        if ($class_result->num_rows > 0):
?>
<div class="card mb-3">
    <h5><?= $dc['name'] ?> (Batch <?= $dc['batch_year'] ?>)</h5>
    <?php render_timetable_grid($class_result, true); ?>
</div>
<?php
        endif;
    endwhile;
    ?>
    </div>
<?php endif; ?>

<?php endif; ?>

<!-- Add Slot Modal -->
<div class="modal fade" id="addSlotModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Timetable Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-form needs-validation" novalidate
                  hx-post="dashboard.php?page=timetable" hx-target="#page-content-wrapper"
                  hx-on::after-request="if(event.detail.successful){window.closeModal('addSlotModal')}">
                <?= csrf_field() ?>
                <input type="hidden" name="employee_id" data-fill="employee_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="">Select</option>
                                <option value="odd">Odd</option>
                                <option value="even">Even</option>
                            </select>
                            <div class="invalid-feedback">Select semester.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select" required>
                                <option value="">Select Class</option>
                                <?php
                                $my_classes = $conn->query("SELECT * FROM classes WHERE department_id = $my_dept_for_staff ORDER BY name");
                                while ($mc = $my_classes->fetch_assoc()): ?>
                                    <option value="<?= $mc['id'] ?>"><?= htmlspecialchars($mc['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Select a class.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select Subject</option>
                                <?php
                                $my_subs = $conn->query("SELECT * FROM subjects WHERE department_id = $my_dept_for_staff ORDER BY name");
                                while ($ms = $my_subs->fetch_assoc()): ?>
                                    <option value="<?= $ms['id'] ?>"><?= htmlspecialchars($ms['code']) ?> - <?= htmlspecialchars($ms['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="invalid-feedback">Select a subject.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Day</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="">Select Day</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?= $i ?>"><?= ['I','II','III','IV','V','VI'][$i-1] ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Select a day.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Period</label>
                            <select name="period_no" class="form-select" required>
                                <option value="">Select Period</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <div class="invalid-feedback">Select a period.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Room No</label>
                            <input type="text" name="room_no" class="form-control" placeholder="e.g. 101">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="combinedToggleStaff" onchange="document.getElementById('combinedClassGroupStaff').style.display = this.checked ? 'block' : 'none'">
                                <label class="form-check-label" for="combinedToggleStaff">Combined Class</label>
                            </div>
                        </div>
                        <div class="col-12" id="combinedClassGroupStaff" style="display:none">
                            <label class="form-label">Combined With</label>
                            <select name="combined_class_id" class="form-select">
                                <option value="">Select second class</option>
                                <?php
                                $all_classes = $conn->query("SELECT c.*, d.name as dept_name FROM classes c JOIN departments d ON c.department_id = d.id ORDER BY d.name, c.name");
                                while ($ac = $all_classes->fetch_assoc()): ?>
                                    <option value="<?= $ac['id'] ?>"><?= htmlspecialchars($ac['name']) ?> (<?= htmlspecialchars($ac['dept_name']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_slot" value="1" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Add Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCombinedStaff() {
    var checked = document.getElementById('combinedToggleStaff').checked;
    document.getElementById('combinedClassGroupStaff').style.display = checked ? 'block' : 'none';
}
</script>
