<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    if ($amount < 1) {
        echo json_encode(["success" => false, "message" => "Invalid amount"]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $host = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $dbname = "newsletter_db";
    $conn = new mysqli($host, $dbuser, $dbpass, $dbname);
    if ($conn->connect_error) {
        echo json_encode(["success" => false, "message" => "DB error"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        $stmt->close();
        $conn->close();
        echo json_encode(["success" => false, "message" => "Failed to update balance."]);
        exit;
    }
    $stmt->close();

    // Get new balance
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $new_balance = 0.00;
    if ($result && $row = $result->fetch_assoc()) {
        $new_balance = $row['balance'];
    }
    $stmt->close();
    $conn->close();
    echo json_encode(["success" => true, "balance" => $new_balance]);
}
?>