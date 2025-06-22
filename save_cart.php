<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = $data['items'];

$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "newsletter_db";
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'details' => $conn->connect_error]);
    exit();
}

$conn->begin_transaction();

try {
    // 1. Find or create the cart for the user
    $stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($cart_id);
    if ($stmt->fetch()) {
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $cart_id = $stmt->insert_id;
        $stmt->close();
    }

    // 2. Remove old cart items
    $stmt = $conn->prepare("DELETE FROM cart_item WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $stmt->close();

    // 3. Add new cart items
    $insert_stmt = $conn->prepare("INSERT INTO cart_item (cart_id, product_id, quantity) VALUES (?, ?, ?)");
    foreach ($cart_items as $item) {
        $product_name = $item['name'];
        $qty = $item['quantity'];
        // Get product_id from name
        $pstmt = $conn->prepare("SELECT product_id FROM products WHERE product_name = ?");
        $pstmt->bind_param("s", $product_name);
        $pstmt->execute();
        $pstmt->bind_result($product_id);
        if ($pstmt->fetch()) {
            $insert_stmt->bind_param("iii", $cart_id, $product_id, $qty);
            $insert_stmt->execute();
        }
        $pstmt->close();
    }
    $insert_stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'cart_id' => $cart_id]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Cart save failed', 'details' => $e->getMessage()]);
}
$conn->close();
?>