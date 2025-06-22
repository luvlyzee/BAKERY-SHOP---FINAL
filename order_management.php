<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "newsletter_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all orders with customer name and order items
$orders = [];
$sql = "SELECT o.order_id, o.user_id, o.total_amount AS total, o.status, r.full_name
        FROM `order` o
        JOIN `registers` r ON o.user_id = r.user_id
        ORDER BY o.order_id DESC";
$result = $conn->query($sql);
while ($order = $result->fetch_assoc()) {
    $order_id = $order['order_id'];
    // Get order items and product names for this order
    $sql_items = "SELECT oi.quantity, p.product_name
                  FROM order_item oi
                  JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = ?";
    $stmt = $conn->prepare($sql_items);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $itemsResult = $stmt->get_result();

    $productsArr = [];
    $quantitiesArr = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $productsArr[] = $item['product_name'];
        $quantitiesArr[] = $item['quantity'];
    }
    $stmt->close();

    $orders[] = [
        'order_id' => $order_id,
        'customer' => $order['full_name'],
        'products' => $productsArr,
        'quantities' => $quantitiesArr,
        'total' => $order['total'],
        'status' => $order['status'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management</title>
    <style>
        table {width: 100%; border-collapse: collapse;}
        th, td {padding: 10px; border: 1px solid #eee;}
        th {background: #fbcfe8;}
        tr:nth-child(even) {background: #fdf2f8;}
    </style>
</head>
<body>
    <h1>Order Management</h1>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Products</th>
                <th>Quantities</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($orders as $o): ?>
            <tr>
                <td><?= $o['order_id'] ?></td>
                <td><?= htmlspecialchars($o['customer']) ?></td>
                <td>
                    <?php foreach($o['products'] as $p) echo htmlspecialchars($p) . "<br>"; ?>
                </td>
                <td>
                    <?php foreach($o['quantities'] as $q) echo intval($q) . "<br>"; ?>
                </td>
                <td>₱ <?= number_format($o['total'], 2) ?></td>
                <td><?= htmlspecialchars($o['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>