<?php
requireAdmin();
$msg = '';
$errors = [];
$success = 0;
$failed = 0;
$type = $_POST['upload_type'] ?? $_GET['type'] ?? 'departments';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $err_code = $_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err_code !== UPLOAD_ERR_OK) {
        $err_msgs = [0=>'OK',1=>'File exceeds php.ini upload_max_filesize',2=>'File exceeds MAX_FILE_SIZE',3=>'Partial upload',4=>'No file selected',6=>'Missing temp folder',7=>'Failed to write file',8=>'Upload stopped by extension'];
        $msg = 'Upload error: ' . ($err_msgs[$err_code] ?? "code $err_code");
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $raw = file_get_contents($file);
        if ($raw === false) {
            $msg = 'Could not read uploaded file.';
        } else {
            // Strip BOM if present
            if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);
            $lines = explode("\n", str_replace("\r\n", "\n", $raw));
            $total_lines = count($lines);
            // Auto-detect delimiter (tab or comma) from header row
            $delim = ',';

            $first_line = $lines[0] ?? '';
            // Count tabs vs commas in the raw line (before any CSV parsing)
            $tab_count = substr_count($first_line, "\t");
            $comma_count = substr_count($first_line, ',');
            if ($tab_count > $comma_count) $delim = "\t";

            $first_row = '';
            $first_cols = -1;
            $first_raw = '';
            $line = 0;
            foreach ($lines as $raw_line) {
                $line++;
                if ($line === 1) continue;
                $raw_line = trim($raw_line);
                if ($raw_line === '') continue;
                if ($first_raw === '') $first_raw = $raw_line;
                $data = str_getcsv($raw_line, $delim);
                $data = array_map('trim', $data);
                if ($first_cols === -1) { $first_cols = count($data); $first_row = implode('|', $data); }
                if (empty($data[0] ?? '') && empty($data[1] ?? '')) continue;
                try {
                    if ($type == 'departments') {
                        $name = sanitize($data[0] ?? '');
                        $code = sanitize($data[1] ?? '');
                        if (empty($name) || empty($code)) throw new Exception('Name and code required');
                        $r = $conn->query("INSERT INTO departments (name, code) VALUES ('$name', '$code')");
                        if ($r === false) throw new Exception('DB error: ' . $conn->error);
                        if ($conn->affected_rows > 0) $success++; else throw new Exception('Insert failed (0 rows)');
                    } elseif ($type == 'employees') {
                        $emp_id = sanitize($data[0] ?? '');
                        $dept_id = intval($data[1] ?? 0);
                        $name = sanitize($data[2] ?? '');
                        $designation = sanitize($data[3] ?? '');
                        $role = sanitize($data[4] ?? 'staff');
                        $email = sanitize($data[5] ?? '');
                        $phone = sanitize($data[6] ?? '');
                        $allowed_roles = ['super_admin','admin','principal','vice_principal','hod','staff'];
                        if (!in_array($role, $allowed_roles)) $role = 'staff';
                        if (empty($emp_id) || $dept_id < 1 || empty($name)) throw new Exception('emp_id, department_id, name required');
                        $pass = password_hash('123456', PASSWORD_DEFAULT);
                        $sql = "INSERT INTO employees (emp_id, department_id, name, designation, role, email, phone, password) VALUES ('$emp_id', $dept_id, '$name', '$designation', '$role', '$email', '$phone', '$pass')";
                        $r = $conn->query($sql);
                        if ($r === false) throw new Exception('DB error: ' . $conn->error);
                        if ($conn->affected_rows > 0) $success++; else throw new Exception('Insert failed (possible duplicate emp_id)');
                    } elseif ($type == 'subjects') {
                        $name = sanitize($data[0] ?? '');
                        $code = sanitize($data[1] ?? '');
                        $dept_id = intval($data[2] ?? 0);
                        $credits = intval($data[3] ?? 3);
                        $hours = intval($data[4] ?? 3);
                        $year = sanitize($data[5] ?? '');
                        $sem = intval($data[6] ?? 0);
                        $sem_mode = sanitize($data[7] ?? '');
                        $is_common = intval($data[8] ?? 0);
                        $valid_years = ['I','II','III'];
                        $valid_modes = ['odd','even'];
                        if (!in_array($year, $valid_years)) $year = 'I';
                        if ($sem < 1 || $sem > 6) $sem = 1;
                        if (!in_array($sem_mode, $valid_modes)) $sem_mode = 'odd';
                        if (empty($name) || $dept_id < 1) throw new Exception('Name and department_id required');
                        $sql = "INSERT INTO subjects (name, code, department_id, credits, lecture_hours_per_week, year, sem, sem_mode, is_common) VALUES ('$name', '$code', $dept_id, $credits, $hours, '$year', $sem, '$sem_mode', $is_common)";
                        $res = $conn->query($sql);
                        if ($res === false) throw new Exception('DB error: ' . $conn->error);
                        if ($conn->affected_rows > 0) $success++; else throw new Exception('Insert failed (0 rows affected)');
                    } elseif ($type == 'classes') {
                        $name = sanitize($data[0] ?? '');
                        $dept_id = intval($data[1] ?? 0);
                        $batch = sanitize($data[2] ?? '');
                        $section = sanitize($data[3] ?? 'A');
                        if (empty($name) || $dept_id < 1) throw new Exception('Name and department_id required');
                        $sql = "INSERT INTO classes (name, department_id, batch_year, section) VALUES ('$name', $dept_id, '$batch', '$section')";
                        $r = $conn->query($sql);
                        if ($r === false) throw new Exception('DB error: ' . $conn->error);
                        if ($conn->affected_rows > 0) $success++; else throw new Exception('Insert failed');
                    }
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "Row $line: " . $e->getMessage();
                }
            }
            $total = $success + $failed;
            $dbg = "type=$type delim=" . ($delim==="\t"?"TAB":$delim) . " lines=$total_lines cols=$first_cols row=[$first_row] raw=[$first_raw]";
            $msg = $total > 0 ? "Upload complete: $success succeeded, $failed failed." : "No data rows found (read $total_lines lines, $dbg).";
        }
    }
}
?>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a href="?page=bulk_upload&type=departments" class="nav-link <?= $type=='departments'?'active':'' ?>">Departments</a></li>
    <li class="nav-item"><a href="?page=bulk_upload&type=employees" class="nav-link <?= $type=='employees'?'active':'' ?>">Staff</a></li>
    <li class="nav-item"><a href="?page=bulk_upload&type=subjects" class="nav-link <?= $type=='subjects'?'active':'' ?>">Subjects</a></li>
    <li class="nav-item"><a href="?page=bulk_upload&type=classes" class="nav-link <?= $type=='classes'?'active':'' ?>">Classes</a></li>
