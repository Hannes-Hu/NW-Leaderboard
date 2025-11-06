<?php
define('DB_SERVER', 'localhost'); 
define('DB_USERNAME', 'u578436281_draft');   
define('DB_PASSWORD', 'F>w7hDzKap1|');       
define('DB_NAME', 'u578436281_draft'); 

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");
?>
