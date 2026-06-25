<?php
$conn = new mysqli(
    'localhost',
    'REDACTED',
    'REDACTED',
    'college_timetable'
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = file_get_contents(__DIR__ . '/../db/setup.sql');
if ($conn->multi_query($sql)) {
    echo "Database setup completed successfully!";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();