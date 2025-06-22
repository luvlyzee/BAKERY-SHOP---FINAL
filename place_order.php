<?php
session_start();
date_default_timezone_set('Asia/Manila'); // <-- Set your local timezone here!
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['items']) || !is_array($data['items']) || !isset($data['total'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = $data['items'];
$total = $data['total'];
$checkout_date = date('Y-m-d');

// --- Always use current server time in 24-hour format (Asia/Manila) ---
$checkout_time = date('H:i:s'); // This is safe and always accurate

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
    // --- CHECK IF USER HAS ENOUGH BALANCE ---
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($current_balance);
    $stmt->fetch();
    $stmt->close();

    if ($current_balance < $total) {
        throw new Exception("Insufficient balance.");
    }

    // --- PREVENT DUPLICATE ORDER (anti-double-submit, within 30 seconds, same total, same user) ---
    $stmt = $conn->prepare("SELECT order_id FROM `order` WHERE user_id = ? AND total_amount = ? AND checkout_date = ? AND ABS(TIMESTAMPDIFF(SECOND, CONCAT(checkout_date, ' ', checkout_time), NOW())) < 30");
    $stmt->bind_param("ids", $user_id, $total, $checkout_date);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        throw new Exception("Duplicate order detected. Please do not submit twice.");
    }
    $stmt->close();

    // --- 1. Create CART (for record/history)
    $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_id = $stmt->insert_id;
    $stmt->close();

    // --- 2. Add CART ITEMS (for record/history)
    $stmt = $conn->prepare("INSERT INTO cart_item (cart_id, product_id, quantity) VALUES (?, ?, ?)");
    if (!$stmt) throw new Exception($conn->error);

    foreach ($cart_items as $item) {
        $product_name = $item['name'];
        $qty = $item['quantity'];
        $pstmt = $conn->prepare("SELECT product_id FROM products WHERE product_name = ?");
        if (!$pstmt) throw new Exception($conn->error);
        $pstmt->bind_param("s", $product_name);
        $pstmt->execute();
        $pstmt->bind_result($product_id);
        $found = $pstmt->fetch();
        $pstmt->close();

        if (!$found) {
            throw new Exception("Product not found: " . htmlspecialchars($product_name));
        }

        $stmt->bind_param("iii", $cart_id, $product_id, $qty);
        $stmt->execute();
    }
    $stmt->close();

    // --- 3. Create ORDER (for purchase record)
    $stmt = $conn->prepare("INSERT INTO `order` (user_id, total_amount, checkout_time, checkout_date) VALUES (?, ?, ?, ?)");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("idss", $user_id, $total, $checkout_time, $checkout_date);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // --- 4. Add ORDER ITEMS (for purchase record + update stock)
    $stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
    if (!$stmt) throw new Exception($conn->error);

    foreach ($cart_items as $item) {
        $product_name = $item['name'];
        $item_price = $item['price'];
        $qty = $item['quantity'];
        $pstmt = $conn->prepare("SELECT product_id FROM products WHERE product_name = ?");
        if (!$pstmt) throw new Exception($conn->error);

        $pstmt->bind_param("s", $product_name);
        $pstmt->execute();
        $pstmt->bind_result($product_id);
        $found = $pstmt->fetch();
        $pstmt->close();

        if (!$found) {
            throw new Exception("Product not found: " . htmlspecialchars($product_name));
        }

        $stmt->bind_param("iidi", $order_id, $product_id, $item_price, $qty);
        $stmt->execute();

        // --- SUBTRACT STOCK FROM PRODUCTS TABLE ---
        $ustmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
        if (!$ustmt) throw new Exception($conn->error);
        $ustmt->bind_param("ii", $qty, $product_id);
        $ustmt->execute();
        $ustmt->close();
    }
    $stmt->close();

    // --- 5. Create PAYMENT (for purchase record)
    $stmt = $conn->prepare("INSERT INTO payment (user_id, order_id, amount) VALUES (?, ?, ?)");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("iid", $user_id, $order_id, $total);
    $stmt->execute();
    $stmt->close();

    // --- 6. UPDATE USER BALANCE ---
    $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("di", $total, $user_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id, 'cart_id' => $cart_id]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'error' => 'Order failed',
        'details' => $e->getMessage(),
        'mysqli_error' => $conn->error
    ]);
}
$conn->close();
?>