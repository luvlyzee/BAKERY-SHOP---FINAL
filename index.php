<?php
session_start();
$user_balance = 0.00; // Default balance
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
$user = [
  "name" => "Juan Dela Cruz",
  "email" => "juan@example.com"
];
$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
  $host = "localhost";
  $dbuser = "root";
  $dbpass = "";
  $dbname = "newsletter_db";
  $conn = new mysqli($host, $dbuser, $dbpass, $dbname); 

  // Fetch user balance from users table using id (NOT register_id)
  $user_balance = 0.00;
  $stmt = $conn->prepare("SELECT balance, register_id, email FROM users WHERE id = ?");
  $stmt->bind_param("i", $_SESSION['user_id']);
  $stmt->execute();
  $stmt->bind_result($user_balance, $register_id, $user_email);
  $stmt->fetch();
  $stmt->close();

  if (!$conn->connect_error) {
    // Get user's name/email from registers table using register_id
    $stmt = $conn->prepare("SELECT fullname, email FROM registers WHERE id = ?");
    $stmt->bind_param("i", $register_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
      $user['name'] = $row['fullname'];
      $user['email'] = $row['email'];
    } else {
      // fallback to the email in users table if not found in registers
      $user['email'] = $user_email;
    }
    $stmt->close();
    $conn->close();
  }
}

