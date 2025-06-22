<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    echo "Order ID not specified.";
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// DB connection
$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "newsletter_db";
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . htmlspecialchars($conn->connect_error));
}

// --- GET ORDER MAIN INFO ---
$stmt = $conn->prepare("SELECT o.order_id, o.checkout_date, o.checkout_time, o.total_amount, r.fullname, r.email
                        FROM `order` o
                        JOIN users u ON o.user_id = u.id
                        LEFT JOIN registers r ON u.register_id = r.id
                        WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo "Order not found.";
    exit();
}
$stmt->bind_result($oid, $checkout_date, $checkout_time, $total_amount, $fullname, $email);
$stmt->fetch();
$stmt->close();

// --- GET ORDER ITEMS ---
$stmt = $conn->prepare(
    "SELECT p.product_name, oi.price, oi.quantity
     FROM order_item oi
     JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = ?"
);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$order_items = [];
while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt->close();
$conn->close();

// If order has no items, show a clear error and stop
if (count($order_items) === 0) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Order Receipt - Sweet Haven Bakery</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600;700&display=swap');
            body { 
                font-family: 'Inter', Arial, sans-serif; 
                background: linear-gradient(135deg, #ffe6f3 0%, #fff5e5 100%);
                margin:0; padding:0;
                min-height: 100vh;
            }
            .receipt-container {
                background: #fff;
                max-width: 450px;
                margin: 45px auto 0 auto;
                border-radius: 20px;
                box-shadow: 0 8px 36px rgba(185, 69, 5, 0.15);
                padding: 38px 34px 32px 34px;
                border: 2px solid #e7b87e;
                position: relative;
                overflow: hidden;
            }
            h2 { font-family: 'Playfair Display', serif; color: #b94505; margin-bottom: 0.5em; text-align: center; }
            .receipt-header, .receipt-footer { text-align: center; margin-bottom: 1.5em;}
            .receipt-header img {
                max-width: 160px;
                margin-bottom: 18px;
                filter: drop-shadow(0 2px 8px #f3d7a6);
            }
            .receipt-info { margin-bottom: 1.2em;}
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 1em;}
            .items-table th, .items-table td { padding: 0.48em 0.3em; font-size: 1.01em;}
            .items-table th { background: #f3d7a6; color: #b94505; text-align: left;}
            .items-table td { border-bottom: 1px solid #f5e0c3;}
            .total-row td { font-weight: bold; color: #b94505; border: none; font-size: 1.09em;}
            .receipt-footer { margin-top: 2em; font-size: 1.06em; color: #7d5b2c;}
            .print-btn {
                background: linear-gradient(90deg, #e7b87e 0%, #d4a373 85%);
                color: #fff;
                border: none;
                border-radius: 8px;
                padding: 0.7rem 1.7rem;
                font-size: 1.12rem;
                font-weight: 700;
                cursor: pointer;
                margin-top: 1.2em;
                display: block;
                margin-left: auto;
                margin-right: auto;
            }
            .print-btn:hover { background: linear-gradient(90deg, #d4a373 10%, #e7b87e 100%);}
            @media (max-width: 540px) {
                .receipt-container { padding: 12px 6vw 18px 6vw; }
                .receipt-header img { max-width: 100px; }
            }
            .no-items-row {
                text-align: center;
                color: #b94505;
                font-style: italic;
            }
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <div class="receipt-header">
                <img src="images/LOGO.png" alt="Sweet Haven Bakery">
                <h2>Order Receipt</h2>
            </div>
            <div class="receipt-info">
                <strong>Name:</strong> <?= htmlspecialchars($fullname ? $fullname : 'N/A') ?><br>
                <strong>Email:</strong> <?= htmlspecialchars($email ? $email : 'N/A') ?><br>
                <strong>Date:</strong> <?= htmlspecialchars($checkout_date) ?><br>
                <strong>Time:</strong> <?= htmlspecialchars($checkout_time) ?><br>
                <strong>Order #:</strong> <?= htmlspecialchars($oid) ?>
            </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align:right;">Price</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="no-items-row" colspan="4">No items found for this order.<br>
                        <span style="color:#b94505;font-size:.95em;">This order may have been created accidentally or not completed.</span></td>
                    </tr>
                </tbody>
            </table>
            <div style="text-align:center;margin-top:2em;">
                <a href="orders.php" style="text-decoration:none;color:#fff;background:#b94505;padding:10px 24px;border-radius:7px;font-weight:700;">Back to My Orders</a>
            </div>
            <div class="receipt-footer">
                If you think this is an error, please contact support.<br>
                Thank you for visiting Sweet Haven Bakery!
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Receipt - Sweet Haven Bakery</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600;700&display=swap');
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            min-height: 100vh;
            min-width: 100vw;
            font-family: 'Inter', Arial, sans-serif;
            /* Stronger pink-peach gradient */
            background: linear-gradient(135deg, #ffd6e0 0%, #fff5e5 100%);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .receipt-container {
            background: rgba(255,255,255, 0.97); /* slightly see-through for more gradient pop */
            max-width: 430px;
            margin: 40px;
            border-radius: 22px;
            box-shadow: 0 8px 36px rgba(255, 176, 95, 0.15);
            padding: 38px 26px 32px 26px;
            border: 2px solid #ffd9ad;
            position: relative;
            overflow: hidden;
        }
        .ribbon {
            position: absolute;
            right: -88px;
            top: 28px;
            background: linear-gradient(95deg, #ffd8a6 0%, #ffb56a 98%);
            color: #b35e11;
            font-size: 1.1em;
            font-weight: 700;
            padding: 10px 92px 10px 44px;
            transform: rotate(23deg);
            box-shadow: 0 2px 8px rgba(255, 176, 95, 0.09);
            letter-spacing: 2.5px;
            z-index: 3;
            opacity: 0.95;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 1.5em;
        }
        .receipt-header img {
            max-width: 170px;
            margin-bottom: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 24px #ffe4c7;
            background: #fff;
        }
        h2 {
            font-family: 'Playfair Display', serif;
            color: #b35e11;
            margin-bottom: 0.3em;
            text-align: center;
            font-size: 2.1em;
            font-weight: 700;
        }
        .order-info {
            text-align: center;
            color: #a76a1b;
            font-size: 1.13em;
            margin-bottom: 1.2em;
            letter-spacing: 1px;
        }
        .receipt-details {
            background: #fff6ea;
            border-radius: 13px;
            padding: 13px 18px 11px 18px;
            margin-bottom: 1.4em;
            font-size: 1.09em;
            box-shadow: 0 2px 8px #ffe7c8;
        }
        .receipt-details strong {
            color: #c46a16;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px #ffd9ad;
        }
        .items-table th, .items-table td {
            padding: 0.6em 0.4em;
            font-size: 1.05em;
        }
        .items-table th {
            background: #ffd8a6;
            color: #c46a16;
            text-align: left;
            font-weight: 700;
            letter-spacing: 1.1px;
        }
        .items-table td {
            border-bottom: 1px solid #ffeee2;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .total-row td {
            font-weight: bold;
            color: #bf670e;
            border: none;
            font-size: 1.19em;
            background: #fff6ea;
        }
        .total-label {
            text-align: right;
            font-weight: 700;
        }
        .total-amount {
            text-align: right;
            color: #d16e00;
            font-weight: 700;
            font-size: 1.13em;
        }
        .print-btn {
            background: linear-gradient(90deg, #ffbb71 0%, #ffae6f 85%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 0.9rem 2.2rem;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            margin: 18px auto 10px auto;
            display: block;
            box-shadow: 0 3px 10px #ffd9ad;
            letter-spacing: 1.5px;
            transition: background .18s;
        }
        .print-btn:hover {
            background: linear-gradient(90deg, #ffae6f 10%, #ffbb71 100%);
        }
        .divider {
            border: none;
            border-top: 1.5px dashed #ffcf99;
            margin: 2em 0 1.3em 0;
        }
        .thanks {
            font-family: 'Playfair Display', serif;
            color: #b35e11;
            font-size: 1.17em;
            margin-bottom: 0.3em;
            letter-spacing: 1.2px;
            text-align: center;
            font-weight: 700;
        }
        .receipt-footer {
            margin-top: 1.2em;
            font-size: 1.07em;
            color: #b35e11;
            text-align: center;
        }
        .support {
            font-size: 1em;
            color: #a76a1b;
            text-align: center;
            margin-top: 0.3em;
        }
        .support a {
            color: #b35e11;
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .receipt-container {
                padding: 13px 2vw 18px 2vw;
            }
            .receipt-header img { max-width: 88vw; }
            h2 { font-size: 1.35em; }
            .ribbon { font-size: .91em; padding: 7px 28vw 7px 18px; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="ribbon">SWEET HAVEN BAKERY</div>
        <div class="receipt-header">
            <img src="images/LOGO.png" alt="Sweet Haven Bakery">
        </div>
        <h2>Order Receipt</h2>
        <div class="order-info">
            Order #<?= htmlspecialchars($oid) ?> &mdash; <?= htmlspecialchars($checkout_date) ?> <?= htmlspecialchars($checkout_time) ?>
        </div>
        <div class="receipt-details">
            <strong>Name:</strong> <?= htmlspecialchars($fullname ? $fullname : 'N/A') ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($email ? $email : 'N/A') ?>
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align:right;">Price</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td style="text-align:right;">₱<?= number_format($item['price'], 2) ?></td>
                    <td style="text-align:center;"><?= intval($item['quantity']) ?></td>
                    <td style="text-align:right;">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3" class="total-label">Total:</td>
                    <td class="total-amount">₱<?= number_format($total_amount, 2) ?></td>
                </tr>
            </tbody>
        </table>
        <button class="print-btn" onclick="window.print()">Print Receipt</button>
        <hr class="divider">
        <div class="thanks">Thank you for ordering at Sweet Haven!</div>
        <div class="receipt-footer">
            Please keep this receipt for your records.<br>
            <span class="support">For support, contact us: <a href="mailto:support@sweethaven.com">support@sweethaven.com</a></span>
        </div>
    </div>
</body>
</html>