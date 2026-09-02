<?php
$conn = new PDO("mysql:host=127.0.0.1;dbname=dhp_system", "root");
$c = $conn->exec("DROP TABLE IF EXISTS admissions");
echo "Dropped admissions: " . ($c ? "yes" : "no") . "\n";
?>