echo "<script>window.userBalance = " . floatval($user_balance) . ";</script>"; 
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Online Bakery Shop Management System</title>
  <link rel="stylesheet" href="styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    /* --- Checkout Form Enhanced Design --- */
    .checkout-content {
      background-color: #fffefc;
      padding: 35px 30px 32px 30px;
      border-radius: 18px;
      width: 94%;
      max-width: 500px;
      box-shadow: 0 6px 32px rgba(185, 69, 5, 0.18);
      font-family: 'Inter', sans-serif;
      margin: 0 auto;
      position: relative;
      max-height: 95vh;
      overflow-y: auto;
      border: 1.5px solid #e7b87e;
      animation: fadeInModal 0.35s;
    }
    @keyframes fadeInModal {
      from { opacity: 0; transform: translateY(-30px);}
      to   { opacity: 1; transform: translateY(0);}
    }
    .checkout-content h2 {
      font-family: 'Playfair Display', serif;
      color: #b94505;
      font-size: 2.1rem;
      font-weight: 700;
      margin-bottom: 1.2rem;
      letter-spacing: 0.01em;
      text-align: center;
    }
    .checkout-content form {
      margin-top: 0.6rem;
    }
    .form-group {
      margin-bottom: 1.22rem;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
    .checkout-content label.section-label {
      margin-bottom: 0.55em;
      font-size: 1.08rem;
      color: #b94505;
      font-family: 'Playfair Display', serif;
    }
    .checkout-content label {
      color: #7d5b2c;
      font-family: 'Inter', sans-serif;
      font-weight: 600;
      font-size: 1.05rem;
      letter-spacing: 0.01em;
    }
    .checkout-content input,
    .checkout-content select {
      font-family: 'Inter', sans-serif;
      font-size: 1.03rem;
      padding: 0.7rem 0.9rem;
      border-radius: 6px;
      border: 1.5px solid #f3d7a6;
      background: #fffdfa;
      margin-bottom: 0;
      margin-top: 0.13rem;
      width: 100%;
      box-sizing: border-box;
      transition: border 0.2s;
    }
    .checkout-content input:focus,
    .checkout-content select:focus {
      border-color: #e7b87e;
      outline: none;
    }
    .balance-group {
      margin-bottom: 1.22rem;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      background: #fff7ed;
      border-radius: 6px;
      padding: 0.7rem 1rem;
      border: 1.5px solid #e7b87e;
    }
    .balance-group label {
      color: #b94505;
      font-family: 'Playfair Display', serif;
      font-size: 1.08rem;
      font-weight: 700;
      margin-bottom: 0.3rem;
    }
    .balance-amount {
      color: #4a3c31;
      font-family: 'Inter', sans-serif;
      font-size: 1.17rem;
      font-weight: bold;
    }
    .radio-group {
      display: flex;
      flex-direction: row;
      gap: 28px;
      margin-bottom: 0;
      margin-top: 0.2rem;
    }
    .radio-group label {
      font-weight: 600;
      font-size: 1.02rem;
      color: #b94505;
      display: flex;
      align-items: center;
      gap: 5px;
      font-family: 'Inter', sans-serif;
      margin-bottom: 0;
    }
    .datetime-group {
      display: flex;
      flex-direction: row;
      gap: 13px;
      justify-content: space-between;
    }
    .datetime-group .form-group {
      flex: 1;
      margin-bottom: 0;
    }
    .pay-btn {
      width: 100%;
      background: linear-gradient(90deg, #e7b87e 0%, #d4a373 85%);
      color: #fff;
      font-family: 'Inter', sans-serif;
      font-weight: 700;
      font-size: 1.18rem;
      border: none;
      border-radius: 9px;
      padding: 1.1rem 0;
      cursor: pointer;
      margin-top: 1.2rem;
      letter-spacing: 0.01em;
      transition: background 0.2s, box-shadow 0.2s;
      box-shadow: 0 2px 9px rgba(244, 140, 6, 0.09);
    }
    .pay-btn:hover {
      background: linear-gradient(90deg, #d4a373 10%, #e7b87e 100%);
      box-shadow: 0 5px 18px rgba(185, 69, 5, 0.15);
    }
    /* Responsive adjustments for checkout modal */
    @media (max-width: 600px) {
      .checkout-content {
        padding: 14px 4vw 17px 4vw;
        border-radius: 13px;
        max-width: 99vw;
      }
      .datetime-group {
        flex-direction: column;
        gap: 0.5rem;
      }
    }
    /* --- Existing styles below --- */
    body {
      display: flex;
      flex-direction: row;
      font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f9f5f0;
    }
    .slideshow-container {
      position: fixed;
      left: 0;
      top: 0;
      width: 25%;
      height: 100vh;
      overflow: hidden;
      z-index: 100;
    }
    .slide {
      display: none;
      width: 100%;
      height: 100%;
    }
    .slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .main-content {
      margin-left: 25%;
      width: 75%;
      padding: 20px;
      background-color: #f9f5f0;
      position: relative;
    }
    .main-header {
      background: #E77D22;
      border-radius: 0 0 20px 20px;
      padding: 1.3rem 1rem rem 1rem;
      box-shadow: 0 2px 18px rgba(244, 140, 6, 0.08);
      margin-bottom: 2.5rem;
    }
    header h1 {
      font-family: 'Playfair Display', serif;
      font-size: 2.8rem;
      color: #fff;
      font-weight: 700;
      letter-spacing: 0.03em;
      margin: 1.5rem 0 1rem 0;
      text-shadow: 0 2px 12px rgba(185, 69, 5, 0.09);
      text-align: center;
      background: none;
      border-radius: 0;
      padding: 0;
      box-shadow: none;
      max-width: 100%;
      width: 100%;
      margin-left: auto;
      margin-right: auto;
    }
    #myAccountHeader {
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      font-weight: 700;
      font-size: 1.22rem;
      color: #E77D22;
      border: 2px solid #fff;
      border-radius: 0.75rem;
      padding: 0.4rem 1.25rem;
      margin-bottom: 0.75rem;
      width: fit-content;
      user-select: none;
      transition: background-color 0.3s ease, color 0.3s ease;
      display: block;
      text-align: center;
      background: #fff;
      letter-spacing: 0.01em;
    }
    #myAccountHeader:hover,
    #myAccountHeader:focus {
      background-color: #fff;
      color: #E77D22;
      outline: none;
    }
    nav.category-nav {
      font-family: 'Inter', sans-serif;
      font-size: 1.11rem;
      color: #fff;
      text-align: center;
      margin-bottom: 0;
    }
    nav.category-nav a {
      color: #fff;
      text-decoration: none;
      padding: 0.25rem 0.75rem;
      font-weight: 600;
      letter-spacing: 0.01em;
      border-radius: 20px;
      transition: background 0.2s, color 0.2s;
      font-family: 'Inter', sans-serif;
    }
    nav.category-nav a:hover {
      background: #fff;
      color: #E77D22;
    }
    .category-section {
      margin: 40px 0;
      scroll-margin-top: 20px;
    }
    .category-title {
      font-family: 'Playfair Display', serif;
      font-size: 2.1rem;
      color: #b94505;
      border-bottom: 2px solid #d4a373;
      padding-bottom: 10px;
      margin-bottom: 20px;
      letter-spacing: 0.02em;
    }
    .products-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      grid-template-rows: repeat(2, auto);
      gap: 20px;
      align-items: stretch;
    }
    .product-card {
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.09);
      transition: transform 0.3s;
      font-family: 'Inter', sans-serif; 
      display: flex;
      flex-direction: column;
      height: 100%;
      min-height: 350px;
      position: relative;
    }
    .product-card:hover {
      transform: translateY(-5px);
    }
    .product-img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    .product-info {
      flex-grow: 1;
      padding: 15px;
      display: flex;
      flex-direction: column;
      height: 100%;
      padding-bottom: 56px;
    }
    .product-name {
      font-family: 'Playfair Display', serif;
      font-weight: 600;
      margin: 0 0 5px;
      font-size: 1.14rem;
      color: #4a3c31;
      word-break: break-word;
    }
    .product-price {
      color: #b94505;
      font-weight: 700;
      font-size: 1.08rem;
      font-family: 'Inter', sans-serif;
      margin-bottom: 16px;
    }
    .add-to-cart {
      width: 75%;
      padding: 12px 0;
      background: #d4a373;
      border: none;
      color: white;
      border-radius: 5px;
      cursor: pointer;
      margin-top: auto;
      transition: background 0.3s;
      font-family: 'Inter', sans-serif;
      font-weight: 600;
      font-size: 1rem;
      display: block;
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      bottom: 18px;
      text-align: center;
    }
    .add-to-cart:hover {
      background: #b94505;
    }
    .cart-section {
      margin: 2.5rem 0 0 0;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.06);
      padding: 1.5rem;
      font-family: 'Inter', sans-serif;
    }
    .cart-header {
      font-family: 'Playfair Display', serif;
      color: #b94505;
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 1rem;
      letter-spacing: 0.01em;
      text-align: center;
    }
    .cart-item {
      display: flex;
      align-items: center;
      padding: 10px;
      border-bottom: 1px solid #eee;
      gap: 15px;
    }
    .cart-item img {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 5px;
    }
    .cart-item-details {
      flex-grow: 1;
      font-family: 'Inter', sans-serif;
    }
    .cart-item-controls {
      display: flex;
      gap: 5px;
    }
    .qty-btn {
      width: 25px;
      height: 25px;
      border: none;
      background: #d4a373;
      color: white;
      border-radius: 3px;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
    }
    .remove-item {
      background: none;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      color: #b94505;
    }
    .cart-total {
      font-family: 'Inter', sans-serif;
      font-size: 1.18rem;
      font-weight: 700;
      color: #b94505;
      margin-top: 1rem;
      text-align: right;
    }
    .checkout-btn {
      margin-top: 1.2rem;
      width: 100%;
      background: #d4a373;
      color: #fff;
      font-family: 'Inter', sans-serif;
      font-weight: 700;
      font-size: 1.08rem;
      border: none;
      border-radius: 7px;
      padding: 0.85rem 0;
      cursor: pointer;
      transition: background 0.2s;
      letter-spacing: 0.01em;
    }
    .checkout-btn[disabled] {
      opacity: 0.6;
      cursor: not-allowed;
    }
    .checkout-btn:hover:not([disabled]) {
      background: #b94505;
    }
    #checkout-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      display: flex;
    }
    /* Login Required Modal */
    .login-required-content {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.18);
      padding: 30px 24px 25px 24px;
      max-width: 350px;
      width: 95%;
      text-align: center;
      font-family: 'Inter', sans-serif;
    }
    .login-required-content h2 {
      font-family: 'Playfair Display', serif;
      color: #b94505;
      font-size: 1.5rem;
      margin-bottom: 0.75rem;
    }
    .login-required-content p {
      font-size: 1.07rem;
      margin-bottom: 1.2rem;
    }
    .login-required-content a {
      display: inline-block;
      background: #e77d22;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      padding: 0.7em 1.6em;
      font-weight: bold;
      font-size: 1.06rem;
      transition: background 0.2s;
    }
    .login-required-content a:hover {
      background: #b94505;
    }
    .login-required-content .close-login-modal {
      background: none;
      border: none;
      color: #b94505;
      font-size: 1.7rem;
      position: absolute;
      right: 16px;
      top: 8px;
      cursor: pointer;
    }

    /* --- Smooth Scrolling and Section Offset --- */
    html {
      scroll-behavior: smooth;
    }

    .category-section {
      scroll-margin-top: 120px; /* Adjust based on your header height */
      padding-top: 20px; /* Extra padding to ensure content isn't hidden */
    }
    @media (max-width: 900px) {
      .main-content { margin-left: 0; width: 100%; }
      .slideshow-container { display:none; }
      .main-header { padding: 1rem 0.5rem 1rem 0.5rem;}
      header h1 { font-size: 2rem; }
      #checkout-modal { width: 100vw; height: 100vh; left: 0; top: 0; z-index: 105; }
      .product-card { min-height: 300px; }
      .add-to-cart { width: 90%; padding: 10px 0; bottom: 12px; }
      .product-info { padding-bottom: 42px; }
    }
  </style>
