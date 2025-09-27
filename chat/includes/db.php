<?php
$host = "162.214.80.164";
$user = "ensplpmy_hudaif";
$pass = "abd527-157";
$dbname = "ensplpmy_cl";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
?>