<?php
requireAdmin();
$msg = '';
$type = $_GET['type'] ?? 'departments';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $file = $_FILES['csv_file']['tmp_name'] ?? null;
    if ($file) {
        $handle = fopen($file, "r");
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if ($count > 0) {
                if ($type == 'departments') {
                    $c0 = $data[0] ?? '';
                    $c1 = $data[1] ?? '';
                    if (!empty($c0) && !empty($c1))
                        $conn->query("INSERT INTO departments (name, code) VALUES ('$c0', '$c1')");
                } elseif ($type == 'employees') {
                    $emp_id = $data[0] ?? '';
                    $dept_id = intval($data[1] ?? 0);
                    $name = $data[2] ?? '';
                    $designation = $data[3] ?? '';
                    if (!empty($emp_id) && $dept_id > 0 && !empty($name)) {
                        $pass = password_hash('123456', PASSWORD_DEFAULT);
                        $conn->query("INSERT INTO employees (emp_id, department_id, name, designation, password) VALUES ('$emp_id', $dept_id, '$name', '$designation', '$pass')");
                    }
                } elseif ($type == 'subjects') {
                    $name = $data[0] ?? '';
                    $code = $data[1] ?? '';
                    $dept_id = intval($data[2] ?? 0);
                    $credits = intval($data[3] ?? 3);
                    $hours = intval($data[4] ?? 3);
                    if (!empty($name) && $dept_id > 0)
                        $conn->query("INSERT INTO subjects (name, code, department_id, credits, lecture_hours_per_week) VALUES ('$name', '$code', $dept_id, $credits, $hours)");
                } elseif ($type == 'classes') {
                    $name = $data[0] ?? '';
                    $dept_id = intval($data[1] ?? 0);
                    $batch = intval($data[2] ?? 2024);
                    $section = $data[3] ?? 'A';
                    if (!empty($name) && $dept_id > 0)
                        $conn->query("INSERT INTO classes (name, department_id, batch_year, section) VALUES ('$name', $dept_id, $batch, '$section')");
                }
            }
            $count++;
        }
        $msg = ucfirst($type) . " uploaded successfully";
        fclose($handle);
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
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="card mb-3">
    <h5>Upload <?= ucfirst($type) ?></h5>
    <form method="POST" enctype="multipart/form-data" class="row g-3">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <div class="col-md-3">
            <button type="submit" name="upload" value="1" class="btn btn-success"><i class="bi bi-upload me-1"></i>Upload CSV</button>
        </div>
    </form>
</div>

<div class="card">
    <h5>CSV Format for <?= ucfirst($type) ?></h5>
    <?php if ($type == 'departments'): ?>
    <pre class="bg-light p-3">name,code
Computer Science,CS
Information Technology,IT
Electronics,EC</pre>
    <?php elseif ($type == 'employees'): ?>
    <pre class="bg-light p-3">emp_id,department_id,name,designation
EMP001,1,John Doe,Professor
EMP002,1,Jane Smith,Lecturer</pre>
    <?php elseif ($type == 'subjects'): ?>
    <pre class="bg-light p-3">name,code,department_id,credits,hours
Data Structures,CS301,1,3,4
Algorithms,CS302,1,3,4</pre>
    <?php elseif ($type == 'classes'): ?>
    <pre class="bg-light p-3">name,department_id,batch_year,section
CS-A,1,2024,A
CS-B,1,2024,B
IT-A,2,2024,A</pre>
    <?php endif; ?>
</div>
