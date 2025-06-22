<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
  echo '<div style="text-align:center; color:#c0392b;">Not logged in.</div>';
  exit;
}

// DB connection - update to match your config
$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "newsletter_db";
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) die("DB error: " . $conn->connect_error);

$order_history = [];
$orders_q = $conn->prepare("SELECT o.order_id, o.total_amount, o.checkout_date, o.checkout_time, o.order_status FROM `order` o WHERE o.user_id=? ORDER BY o.checkout_date DESC, o.checkout_time DESC");
$orders_q->bind_param("i", $user_id);
$orders_q->execute();
$orders_res = $orders_q->get_result();
while ($row = $orders_res->fetch_assoc()) {
    $item_q = $conn->prepare("SELECT p.product_name, oi.quantity FROM order_item oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id=?");
    $item_q->bind_param("i", $row['order_id']);
    $item_q->execute();
    $items_res = $item_q->get_result();
    $items = [];
    while ($i = $items_res->fetch_assoc()) {
        $items[] = [
            'name' => $i['product_name'],
            'qty' => $i['quantity']
        ];
    }
    $item_q->close();
    $order_history[] = [
        'order_id' => $row['order_id'],
        'total' => $row['total_amount'],
        'checkout_date' => $row['checkout_date'],
        'checkout_time' => $row['checkout_time'],
        'status' => $row['order_status'],
        'items' => $items
    ];
}
$orders_q->close();
$conn->close();
?>

<table style="width:100%; font-size:1em; border-collapse:collapse;">
  <thead>
    <tr style="background:#ffe4c4;">
      <th style="padding:7px 4px;">Order #</th>
      <th style="padding:7px 4px;">Date & Time</th>
      <th style="padding:7px 4px;">Items</th>
      <th style="padding:7px 4px;">Qty</th>
      <th style="padding:7px 4px;">Total</th>
      <th style="padding:7px 4px;">Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($order_history as $ord): ?>
      <tr style="border-bottom:1px solid #f6d7ae;">
        <td style="padding:6px 5px; text-align:center;"><?= $ord['order_id'] ?></td>
        <td style="padding:6px 5px;">
          <?= htmlspecialchars($ord['checkout_date']) ?>
          <?= $ord['checkout_time'] ? htmlspecialchars($ord['checkout_time']) : '' ?>
        </td>
        <td style="padding:6px 5px;"><?php foreach($ord['items'] as $it) echo htmlspecialchars($it['name']).'<br>'; ?></td>
        <td style="padding:6px 5px;"><?php foreach($ord['items'] as $it) echo intval($it['qty']).'<br>'; ?></td>
        <td style="padding:6px 5px;">₱<?= number_format($ord['total'],2) ?></td>
        <td style="padding:6px 5px; color:
          <?= $ord['status'] == "Completed" || $ord['status'] == "Delivered" ? "#008b13" : ($ord['status'] == "Pending" ? "#c39c1e" : "#c0392b") ?>">
          <?= htmlspecialchars($ord['status']) ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($order_history)): ?>
      <tr><td colspan="6" style="text-align:center; color:#b94505;">No orders yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>