<?php
// db.php - Database connection file
$servername = "localhost";
$username = "root"; // Change to your MySQL username
$password = "root"; // Change to your MySQL password
$dbname = "blog_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>