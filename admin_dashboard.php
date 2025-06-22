<?php
// Set your preferred timezone so "today" matches your local time (e.g., Asia/Manila)
date_default_timezone_set('Asia/Manila');

// Restrict dashboard to logged-in admins only
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit();
}
$admin_username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Administrator';

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "newsletter_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $category = $_POST['category'];
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $img = $_POST['img'];
    $stmt = $conn->prepare("INSERT INTO products (category, product_name, price, stock, img) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $category, $product_name, $price, $stock, $img);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $category = $_POST['category'];
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $img = $_POST['img'];
    $stmt = $conn->prepare("UPDATE products SET category=?, product_name=?, price=?, stock=?, img=? WHERE product_id=?");
    $stmt->bind_param("ssdisi", $category, $product_name, $price, $stock, $img, $product_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Order Status Update (save status to database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE `order` SET order_status=? WHERE order_id=?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// INVENTORY SECTION
// Handle Add Inventory Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_inventory'])) {
    $ingredient = $_POST['ingredient'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $measurement = $_POST['measurement'];
    $expiry_date = $_POST['expiry_date'];
    $stmt = $conn->prepare("INSERT INTO inventory (ingredient, category, quantity, measurement, expiry_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $ingredient, $category, $quantity, $measurement, $expiry_date);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Edit Inventory Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_inventory'])) {
    $id = $_POST['id'];
    $ingredient = $_POST['ingredient'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $measurement = $_POST['measurement'];
    $expiry_date = $_POST['expiry_date'];
    $stmt = $conn->prepare("UPDATE inventory SET ingredient=?, category=?, quantity=?, measurement=?, expiry_date=? WHERE id=?");
    $stmt->bind_param("ssissi", $ingredient, $category, $quantity, $measurement, $expiry_date, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Delete Inventory Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inventory'])) {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle Delete Order (only if status is Completed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    // Fetch order status from database
    $status_res = $conn->prepare("SELECT order_status FROM `order` WHERE order_id=?");
    $status_res->bind_param("i", $order_id);
    $status_res->execute();
    $status_result = $status_res->get_result();
    $row = $status_result->fetch_assoc();
    $status_res->close();
    if ($row && $row['order_status'] === 'Completed') {
        $stmt = $conn->prepare("DELETE FROM `order` WHERE order_id=?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_dashboard.php");
    exit();
}

// Get all products
$products = [];
$sql = "SELECT * FROM products";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Fetch inventory items
$inventory = [];
$inv_sql = "SELECT * FROM inventory";
$inv_result = $conn->query($inv_sql);
while ($row = $inv_result->fetch_assoc()) {
    $inventory[] = $row;
}

// REAL ORDERS FROM DATABASE (with correct fullname fetching logic, and NO duplicate orders)
$orders = [];
$order_ids = [];
$order_sql = "SELECT o.order_id, o.user_id, o.total_amount, o.checkout_date, o.checkout_time, o.order_status
    FROM `order` o 
    ORDER BY o.order_id DESC";
$order_res = $conn->query($order_sql);
if ($order_res) {
    while ($order = $order_res->fetch_assoc()) {
        $order_id = $order['order_id'];
        // Prevent duplicate order display
        if (in_array($order_id, $order_ids)) continue;
        $order_ids[] = $order_id;

        $user_id = $order['user_id'];
        $total = $order['total_amount'];

        // Get customer full name (users -> register_id -> registers)
        $cust_sql = $conn->prepare('
            SELECT r.fullname 
            FROM users u 
            JOIN registers r ON u.register_id = r.id 
            WHERE u.id = ?
        ');
        $cust_sql->bind_param("i", $user_id);
        $cust_sql->execute();
        $cust_result = $cust_sql->get_result();
        $cust_row = $cust_result->fetch_assoc();
        $customer_name = $cust_row ? $cust_row['fullname'] : 'Unknown';
        $cust_sql->close();

        // Get order items
        $item_sql = $conn->prepare("SELECT oi.product_id, oi.quantity, p.product_name
            FROM order_item oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?");
        $item_sql->bind_param("i", $order_id);
        $item_sql->execute();
        $item_result = $item_sql->get_result();
        $items = [];
        while ($item_row = $item_result->fetch_assoc()) {
            $items[] = [
                'product_name' => $item_row['product_name'],
                'quantity' => $item_row['quantity']
            ];
        }
        $item_sql->close();

        // Status logic: fetch from DB, default "Pending"
        $status = isset($order['order_status']) ? $order['order_status'] : 'Pending';

        // Combine checkout_date and checkout_time for display
        $checkout_datetime = $order['checkout_date'] . ' ' . $order['checkout_time'];

        $orders[] = [
            'order_id' => $order_id,
            'customer_name' => $customer_name,
            'items' => $items,
            'total' => $total,
            'status' => $status,
            'checkout_datetime' => $checkout_datetime
        ];
    }
}

// For dashboard's "Recent Orders" (last 5)
$recentOrders = array_slice($orders, 0, 5);

// --------- REPORTS & SUMMARY SECTION -----------

// Group sales by date (YYYY-MM-DD format)
$sales_by_day = [];
$orders_by_day = [];

// We'll assume your `order` table has checkout_date and checkout_time fields
$report_sql = "SELECT checkout_date as order_date, 
                      SUM(total_amount) as total_sales, 
                      COUNT(*) as total_orders
               FROM `order`
               GROUP BY order_date
               ORDER BY order_date DESC";
$report_res = $conn->query($report_sql);
if ($report_res) {
    while ($row = $report_res->fetch_assoc()) {
        $date = $row['order_date'];
        $sales_by_day[$date] = $row['total_sales'];
        $orders_by_day[$date] = $row['total_orders'];
    }
}

// ---------- MODIFICATION: Dynamic 'Total Sales' by any date ----------
// Check if a date filter is set (GET parameter), default to today
$today = date('Y-m-d');
$selected_date = isset($_GET['sales_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['sales_date']) ? $_GET['sales_date'] : $today;

// Query for total sales for that specific date
$sales_stmt = $conn->prepare("SELECT SUM(total_amount) as total_sales, COUNT(*) as total_orders FROM `order` WHERE checkout_date = ?");
$sales_stmt->bind_param("s", $selected_date);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result()->fetch_assoc();
$selected_sales = isset($sales_result['total_sales']) ? $sales_result['total_sales'] : 0;
$selected_orders = isset($sales_result['total_orders']) ? $sales_result['total_orders'] : 0;
$sales_stmt->close();

// If no filter used, fallback (for reports table etc.)
$today_sales = isset($sales_by_day[$today]) ? $sales_by_day[$today] : 0;
$today_orders = isset($orders_by_day[$today]) ? $orders_by_day[$today] : 0;
?>

<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bakery Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    ::-webkit-scrollbar {height: 8px;width: 8px;}
    ::-webkit-scrollbar-thumb {background: #f87171; border-radius: 8px;}
    ::-webkit-scrollbar-track {background: #ffe4e6;}
    .hidden { display: none !important; }
  </style>
</head>
<body class="bg-rose-50 min-h-screen font-sans flex">

  <!-- Sidebar -->
  <aside class="w-72 bg-pink-200 p-6 flex flex-col justify-between sticky top-0 h-screen shadow-lg">
    <div>
      <h1 class="text-3xl font-extrabold text-pink-900 mb-12 tracking-wide flex items-center gap-2">
        <span>🍰</span> Sweet Haven
      </h1>
      <nav class="flex flex-col gap-5 text-pink-900 text-lg font-semibold">
        <button onclick="showSection('dashboard')" id="nav-dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-pink-300 transition font-bold bg-pink-300">
          <span>🏠</span> Dashboard
        </button>
        <button onclick="showSection('products')" id="nav-products" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-pink-300 transition">
          <span>🧁</span> Product Management
        </button>
        <button onclick="showSection('orders')" id="nav-orders" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-pink-300 transition">
          <span>📦</span> Order Management
        </button>
        <button onclick="showSection('inventory')" id="nav-inventory" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-pink-300 transition">
          <span>📋</span> Inventory Monitoring
        </button>
        <button onclick="showSection('reports')" id="nav-reports" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-pink-300 transition">
          <span>📊</span> Reports & Summary
        </button>
      </nav>
    </div>
    <div class="bg-white rounded-xl shadow p-4 flex items-center gap-4 cursor-pointer hover:shadow-md transition">
      <img src="https://via.placeholder.com/50" alt="Profile" class="w-12 h-12 rounded-full object-cover" />
      <div>
        <p class="font-semibold text-pink-900"><?= htmlspecialchars($admin_username) ?></p>
        <p class="text-xs text-pink-700">Administrator</p>
        <a href="admin_login.php?logout=1" class="mt-1 text-pink-600 text-xs hover:underline">Logout</a>
      </div>
    </div>
  </aside>

  <!-- Main Content Area -->
  <main class="flex-1 p-8 overflow-y-auto">

    <!-- Top Navbar -->
    <div class="flex justify-between items-center mb-8">
      <!-- Top right icons removed -->
    </div>

    <!-- Dashboard Overview -->
    <section id="dashboard" class="section">
      <h2 class="text-3xl font-extrabold text-pink-900 mb-8">Welcome back, <?= htmlspecialchars($admin_username) ?>! </h2>
      <!-- Summary Cards -->
      <form method="get" class="flex flex-wrap gap-3 mb-4 items-center">
        <label for="sales_date" class="font-semibold text-pink-900 text-sm">Total Sales for Date:</label>
        <input type="date" id="sales_date" name="sales_date" value="<?= htmlspecialchars($selected_date) ?>" class="border border-pink-300 rounded-lg px-2 py-1" max="<?= date('Y-m-d') ?>">
        <button type="submit" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-1 rounded-lg">View</button>
        <?php if (isset($_GET['sales_date'])): ?>
          <a href="admin_dashboard.php" class="ml-2 text-sm text-pink-700 hover:underline">Reset</a>
        <?php endif; ?>
      </form>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
        <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4 border border-pink-200">
          <div class="bg-pink-100 p-3 rounded-lg text-pink-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2"
              viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 3v18h18"></path>
              <path d="M9 17v-6a3 3 0 016 0v6"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-pink-800">Total Sales <?= $selected_date == $today ? "Today" : "for " . date('F j, Y', strtotime($selected_date)) ?></p>
            <p class="text-2xl font-bold text-pink-900">₱ <?= number_format($selected_sales, 2) ?></p>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow p-6 flex items-center gap-4 border border-pink-200">
          <div class="bg-pink-100 p-3 rounded-lg text-pink-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2"
              viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <path d="M12 6v6l4 2" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-pink-800">Orders <?= $selected_date == $today ? "Today" : "for " . date('F j, Y', strtotime($selected_date)) ?></p>
            <p class="text-2xl font-bold text-pink-900"><?= $selected_orders ?></p>
          </div>
        </div>
      </div>
      <!-- Recent Orders Table -->
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-xl font-semibold text-pink-900 mb-4">Recent Orders</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-gray-700">
            <thead class="bg-pink-100 text-pink-800 font-semibold">
              <tr>
                <th class="px-4 py-2">Order ID</th>
                <th class="px-4 py-2">Customer</th>
                <th class="px-4 py-2">Product</th>
                <th class="px-4 py-2">Quantity</th>
                <th class="px-4 py-2">Total</th>
                <th class="px-4 py-2">Status</th>
              </tr>
            </thead>
            <tbody id="recentOrdersBody" class="bg-white">
              <?php foreach($recentOrders as $order): ?>
                <tr class="border-b border-pink-100 hover:bg-pink-50 transition">
                  <td class="px-4 py-2 font-mono"><?= $order['order_id'] ?></td>
                  <td class="px-4 py-2"><?= htmlspecialchars($order['customer_name']) ?></td>
                  <td class="px-4 py-2">
                    <?php foreach($order['items'] as $item) echo htmlspecialchars($item['product_name'])."<br>"; ?>
                  </td>
                  <td class="px-4 py-2">
                    <?php foreach($order['items'] as $item) echo intval($item['quantity'])."<br>"; ?>
                  </td>
                  <td class="px-4 py-2 font-semibold">₱ <?= number_format($order['total'], 2) ?></td>
                  <td class="px-4 py-2 font-semibold 
                    <?= $order['status'] === "Completed" ? "text-green-600" : ($order['status'] === "Pending" ? "text-yellow-600" : "text-red-600") ?>">
                    <?= htmlspecialchars($order['status']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Product Management -->
    <section id="products" class="section hidden">
      <h2 class="text-3xl font-extrabold text-pink-900 mb-8">🧁 Product Management</h2>
      <button
        onclick="document.getElementById('addProductModal').classList.remove('hidden')"
        class="mb-6 bg-green-500 hover:bg-green-600 text-white px-5 py-3 rounded-lg shadow transition"
      >+ Add New Product</button>
      <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="w-full text-left text-gray-700">
          <thead class="bg-pink-100 text-pink-800 font-semibold">
            <tr>
              <th class="px-4 py-3">Image</th>
              <th class="px-4 py-3">Category</th>
              <th class="px-4 py-3">Name</th>
              <th class="px-4 py-3">Price</th>
              <th class="px-4 py-3">Stock</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($products as $p): ?>
            <tr class="border-b border-pink-100 hover:bg-pink-50 transition">
              <td class="px-4 py-3">
                <img src="<?= htmlspecialchars($p['img']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" class="w-16 h-16 object-cover rounded-lg"/>
              </td>
              <td class="px-4 py-3"><?= htmlspecialchars($p['category']) ?></td>
              <td class="px-4 py-3 font-semibold text-pink-900"><?= htmlspecialchars($p['product_name']) ?></td>
              <td class="px-4 py-3 font-semibold text-pink-700">₱ <?= number_format($p['price'], 2) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($p['stock']) ?></td>
              <td class="px-4 py-3 flex gap-2">
                <button
                  class="bg-pink-600 text-white px-3 py-2 rounded hover:bg-pink-700"
                  onclick='openEditModal(<?= json_encode($p) ?>)'
                >Edit</button>
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this product?');" style="display:inline;">
                  <input type="hidden" name="delete_product" value="1" />
                  <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>" />
                  <button type="submit" class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-700">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>


    <!-- Order Management (NO DUPLICATES) -->
    <section id="orders" class="section hidden">
      <h2 class="text-3xl font-extrabold text-pink-900 mb-8">📦 Order Management</h2>
      <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-700">
          <thead class="bg-pink-100 text-pink-800 font-semibold">
            <tr>
              <th class="px-4 py-3">Order ID</th>
              <th class="px-4 py-3">Customer</th>
              <th class="px-4 py-3">Product(s)</th>
              <th class="px-4 py-3">Quantity</th>
              <th class="px-4 py-3">Total</th>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($orders as $order): ?>
              <tr class="border-b border-pink-100 hover:bg-pink-50 transition">
                <td class="px-4 py-3 font-mono"><?= $order['order_id'] ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($order['customer_name']) ?></td>
                <td class="px-4 py-3">
                  <?php foreach($order['items'] as $item) echo htmlspecialchars($item['product_name'])."<br>"; ?>
                </td>
                <td class="px-4 py-3">
                  <?php foreach($order['items'] as $item) echo intval($item['quantity'])."<br>"; ?>
                </td>
                <td class="px-4 py-3 font-semibold">₱ <?= number_format($order['total'], 2) ?></td>
                <td class="px-4 py-3"><?= date('Y-m-d H:i', strtotime($order['checkout_datetime'])) ?></td>
                <td class="px-4 py-3 font-semibold 
                  <?= $order['status'] === "Completed" ? "text-green-600" : ($order['status'] === "Pending" ? "text-yellow-600" : "text-red-600") ?>">
                  <?= htmlspecialchars($order['status']) ?>
                </td>
                <td class="px-4 py-3">
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="update_status" value="1"/>
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>"/>
                    <button name="status" value="Completed" class="text-green-600 hover:underline mr-2" type="submit">Complete</button>
                    <button name="status" value="Cancelled" class="text-red-600 hover:underline mr-2" type="submit">Cancel</button>
                  </form>
                  <?php if ($order['status'] === "Completed"): ?>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this completed order?');">
                      <input type="hidden" name="delete_order" value="1"/>
                      <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>"/>
                      <button type="submit" class="text-gray-500 hover:underline">Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section> 

    <!-- Inventory Monitoring -->
    <section id="inventory" class="section hidden">
      <h2 class="text-3xl font-extrabold text-pink-900 mb-8">📋 Inventory Monitoring</h2>
      <button
        onclick="document.getElementById('addInventoryModal').classList.remove('hidden')"
        class="mb-6 bg-green-500 hover:bg-green-600 text-white px-5 py-3 rounded-lg shadow transition"
      >+ Add Inventory Item</button>
      <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
        <table class="w-full text-left text-gray-700">
          <thead class="bg-pink-100 text-pink-800 font-semibold">
            <tr>
              <th class="px-4 py-2">Ingredient</th>
              <th class="px-4 py-2">Category</th>
              <th class="px-4 py-2">Quantity</th>
              <th class="px-4 py-2">Measurement</th>
              <th class="px-4 py-2">Expiry Date</th>
              <th class="px-4 py-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($inventory as $i): ?>
              <tr class="border-b border-pink-100 hover:bg-pink-50 transition">
                <td class="px-4 py-2"><?= htmlspecialchars($i['ingredient']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($i['category']) ?></td>
                <td class="px-4 py-2"><?= intval($i['quantity']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($i['measurement']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($i['expiry_date']) ?></td>
                <td class="px-4 py-2 flex gap-2">
                  <button class="bg-pink-600 text-white px-3 py-2 rounded hover:bg-pink-700"
                    onclick='openEditInventoryModal(<?= json_encode($i) ?>)'>Edit</button>
                  <form method="post" onsubmit="return confirm('Are you sure you want to delete this inventory item?');" style="display:inline;">
                    <input type="hidden" name="delete_inventory" value="1" />
                    <input type="hidden" name="id" value="<?= $i['id'] ?>" />
                    <button type="submit" class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-700">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Reports & Summary -->
    <section id="reports" class="section hidden">
      <h2 class="text-3xl font-extrabold text-pink-900 mb-8">📊 Reports & Summary</h2>
      <div class="bg-white rounded-xl shadow p-6">
        <?php if (count($sales_by_day) == 0): ?>
            <p class="text-gray-600">No sales reports available yet.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-gray-700">
              <thead class="bg-pink-100 text-pink-800 font-semibold">
                <tr>
                  <th class="px-4 py-2">Date</th>
                  <th class="px-4 py-2">Total Sales (₱)</th>
                  <th class="px-4 py-2">Total Orders</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($sales_by_day as $date => $sales): ?>
                  <tr class="border-b border-pink-100 hover:bg-pink-50 transition">
                    <td class="px-4 py-2"><?= date('F j, Y', strtotime($date)) ?></td>
                    <td class="px-4 py-2 font-semibold"><?= number_format($sales, 2) ?></td>
                    <td class="px-4 py-2"><?= $orders_by_day[$date] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

  </main>

  <!-- Add Product Modal -->
  <div id="addProductModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-96 p-8">
      <h3 class="text-2xl font-bold text-pink-900 mb-6">Add New Product</h3>
      <form method="post" class="flex flex-col gap-5">
        <input type="hidden" name="add_product" value="1" />
        <input type="text" name="category" placeholder="Category" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="text" name="product_name" placeholder="Product Name" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="number" min="0" step="0.01" name="price" placeholder="Price (₱)" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="number" min="0" name="stock" placeholder="Stock" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="url" name="img" placeholder="Image URL" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <div class="flex justify-end gap-3">
          <button type="button" onclick="document.getElementById('addProductModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-lg">Cancel</button>
          <button type="submit" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-lg">Add Product</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div id="editProductModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-96 p-8">
      <h3 class="text-2xl font-bold text-pink-900 mb-6">Edit Product</h3>
      <form method="post" class="flex flex-col gap-5" id="editProductForm">
        <input type="hidden" name="edit_product" value="1" />
        <input type="hidden" name="product_id" id="edit_product_id" />
        <input type="text" name="category" id="edit_category" placeholder="Category" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="text" name="product_name" id="edit_product_name" placeholder="Product Name" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="number" min="0" step="0.01" name="price" id="edit_price" placeholder="Price (₱)" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="number" min="0" name="stock" id="edit_stock" placeholder="Stock" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="url" name="img" id="edit_img" placeholder="Image URL" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <div class="flex justify-end gap-3">
          <button type="button" onclick="document.getElementById('editProductModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-lg">Cancel</button>
          <button type="submit" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-lg">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Inventory Modal -->
  <div id="addInventoryModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-96 p-8">
      <h3 class="text-2xl font-bold text-pink-900 mb-6">Add Inventory Item</h3>
      <form method="post" class="flex flex-col gap-5">
        <input type="hidden" name="add_inventory" value="1" />
        <input type="text" name="ingredient" placeholder="Ingredient" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="text" name="category" placeholder="Category" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="number" min="0" name="quantity" placeholder="Quantity" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="text" name="measurement" placeholder="Measurement (kg, L, pcs, etc.)" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="date" name="expiry_date" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <div class="flex justify-end gap-3">
          <button type="button" onclick="document.getElementById('addInventoryModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-lg">Cancel</button>
          <button type="submit" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-lg">Add Item</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Inventory Modal -->
  <div id="editInventoryModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-lg w-96 p-8">
      <h3 class="text-2xl font-bold text-pink-900 mb-6">Edit Inventory Item</h3>
      <form method="post" class="flex flex-col gap-5" id="editInventoryForm">
        <input type="hidden" name="edit_inventory" value="1" />
        <input type="hidden" name="id" id="edit_inventory_id" />
        <input type="text" name="ingredient" id="edit_ingredient" placeholder="Ingredient" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="text" name="category" id="edit_category_inventory" placeholder="Category" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="number" min="0" name="quantity" id="edit_quantity" placeholder="Quantity" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="text" name="measurement" id="edit_measurement" placeholder="Measurement (kg, L, pcs, etc.)" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <input type="date" name="expiry_date" id="edit_expiry_date" class="border border-pink-300 rounded-lg px-4 py-2" required />
        <div class="flex justify-end gap-3">
          <button type="button" onclick="document.getElementById('editInventoryModal').classList.add('hidden')" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-lg">Cancel</button>
          <button type="submit" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-lg">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Sidebar navigation
    function showSection(sectionId) {
      document.querySelectorAll('.section').forEach(s => s.classList.add('hidden'));
      document.querySelectorAll('aside nav button').forEach(btn => btn.classList.remove('bg-pink-300', 'font-bold'));
      document.getElementById(sectionId).classList.remove('hidden');
      document.getElementById('nav-' + sectionId).classList.add('bg-pink-300', 'font-bold');
    }
    showSection('dashboard'); // Default section

    // Edit Modal JS
    function openEditModal(product) {
      document.getElementById('edit_product_id').value = product.product_id;
      document.getElementById('edit_category').value = product.category;
      document.getElementById('edit_product_name').value = product.product_name;
      document.getElementById('edit_price').value = product.price;
      document.getElementById('edit_stock').value = product.stock;
      document.getElementById('edit_img').value = product.img;
      document.getElementById('editProductModal').classList.remove('hidden');
    }

    // Edit Inventory Modal JS
    function openEditInventoryModal(item) {
      document.getElementById('edit_inventory_id').value = item.id;
      document.getElementById('edit_ingredient').value = item.ingredient;
      document.getElementById('edit_category_inventory').value = item.category;
      document.getElementById('edit_quantity').value = item.quantity;
      document.getElementById('edit_measurement').value = item.measurement;
      document.getElementById('edit_expiry_date').value = item.expiry_date;
      document.getElementById('editInventoryModal').classList.remove('hidden');
    }
  </script>
</body>
</html>