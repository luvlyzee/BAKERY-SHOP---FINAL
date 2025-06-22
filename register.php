<?php
// --- PHP REGISTER BACKEND ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');

    // DB config
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

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm-password'] ?? '');

    // Additional validation (redundant with client-side but good practice)
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm)) {
        $response['message'] = "All fields are required";
        echo json_encode($response);
        exit;
    }

    if (strlen($fullname) < 3) {
        $response['message'] = "Full Name must be at least 3 characters.";
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Please enter a valid email address.";
        echo json_encode($response);
        exit;
    }

    if (strlen($password) < 6) {
        $response['message'] = "Password must be at least 6 characters.";
        echo json_encode($response);
        exit;
    }

    if ($password !== $confirm) {
        $response['message'] = "Passwords do not match";
        echo json_encode($response);
        exit;
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM registers WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $response['message'] = "Email already registered";
        echo json_encode($response);
        exit;
    }
    $check->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO registers (fullname, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $fullname, $email, $hashed_password);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Registration successful!";
        $response['redirect'] = "login.php";
    } else {
        $response['message'] = "Error: " . $stmt->error;
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
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - Sweet Haven Bakery</title>
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
    .register-container {
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
    input[type="text"],
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
    .login-link {
      margin-top: 1rem;
      font-size: 0.9rem;
      color: #4a3c31;
    }
    .login-link a {
      color: #b94505;
      text-decoration: none;
      font-weight: 700;
    }
    .login-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <main class="register-container" role="main" aria-label="Register Form">
    <h2>Create a Sweet Haven Account</h2>
    <form id="register-form" novalidate>
      <input type="text" id="fullname" name="fullname" placeholder="Full Name" required aria-required="true" minlength="3" />
      <input type="email" id="email" name="email" placeholder="Email address" required aria-required="true" />
      <input type="password" id="password" name="password" placeholder="Password" required aria-required="true" minlength="6" />
      <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm Password" required aria-required="true" minlength="6" />
      <button type="submit">Register</button>
      <p class="message" id="register-message" role="alert"></p>
    </form>
    <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
  </main>

  <script>
    const registerForm = document.getElementById('register-form');
    const registerMessage = document.getElementById('register-message');

    registerForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const fullname = registerForm.fullname.value.trim();
      const email = registerForm.email.value.trim();
      const password = registerForm.password.value.trim();
      const confirmPassword = registerForm['confirm-password'].value.trim();

      registerMessage.style.display = 'none';

      if (!fullname || !email || !password || !confirmPassword) {
        return showError('Please fill out all fields.');
      }

      if (fullname.length < 3) {
        return showError('Full Name must be at least 3 characters.');
      }

      const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,}$/i;
      if (!emailPattern.test(email)) {
        return showError('Please enter a valid email address.');
      }

      if (password.length < 6) {
        return showError('Password must be at least 6 characters.');
      }

      if (password !== confirmPassword) {
        return showError('Passwords do not match.');
      }

      // AJAX call to PHP backend
      const formData = new FormData();
      formData.append('fullname', fullname);
      formData.append('email', email);
      formData.append('password', password);
      formData.append('confirm-password', confirmPassword);

      fetch('register.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          registerMessage.style.color = 'green';
          registerMessage.textContent = data.message + ' Redirecting to login...';
          registerMessage.style.display = 'block';
          setTimeout(() => {
            window.location.href = data.redirect;
          }, 2000);
        } else {
          showError(data.message);
        }
      })
      .catch(error => {
        showError('An error occurred. Please try again.');
        console.error('Error:', error);
      });
    });

    function showError(message) {
      registerMessage.textContent = message;
      registerMessage.style.color = 'red';
      registerMessage.style.display = 'block';
    }
  </script>
</body>
</html>