</head>

<body>
  <!-- Promo Slideshow (Always shown unless on mobile/small screen) -->
  <div class="slideshow-container">
    <div class="slide">
      <img src="https://i.pinimg.com/736x/26/17/3d/26173dacb75f378b84c937f868d46862.jpg" alt="Promo 1">
    </div>
    <div class="slide">
      <img src="https://i.pinimg.com/736x/63/28/b2/6328b2bd9b901be696b4e17c34e1b25e.jpg" alt="Promo 2">
    </div>
    <div class="slide">
      <img src="https://i.pinimg.com/736x/a6/34/ef/a634ef16e47171448beb1634cf793522.jpg" alt="Promo 3">
    </div>
    <div class="slide">
      <img src="https://i.pinimg.com/736x/a6/55/c6/a655c66558328ce02475ebba4b7eae16.jpg" alt="Promo 4">
    </div>
    <div class="slide">
      <img src="https://i.pinimg.com/736x/d5/b4/ee/d5b4ee45254935ca16d5bd75e339dc08.jpg" alt="Promo 5">
    </div>
  </div>

  <div class="main-content">
    <header class="main-header">
      <button id="myAccountHeader" type="button" role="button" aria-label="View My Account Details"
        style="text-decoration:none;display:none;">
        MY ACCOUNT
      </button>
      <h1>SWEET HAVEN BAKERY</h1>
      <nav class="category-nav">
        <a href="index.php#breads">Breads</a> |
        <a href="index.php#pastries">Pastries</a> |
        <a href="index.php#cakes">Cakes</a> |
        <a href="index.php#cookies">Cookies</a>
      </nav>
    </header>

    <main>
      <section id="breads" class="category-section">
        <h2 class="category-title">Breads</h2>
        <div class="products-grid" id="breads-grid"></div>
      </section>
      <section id="pastries" class="category-section">
        <h2 class="category-title">Pastries</h2>
        <div class="products-grid" id="pastries-grid"></div>
      </section>
      <section id="cakes" class="category-section">
        <h2 class="category-title">Cakes</h2>
        <div class="products-grid" id="cakes-grid"></div>
      </section>
      <section id="cookies" class="category-section">
        <h2 class="category-title">Cookies</h2>
        <div class="products-grid" id="cookies-grid"></div>
      </section>
      <section class="cart-section" aria-label="Shopping Cart">
        <div class="cart-header">Your Cart</div>
        <div class="cart-items" id="cart-items" tabindex="0" aria-live="polite" aria-relevant="additions removals"></div>
        <div class="cart-total" aria-label="Total price" id="cart-total">Total: &#8369;0.00</div>
        <button id="checkout-button" class="checkout-btn" aria-disabled="true" disabled>Proceed to Checkout</button>
      </section>
    </main>

    <!-- Checkout Modal -->
        <div id="checkout-modal" role="dialog" aria-modal="true" aria-labelledby="checkout-title" style="display: none;">
          <?php if ($is_logged_in): ?>
          <div class="checkout-content">
            <button class="close-btn" aria-label="Close checkout form">&times;</button>
            <h2 id="checkout-title">Checkout</h2>
            <form id="checkout-form" novalidate>
              <div class="form-group">
                <label for="cust-name">Name</label>
                <input type="text" id="cust-name" name="cust-name" required autocomplete="name" 
                      value="<?php echo $is_logged_in ? htmlspecialchars($user['name']) : ''; ?>" 
                      <?php echo $is_logged_in ? 'readonly' : ''; ?> />
              </div>
              <div class="form-group">
                <label for="cust-email">Email</label>
                <input type="email" id="cust-email" name="cust-email" required autocomplete="email" 
                      value="<?php echo $is_logged_in ? htmlspecialchars($user['email']) : ''; ?>" 
                      <?php echo $is_logged_in ? 'readonly' : ''; ?> />
              </div>
              <div class="form-group">
                <label for="cust-phone">Phone Number</label>
                <input type="tel" id="cust-phone" name="cust-phone" required pattern="\+?[0-9\-\s]{7,15}" autocomplete="tel" />
              </div>
              <div class="balance-group">
                <label>Current Balance</label>
                <span class="balance-amount" id="modal-balance">&#8369;<?php echo number_format($user_balance, 2); ?></span>
              </div>
              <div class="balance-group" style="margin-top:-0.5rem;">
                <label>Amount Payable</label>
                <span class="balance-amount" id="modal-amount-payable">&#8369;0.00</span>
              </div>
              <div class="form-group">
                <label class="section-label" style="display:block;margin-bottom:6px;">Mode of Delivery</label>
                <div class="radio-group">
                  <label><input type="radio" name="delivery-mode" value="pickup" checked> Pick-up</label>
                  <label><input type="radio" name="delivery-mode" value="delivery"> Delivery</label>
                </div>
              </div>
              <div class="form-group delivery-address-group">
                <label for="cust-address">Delivery Address</label>
                <input type="text" id="cust-address" name="cust-address" autocomplete="street-address" />
              </div>
              <div class="datetime-group">
                <div class="form-group">
                  <label for="delivery-date">Date</label>
                  <input type="date" id="delivery-date" name="delivery-date" required min="">
                </div>
                <div class="form-group">
                  <label for="delivery-time">Time</label>
                  <input type="time" id="delivery-time" name="delivery-time" required>
                </div>
              </div>
              <button type="submit" class="pay-btn">Pay Now</button>
            </form>
          </div>
          <?php else: ?>
          <div class="login-required-content" style="position:relative;">
            <button class="close-login-modal" aria-label="Close">&times;</button>
            <h2 id="login-required-title">Not Logged In</h2>
            <p>You must be logged in to proceed to checkout.</p>
            <a href="login.php">Login Now</a>
          </div>
          <?php endif; ?>
        </div>

        <!-- Confirmation Message -->
        <div id="confirmation-message" role="alert" aria-live="assertive" style="display: none;">
          <h2>Thank you for your order!</h2>
          <p>Your order has been successfully placed and will be delivered soon.</p>
          <button id="confirm-close-btn">Close</button>
        </div>

        <?php include 'dashboard.php'; ?>
      </div>
  <script src="app.js"></script>
    <script>
      let user = {
        name: "<?php echo htmlspecialchars($user['name']); ?>",
        email: "<?php echo htmlspecialchars($user['email']); ?>"
      };
      window.user = user;
      window.is_logged_in = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    </script>
    <script>
      // --- JS FOR UPDATING CHECKOUT BALANCE DYNAMICALLY ---
      function updateCheckoutBalance() {
        fetch('get_balance.php')
          .then(response => response.json())
          .then(data => {
            if (data.balance !== undefined && document.getElementById('modal-balance')) {
              document.getElementById('modal-balance').textContent = '₱' + data.balance;
            }
          });
      }

      // When the checkout modal opens, update balance (total amount logic removed)
      document.addEventListener("DOMContentLoaded", function() {
        const checkoutModal = document.getElementById("checkout-modal");
        const checkoutBtn = document.getElementById("checkout-button");
        const closeCheckoutBtn = document.querySelector("#checkout-modal .close-btn");
        const closeLoginModalBtn = document.querySelector("#checkout-modal .close-login-modal");

        if (checkoutModal) checkoutModal.style.display = "none";

        if (checkoutBtn) {
          checkoutBtn.addEventListener("click", function(e) {
            checkoutModal.style.display = "flex";
            // Wait for modal to be visible and DOM to update
            setTimeout(() => {
              updateCheckoutBalance();
              // --- Autofill Name and Email from dashboard/user ---
              if (window.user && window.is_logged_in) {
                const nameInput = document.getElementById('cust-name');
                const emailInput = document.getElementById('cust-email');
                if (nameInput) nameInput.value = window.user.name || '';
                if (emailInput) emailInput.value = window.user.email || '';
              }
              if (window.is_logged_in) {
                const input = document.querySelector("#checkout-modal input, #checkout-modal select");
                if(input) input.focus();
              }
            }, 100); // 100ms delay to ensure modal is visible
          });
        }
        if (closeCheckoutBtn) {
          closeCheckoutBtn.addEventListener("click", function() {
            checkoutModal.style.display = "none";
          });
        }
        if (closeLoginModalBtn) {
          closeLoginModalBtn.addEventListener("click", function() {
            checkoutModal.style.display = "none";
          });
        }
        checkoutModal.addEventListener("click", function(e) {
          if (e.target === checkoutModal) {
            checkoutModal.style.display = "none";
          }
        });

        // Slideshow logic
        let slideIndex = 0;
        function showSlides() {
          let slides = document.getElementsByClassName("slide");
          for (let i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
          }
          slideIndex++;
          if (slideIndex > slides.length) {slideIndex = 1;}
          if (slides.length > 0) {
            slides[slideIndex-1].style.display = "block";
          }
          setTimeout(showSlides, 3500);
        }
        showSlides();
      });

    // Enhanced smooth scrolling for category navigation
    document.addEventListener("DOMContentLoaded", function() {
      // Function to handle scrolling to sections with header offset
      function scrollToSection(hash) {
        if (hash) {
          const element = document.querySelector(hash);
          if (element) {
            const headerHeight = document.querySelector('.main-header').offsetHeight;
            const elementPosition = element.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerHeight;
            
            window.scrollTo({
              top: offsetPosition,
              behavior: 'smooth'
            });
          }
        }
      }

      // Handle initial hash on page load (coming from welcome.php)
      if (window.location.hash) {
        setTimeout(() => scrollToSection(window.location.hash), 100);
      }

      // Handle navigation clicks within index.php
      document.querySelectorAll('.category-nav a').forEach(link => {
        link.addEventListener('click', function(e) {
          // Only handle internal links
          if (this.getAttribute('href').startsWith('#')) {
            e.preventDefault();
            scrollToSection(this.getAttribute('href'));
          }
        });
      });
    });
    </script>
  </body>
  </html> 