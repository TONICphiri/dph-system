<?php
$conn = new PDO("mysql:host=127.0.0.1", "root");
$conn->exec("DROP DATABASE IF EXISTS dhp_system");
$conn->exec("CREATE DATABASE dhp_system");
$conn->exec("USE dhp_system");
echo "Database recreated successfully\n";
?>