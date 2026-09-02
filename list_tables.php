<?php
$conn = new PDO("mysql:host=127.0.0.1;dbname=dhp_system", "root");
$s = $conn->query("SHOW TABLES");
foreach ($s as $t) echo $t[0] . "\n";
?>