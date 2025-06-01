<?php
$host = "localhost";
$user = "root";
$password = ""; // use your actual DB password
$dbname = "cvsuinventory";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
