<?php
$conn = new PDO("mysql:host=127.0.0.1;dbname=dhp_system", "root", "");
// List all tables
$tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in database:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}
// Try to truncate each table that exists and has data
foreach ($tables as $table) {
    if (in_array($table, ['patients', 'encounters', 'admissions', 'prescriptions', 'vitals', 'inventory', 'sync_queues', 'migrations'])) {
        try {
            $conn->exec("SET FOREIGN_KEY_CHECKS=0");
            $conn->exec("TRUNCATE `$table`");
            echo "Truncated $table\n";
            $conn->exec("SET FOREIGN_KEY_CHECKS=1");
        } catch (Exception $e) {
            echo "Error truncating $table: " . $e->getMessage() . "\n";
        }
    }
}
echo "Done\n";
?>