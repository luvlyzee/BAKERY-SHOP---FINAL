<?php
$host = 'localhost';
$db = 'newsletter_db';
$user = 'root';
$pass = '';

// Connect to MySQL
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Create DB if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if (isset($_POST['newsletter-email'])) {
  $email = trim($_POST['newsletter-email']);
  
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "invalid";
    exit;
  }

  $stmt = $conn->prepare("INSERT IGNORE INTO subscribers (email) VALUES (?)");
  $stmt->bind_param("s", $email);
  
  if ($stmt->execute()) {
    echo "success";
  } else {
    echo "fail";
  }

  $stmt->close();
} else {
  echo "fail";
}

$conn->close();
?>