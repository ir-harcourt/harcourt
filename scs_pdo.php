<?php
// alter table `xinventory` rename to `inventory`
set_time_limit(600);
//ini_set("memory_limit","512M");
$pdo=new PDO("mysql:host=localhost;dbname=wp_harcourt", "harcourt", "npE3dglcJYckOtpgNtGW");

$query=file_get_contents("scs_sql/wp_inventory.sql");
print "<br>query: " . strlen($query);
$stmt=$pdo->prepare($query);
$stmt_status=$stmt->execute();
print "<br>status: " .  $stmt_status;
?>