</ul>

<?php if ($msg): ?>
    <div class="alert alert-<?= $failed > 0 ? 'warning' : 'success' ?> alert-auto"><?= e($msg) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header-tabs">
        <h5><i class="bi bi-upload me-2" style="color:#667eea"></i>Upload <?= ucfirst($type) ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" class="row g-3" action="?page=bulk_upload&type=<?= e($type) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="upload_type" value="<?= e($type) ?>">
            <div class="col-md-6">
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                <div class="form-text">CSV or TSV file with header row (first row is skipped as header). Supports comma or tab delimiters.</div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" name="upload" value="1" class="btn btn-success"><i class="bi bi-upload me-1"></i>Upload CSV</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header-tabs">
        <h5><i class="bi bi-info-circle me-2" style="color:#667eea"></i>CSV Format for <?= ucfirst($type) ?></h5>
    </div>
    <div class="card-body">
        <?php if ($type == 'departments'): ?>
        <pre class="bg-light p-3 mb-3">name,code
Computer Science,CS
Information Technology,IT</pre>
        <a href="data:text/csv;charset=utf-8,name,code%0AComputer Science,CS%0AInformation Technology,IT%0A" download="departments_sample.csv" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download Sample CSV</a>

        <?php elseif ($type == 'employees'): ?>
        <pre class="bg-light p-3 mb-3">emp_id,department_id,name,designation,role,email,phone
EMP001,1,John Doe,Professor,hod,john@college.edu,9876543210
EMP002,1,Jane Smith,Lecturer,staff,jane@college.edu,9876543211</pre>
        <p class="text-muted small"><strong>Note:</strong> Default password is <code>123456</code>. <code>role</code> defaults to <code>staff</code>. Columns: emp_id*, department_id*, name*, designation, role, email, phone (<code>*</code> required).</p>
        <a href="data:text/csv;charset=utf-8,emp_id,department_id,name,designation,role,email,phone%0AEMP001,1,John Doe,Professor,hod,john@college.edu,9876543210%0AEMP002,1,Jane Smith,Lecturer,staff,jane@college.edu,9876543211%0A" download="employees_sample.csv" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download Sample CSV</a>

        <?php elseif ($type == 'subjects'): ?>
        <pre class="bg-light p-3 mb-3">name,code,department_id,credits,hours,year,sem,sem_mode
Data Structures,CS301,1,3,4,III,5,odd
Algorithms,CS302,1,3,4,III,5,odd
Java Programming,CS303,1,3,3,I,1,odd</pre>
        <p class="text-muted small"><strong>Note:</strong> <code>year</code>: I/II/III, <code>sem</code>: 1-6, <code>sem_mode</code>: odd/even. Defaults: credits=3, hours=3, year=I, sem=1, sem_mode=odd. Columns: name*, code, department_id*, credits, hours, year, sem, sem_mode (<code>*</code> required).</p>
        <a href="data:text/csv;charset=utf-8,name,code,department_id,credits,hours,year,sem,sem_mode%0AData Structures,CS301,1,3,4,III,5,odd%0AJava Programming,CS303,1,3,3,I,1,odd%0A" download="subjects_sample.csv" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download Sample CSV</a>

        <?php elseif ($type == 'classes'): ?>
        <pre class="bg-light p-3 mb-3">name,department_id,batch_year,section
CS-A,1,2024-2027,A
CS-B,1,2024-2027,B
IT-A,2,2024-2027,A</pre>
        <p class="text-muted small"><strong>Note:</strong> <code>batch_year</code> accepts range like <code>2024-2027</code> or single year. <code>section</code> defaults to <code>A</code>. Columns: name*, department_id*, batch_year, section (<code>*</code> required).</p>
        <a href="data:text/csv;charset=utf-8,name,department_id,batch_year,section%0ACS-A,1,2024-2027,A%0ACS-B,1,2024-2027,B%0A" download="classes_sample.csv" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download Sample CSV</a>
        <?php endif; ?>
    </div>
</div>
