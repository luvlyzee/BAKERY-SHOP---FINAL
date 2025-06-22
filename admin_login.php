<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "newsletter_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// Define the admin users (username => password)
$admins = [
    "ally"    => "aquino",
    "alyanna" => "magpantay",
    "andrea"  => "malabuyoc"
];

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

// Handle Login
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = strtolower(trim($_POST['username']));
    $password = trim($_POST['password']);
    if (isset($admins[$username]) && $admins[$username] === $password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = ucfirst($username); // For display
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Login - Sweet Haven</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-rose-100 flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
    <h1 class="text-3xl font-bold text-pink-900 text-center mb-8 flex items-center justify-center gap-2">
      <span>🍰</span> Admin Login
    </h1>
    <?php if ($error): ?>
      <div class="bg-red-200 text-red-800 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="flex flex-col gap-5">
      <div>
        <label class="block mb-1 font-semibold text-pink-900" for="username">Username</label>
        <input type="text" name="username" id="username" required class="border border-pink-300 rounded-lg px-4 py-2 w-full" autofocus />
      </div>
      <div>
        <label class="block mb-1 font-semibold text-pink-900" for="password">Password</label>
        <input type="password" name="password" id="password" required class="border border-pink-300 rounded-lg px-4 py-2 w-full" />
      </div>
      <button type="submit" name="login" class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded-lg font-semibold w-full">Login</button>
    </form>
    <p class="text-xs text-gray-500 mt-6 text-center">© <?= date('Y') ?> Sweet Haven Bakery</p>
  </div>
</body>
</html>