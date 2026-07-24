<?php
require_once __DIR__ . '/../config.php';

$migrations = [
    "ALTER TABLE subjects ADD COLUMN IF NOT EXISTS is_common TINYINT DEFAULT 0 COMMENT '1=common paper across departments'",
    "INSERT IGNORE INTO departments (name, code) VALUES ('Languages', 'LAN')",
];

echo "<h2>Running Migrations...</h2><ul>";
foreach ($migrations as $sql) {
    echo "<li>Executing: " . htmlspecialchars(substr($sql, 0, 80)) . "... ";
    if ($conn->query($sql)) {
        echo "<span style='color:green'>OK</span>";
    } else {
        echo "<span style='color:red'>" . $conn->error . "</span>";
    }
    echo "</li>";
}
echo "</ul><p><a href='../dashboard.php?page=common_paper_allocation'>Go to Common Paper Allocation</a></p>";
