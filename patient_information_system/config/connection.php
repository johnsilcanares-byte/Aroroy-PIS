<?php 
$host = "localhost";
$user = "root";
$password = "";
$db = "pms_db";

try {
  $con = new PDO("mysql:dbname=$db;port=3306;host=$host", $user, $password);
  $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  echo "Connection failed: ". $e->getMessage();
  exit;
}

// FIXED: Only start session if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}