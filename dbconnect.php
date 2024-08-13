<?php
$servername = "158.101.123.136";
$username = "admin";
$password = "@Mysqlse2024";

// Create connection without specifying a database
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>