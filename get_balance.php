<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "newsletter_db";
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

$user_balance = 0.00;
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($user_balance);
$stmt->fetch();
$stmt->close();
$conn->close();

echo json_encode(['balance' => number_format($user_balance, 2)]);
?>