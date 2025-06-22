<?php
// display_cart.php: Display the current user's cart and its items

session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "<div>Please <a href='login.php'>log in</a> to view your cart.</div>";
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'newsletter_db');
if ($conn->connect_error) {
    echo "<div>Database connection failed: " . htmlspecialchars($conn->connect_error) . "</div>";
    exit;
}

// 1. Get the user's cart_id (assuming one cart per user)
$sql = "SELECT cart_id FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($cart_id);
$stmt->fetch();
$stmt->close();

if (!$cart_id) {
    echo "<div>Your cart is empty.</div>";
    $conn->close();
    exit;
}

// 2. Get cart items with product info
$sql = "SELECT ci.quantity, p.product_name, p.price, p.product_id
        FROM cart_item ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div>Your cart is empty.</div>";
} else {
    echo "<table border='1' cellpadding='7' style='border-collapse:collapse;'>";
    echo "<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr>";
    $total = 0;
    while ($item = $result->fetch_assoc()) {
        $subtotal = $item['price'] * $item['quantity'];
        $total += $subtotal;
        echo "<tr>";
        echo "<td>" . htmlspecialchars($item['product_name']) . "</td>";
        echo "<td>₱" . number_format($item['price'], 2) . "</td>";
        echo "<td>" . intval($item['quantity']) . "</td>";
        echo "<td>₱" . number_format($subtotal, 2) . "</td>";
        echo "</tr>";
    }
    echo "<tr><td colspan='3' align='right'><b>Total:</b></td><td><b>₱" . number_format($total, 2) . "</b></td></tr>";
    echo "</table>";
}

$stmt->close();
$conn->close();
?>