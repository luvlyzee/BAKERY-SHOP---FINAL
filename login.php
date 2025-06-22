<?php
// --- PHP LOGIN BACKEND ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');
    // DB config (same as register.php)
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "newsletter_db";

    // Connect
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
    }

    $response = ['success' => false, 'message' => ''];
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate inputs
    if (empty($email) || empty($password)) {
        $response['message'] = "Please fill in all fields";
        echo json_encode($response);
        exit;
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, fullname, password, email FROM registers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['message'] = "Invalid email or password";
        echo json_encode($response);
        exit;
    }

    $register_user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $register_user['password'])) {
        // Check if user exists in USERS table
        $check_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_user->bind_param("s", $email);
        $check_user->execute();
        $check_user->store_result();

        // If not exists in users table, insert
        if ($check_user->num_rows === 0) {
            $insert_user = $conn->prepare("INSERT INTO users (register_id, email, password) VALUES (?, ?, ?)");
            $insert_user->bind_param("iss", $register_user['id'], $email, $register_user['password']);
            $insert_user->execute();
            $insert_user->close();
        }
        $check_user->close();

        // Now fetch the correct user's id in users table (not registers)
        $get_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $get_user->bind_param("s", $email);
        $get_user->execute();
        $get_user->bind_result($user_id);
        $get_user->fetch();
        $get_user->close();

        // Start session
        session_start();
        $_SESSION['user_id'] = $user_id; // <-- THIS IS NOW THE USERS.ID!
        $_SESSION['user_email'] = $register_user['email'];
        $_SESSION['user_name'] = $register_user['fullname'];

        $response['success'] = true;
        $response['message'] = "Login successful! Welcome back, " . $register_user['fullname'];
        $response['redirect'] = "index.php";
    } else {
        $response['message'] = "Invalid email or password";
    }

    $stmt->close();
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Sweet Haven Bakery</title>
  <style>
    body {
      font-family: 'Quicksand', sans-serif;
      background: linear-gradient(135deg, #f9e4c8, #f48c06);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      color: #4a3c31;
    }
    .login-container {
      background: white;
      padding: 2rem 3rem;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(180, 130, 80, 0.4);
      width: 380px;
      text-align: center;
    }
    h2 {
      margin-bottom: 1.5rem;
      color: #b94505;
      font-weight: 700;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    input[type="email"],
    input[type="password"] {
      padding: 0.8rem 1rem;
      border: 2px solid #b94505;
      border-radius: 25px;
      font-size: 1rem;
      outline-offset: 2px;
    }
    button {
      background-color: #f48c06;
      border: none;
      color: white;
      font-weight: 700;
      padding: 0.8rem 1rem;
      border-radius: 25px;
      cursor: pointer;
      font-size: 1.1rem;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #b94505;
    }
    .message {
      margin-top: 1rem;
      font-weight: 600;
      display: none;
    }
    .register-link {
      margin-top: 1rem;
      font-size: 0.9rem;
      color: #4a3c31;
    }
    .register-link a {
      color: #b94505;
      text-decoration: none;
      font-weight: 700;
    }
    .register-link a:hover {
      text-decoration: underline;
    }
    .spinner {
      margin-top: 1rem;
      display: none;
    }
    .spinner:after {
      content: '';
      display: inline-block;
      width: 24px;
      height: 24px;
      border: 4px solid #f48c06;
      border-top: 4px solid transparent;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <main class="login-container" role="main" aria-label="Login Form">
    <h2>Welcome Back!</h2>
    <form id="login-form" novalidate>
      <input type="email" id="email" name="email" placeholder="Email address" required aria-required="true" />
      <input type="password" id="password" name="password" placeholder="Password" required aria-required="true" minlength="6" />
      <button type="submit">Login</button>
      <p class="message" id="login-message" role="alert"></p>
      <div class="spinner" id="spinner"></div>
    </form>
    <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
  </main>

  <script>
    const loginForm = document.getElementById('login-form');
    const loginMessage = document.getElementById('login-message');
    const spinner = document.getElementById('spinner');

    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();

      spinner.style.display = 'inline-block';
      loginMessage.style.display = 'none';

      const email = loginForm.email.value.trim();
      const password = loginForm.password.value.trim();

      if (!email || !password) {
        showError('Please fill in all fields.');
        spinner.style.display = 'none';
        return;
      }

      // Fallback: AJAX login
      const formData = new FormData();
      formData.append('email', email);
      formData.append('password', password);

      fetch('login.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        spinner.style.display = 'none';

        if (data.success) {
          loginMessage.style.color = 'green';
          loginMessage.textContent = data.message;
          loginMessage.style.display = 'block';

          setTimeout(() => {
            window.location.href = data.redirect;
          }, 2000);
        } else {
          showError(data.message);
        }
      })
      .catch(error => {
        spinner.style.display = 'none';
        showError('An error occurred. Please try again.');
        console.error('Error:', error);
      });
    });

    function showError(message) {
      loginMessage.textContent = message;
      loginMessage.style.color = 'red';
      loginMessage.style.display = 'block';
    }
  </script>
</body>
</html>