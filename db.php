<?php

$servername = "localhost";
$username = "employeeid";
$password = "password";
$dbname = "employeedata";
$conn = new mysqli($servername, 
            $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failure: " 
        . $conn->connect_error);
} 

$sql = "CREATE DATABASE employeeData";
if ($conn->query($sql) === TRUE) {
    echo "New Employee registered";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>