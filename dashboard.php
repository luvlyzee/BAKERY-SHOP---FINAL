<?php
// dashboard.php - Enhanced: Shows correct order counts and user order history in the dashboard modal.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// DB connection (reuse from your main file if already connected).
$host = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "newsletter_db";
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) die("DB error: " . $conn->connect_error);

// Fetch user info (assume you have $user array with name/email elsewhere)
if (!isset($user)) {
    $user = ['name' => '', 'email' => ''];
    if ($user_id) {
        $u_sql = $conn->prepare('SELECT u.email, r.fullname FROM users u JOIN registers r ON u.register_id = r.id WHERE u.id=?');
        $u_sql->bind_param("i", $user_id);
        $u_sql->execute();
        $u_res = $u_sql->get_result();
        $u = $u_res->fetch_assoc();
        if ($u) {
            $user['name'] = $u['fullname'];
            $user['email'] = $u['email'];
        }
        $u_sql->close();
    }
}

// Fetch order status counts and order history for this user
$pending = $delivered = $cancelled = 0;

if ($user_id) {
    // FIX: Use checkout_date and checkout_time instead of created_at
    $orders_q = $conn->prepare("SELECT o.order_id, o.total_amount, o.checkout_date, o.checkout_time, o.order_status FROM `order` o WHERE o.user_id=? ORDER BY o.checkout_date DESC, o.checkout_time DESC");
    $orders_q->bind_param("i", $user_id);
    $orders_q->execute();
    $orders_res = $orders_q->get_result();
    while ($row = $orders_res->fetch_assoc()) {
        if ($row['order_status'] === "Pending") $pending++;
        elseif ($row['order_status'] === "Completed" || $row['order_status'] === "Delivered") $delivered++;
        elseif ($row['order_status'] === "Cancelled") $cancelled++;
    }
    $orders_q->close();
}

if (!$user_id) $pending = $delivered = $cancelled = 0;
?>

<!-- DASHBOARD MODAL (appears when MY ACCOUNT is clicked) -->
<style>
  /* ... (all your CSS is unchanged) ... */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.25);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 11000;
  }
  .modal-backdrop.active {
    display: flex !important;
  }
  .modal.dashboard-modal {
    background: linear-gradient(135deg, #fff8f0 60%, #ffe4c4 100%);
    border-radius: 1.5rem;
    box-shadow: 0 18px 48px 0 rgba(244, 140, 6, 0.18), 0 2px 18px rgba(180, 130, 80, 0.13);
    padding: 2.5rem 3rem;
    max-width: 900px;
    width: 96vw;
    min-width: 340px;
    color: #4b3b2b;
    position: relative;
    font-size: 1.08rem;
    font-family: 'Inter', sans-serif;
    border: 2px solid #e3b36d;
    overflow: visible;
    transition: box-shadow 0.2s, border 0.2s;
  }
  .modal.dashboard-modal::before {
    content: "";
    position: absolute;
    inset: -8px;
    border-radius: 2rem;
    background: linear-gradient(120deg, #e77d22 0%, #ffe4c4 100%);
    z-index: -1;
    filter: blur(6px);
    opacity: 0.11;
    pointer-events: none;
  }
  .modal h2 {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 2.2rem;
    color: #b94505;
    text-align: center;
    letter-spacing: 0.04em;
    text-shadow: 0 2px 12px rgba(185, 69, 5, 0.06);
  }
  .modal p {
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    margin-bottom: 0.8rem;
    line-height: 1.4;
  }
  .close-button {
    position: absolute;
    top: 1.2rem;
    right: 1.4rem;
    background: #fff3e6;
    border: 1.5px solid #ffe4c4;
    font-size: 1.8rem;
    font-weight: 700;
    color: #b94505;
    cursor: pointer;
    border-radius: 50%;
    width: 2.7rem;
    height: 2.7rem;
    box-shadow: 0 2px 10px rgba(180, 130, 80, 0.09);
    transition: background 0.23s, color 0.23s, border 0.2s;
    z-index: 1;
    display: flex; align-items: center; justify-content: center;
  }
  .close-button:hover,
  .close-button:focus {
    background: #ffdbaa;
    color: #7a3100;
    border: 1.5px solid #e3b36d;
    outline: none;
  }
  .dashboard-modal {
    /* for specificity only; real style above */
  }
  .dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 2fr 2fr;
    gap: 2rem;
    margin-top: 1rem;
  }
  .dashboard-card {
    background: linear-gradient(120deg, #fff3e6 70%, #ffe4c4 100%);
    padding: 1.6rem 1.5rem 1.2rem 1.5rem;
    border-radius: 1.2rem;
    box-shadow: 0 8px 22px rgba(180, 130, 80, 0.09);
    font-family: 'Inter', sans-serif;
    border: 1.5px solid #f6d7ae;
    position: relative;
    overflow: hidden;
    min-width: 0;
    transition: box-shadow 0.2s, border 0.2s;
  }
  .dashboard-card::after {
    content: "";
    position: absolute;
    bottom: -30px;
    right: -20px;
    width: 65px;
    height: 65px;
    background: rgba(231, 125, 34, 0.10);
    border-radius: 50%;
    z-index: 0;
  }
  .dashboard-card h3 {
    font-family: 'Playfair Display', serif;
    color: #b94505;
    margin-bottom: 1.1rem;
    font-size: 1.25rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    z-index: 1;
    position: relative;
  }
  .dashboard-card p {
    margin: 0.45rem 0 0.25rem 0;
    font-family: 'Inter', sans-serif;
    z-index: 1;
    position: relative;
    font-size: 1.06rem;
  }
  .dashboard-btn {
    display: block;
    margin: 0.7rem auto 0.6rem auto;
    width: 90%;
    padding: 0.7rem;
    background: #ffdbaa;
    color: #b94505;
    border: 1.5px solid #e3b36d;
    border-radius: 8px;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    font-size: 1.08rem;
    cursor: pointer;
    transition: background 0.23s, color 0.23s, border 0.2s;
    box-shadow: 0 2px 8px #ffe4c45c;
  }
  .dashboard-btn:hover {
    background-color: #ffe4c4;
    color: #a33a00;
    border-color: #b94505;
  }
  .logout-btn-bottom {
    display: block;
    width: 100%;
    margin: 2.5rem 0 0 0;
    background: linear-gradient(90deg, #b94505 80%, #e77d22 100%);
    color: #fff;
    border: none;
    border-radius: 18px;
    padding: 1.05rem 0;
    font-family: 'Inter', sans-serif;
    font-weight: 700;
    font-size: 1.18rem;
    cursor: pointer;
    transition: background 0.2s, box-shadow 0.23s;
    text-align: center;
    box-shadow: 0 4px 14px rgba(180, 130, 80, 0.16);
    letter-spacing: 1px;
  }
  .logout-btn-bottom:hover {
    background: linear-gradient(90deg, #a33a00 70%, #e77d22 100%);
    box-shadow: 0 6px 18px rgba(180, 130, 80, 0.20);
  }
  .dashboard-icon {
    font-size: 2.6rem;
    margin-bottom: 0.7rem;
    color: #e77d22;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    z-index: 1;
    position: relative;
    filter: drop-shadow(0 2px 10px #ffdbaa50);
  }
  /* BALANCE MODAL STYLES */
  .balance-modal-form {
    margin-top: 1.2rem;
  }
  .balance-type-select {
    width: 100%;
    padding: 0.6rem 0.7rem;
    border-radius: 6px;
    border: 1.3px solid #e3b36d;
    font-size: 1.05rem;
    margin-bottom: 1rem;
    background: #fffbe9;
  }
  .balance-input-group {
    margin-bottom: 1.1rem;
  }
  .balance-input-label {
    display: block;
    margin-bottom: 0.35rem;
    font-weight: 500;
    color: #b94505;
  }
  .balance-input {
    width: 100%;
    padding: 0.6rem 0.7rem;
    border-radius: 6px;
    border: 1.3px solid #e3b36d;
    font-size: 1.05rem;
    background: #fffbe9;
  }
  .balance-modal-balance {
    font-size: 2.1rem;
    font-weight: 700;
    color: #e77d22;
    margin-bottom: 0.7rem;
  }
  @media (max-width: 1200px) {
    .modal.dashboard-modal {
      max-width: 700px;
      padding: 1.7rem 1.1rem;
    }
    .dashboard-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 900px) {
    .modal.dashboard-modal {
      max-width: 98vw;
      padding: 1.1rem 0.2rem;
      border-radius: 1rem;
    }
    .dashboard-grid { grid-template-columns: 1fr; }
  }
  @media (max-width: 600px) {
    .modal.dashboard-modal {
      padding: 0.5rem 0.1rem;
      border-radius: 0.7rem;
    }
    .dashboard-card {
      border-radius: 0.7rem;
      padding: 1rem 0.7rem;
    }
    .dashboard-card h3 { font-size: 1rem; }
  }
  /* Order History Modal */
  .order-history-modal-backdrop {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.18);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 12000;
  }
  .order-history-modal-backdrop.active {
      display: flex !important;
  }
  .order-history-modal {
      background: #fffbe9;
      border: 2px solid #e3b36d;
      border-radius: 1.3rem;
      max-width: 720px;
      width: 94vw;
      max-height: 92vh;
      padding: 2.2rem 2.5rem 1.7rem 2.5rem;
      box-shadow: 0 10px 40px #b9450544, 0 1.5px 10px #e3b36d55;
      font-family: 'Inter',sans-serif;
      color: #4b3b2b;
      position: relative;
      overflow: auto;
      animation: popIn .25s cubic-bezier(0.36,1.2,0.5,1) 1;
  }
  @keyframes popIn {
      0% { transform: scale(0.96); opacity: 0.65;}
      100% { transform: none; opacity: 1;}
  }
  .order-history-modal h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.38rem;
      color: #b94505;
      text-align: center;
      margin-bottom: 1.1rem;
      font-weight: 700;
      letter-spacing: 0.03em;
  }
  .order-history-modal .close-order-history {
      position: absolute;
      top: 1.3rem; right: 1.6rem;
      background: #fff3e6;
      border: 1.5px solid #ffe4c4;
      font-size: 1.7rem;
      font-weight: 700;
      color: #b94505;
      cursor: pointer;
      border-radius: 50%;
      width: 2.5rem;
      height: 2.5rem;
      z-index: 1;
      display: flex; align-items: center; justify-content: center;
  }
  .order-history-modal .close-order-history:hover,
  .order-history-modal .close-order-history:focus {
      background: #ffdbaa;
      color: #7a3100;
      border: 1.5px solid #e3b36d;
  }
</style>


<div id="userDetailsModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="userDetailsTitle"
  aria-hidden="true" style="display:none;">
  <div class="modal dashboard-modal" role="document">
    <button class="close-button" aria-label="Close Dashboard">&times;</button>
    <h2 id="userDetailsTitle">Dashboard</h2>
    <div class="dashboard-grid">
      <div class="dashboard-card">
        <div class="dashboard-icon" aria-hidden="true">👤</div>
        <h3>User Info</h3>
        <p><strong>Name:</strong> <span id="userName"><?= htmlspecialchars($user['name']) ?></span></p>
        <p><strong>Email:</strong> <span id="userEmail"><?= htmlspecialchars($user['email']) ?></span></p>
      </div>
      <div class="dashboard-card">
        <div class="dashboard-icon" aria-hidden="true">📦</div>
        <h3>Orders</h3>
        <p>Pending: <?= $pending ?></p>
        <p>Delivered: <?= $delivered ?></p>
        <p>Cancelled: <?= $cancelled ?></p>
        <?php if ($user_id): ?>
        <button id="showFullOrderHistoryBtn" class="dashboard-btn">
          Show Full Order History
        </button>
        <?php endif; ?>
      </div>
      <!-- BALANCE MODAL CARD (with JS validation) -->
      <div class="dashboard-card">
        <div class="dashboard-icon" aria-hidden="true">💰</div>
        <h3>Balance</h3>
        <div class="balance-modal-balance" id="userBalance">₱0.00</div>
        <form class="balance-modal-form" id="balanceModalForm" autocomplete="off">
          <label for="balanceType" class="balance-input-label">Select Account Type:</label>
          <select id="balanceType" name="balanceType" class="balance-type-select" required>
            <option value="">-- Choose --</option>
            <option value="gcash">GCash</option>
            <option value="paymaya">PayMaya</option>
            <option value="bank">Bank Account</option>
          </select>
          <div class="balance-input-group" id="inputGcash" style="display:none;">
            <label class="balance-input-label" for="gcashNumber">GCash Number:</label>
            <input type="text" id="gcashNumber" name="gcashNumber" class="balance-input" maxlength="11" pattern="[0-9]{11}" inputmode="numeric" autocomplete="off">
          </div>
          <div class="balance-input-group" id="inputPaymaya" style="display:none;">
            <label class="balance-input-label" for="paymayaNumber">PayMaya Number:</label>
            <input type="text" id="paymayaNumber" name="paymayaNumber" class="balance-input" maxlength="11" pattern="[0-9]{11}" inputmode="numeric" autocomplete="off">
          </div>
          <div class="balance-input-group" id="inputBank" style="display:none;">
            <label class="balance-input-label" for="bankNumber">Bank Account Number:</label>
            <input type="text" id="bankNumber" name="bankNumber" class="balance-input" maxlength="12" pattern="[0-9]{12}" inputmode="numeric" autocomplete="off">
          </div>
          <!-- CASH IN AMOUNT -->
          <div class="balance-input-group" id="inputAmount" style="display:none;">
            <label class="balance-input-label" for="cashInAmount">Amount to Cash In (₱):</label>
            <input type="number" min="1" step="0.01" id="cashInAmount" name="cashInAmount" class="balance-input" placeholder="e.g. 500.00">
          </div>
          <button type="submit" class="dashboard-btn">Cash In</button>
        </form>
        <div id="balanceModalMessage" style="margin-top:0.7rem; color:#008b13; font-weight:600;display:none;"></div>
      </div>
    </div>
    <!-- LOGOUT BUTTON (unchanged) -->
    <form method="get" action="" style="margin-top: 2rem;">
      <button type="submit" name="logout" value="1" class="logout-btn-bottom"
        onclick="return confirm('Are you sure you want to log out?');">
        Log Out
      </button>
    </form>
  </div>
</div>
<!-- END DASHBOARD MODAL -->

<!-- Order History Fullscreen Modal -->
<div id="orderHistoryModalBackdrop" class="order-history-modal-backdrop" aria-modal="true" aria-hidden="true">
  <div class="order-history-modal" role="document">
    <button class="close-order-history" aria-label="Close Full Order History">&times;</button>
    <h2>Full Order History</h2>
    <div id="orderHistoryModalContent" style="overflow:auto; max-height:64vh;">
      <div style="text-align:center; color:#b94505; margin:2.5em 0;">Loading...</div>
    </div>
  </div>
</div>

<script>
  // Fill user data from PHP
  let user = window.user || {
    name: "<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : '' ?>",
    email: "<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : '' ?>"
  };

  let userBalance = Number(window.userBalance);
  if (
    isNaN(userBalance) ||
    userBalance === undefined ||
    userBalance === null ||
    userBalance === ""
  ) userBalance = 0.00;

  function updateBalanceDisplay(amount) {
    amount = Number(amount);
    if (isNaN(amount) || amount === undefined || amount === null || amount === "") amount = 0.00;
    document.getElementById('userBalance').textContent = `₱${amount.toFixed(2)}`;
  }

  document.addEventListener('DOMContentLoaded', function () {
    updateBalanceDisplay(userBalance);
  });

  const myAccountHeader = document.getElementById('myAccountHeader');
  if (
    user &&
    user.name !== "Juan Dela Cruz" &&
    user.email !== "juan@example.com" &&
    myAccountHeader
  ) {
    myAccountHeader.style.display = 'block';
  } else if (myAccountHeader) {
    myAccountHeader.style.display = 'none';
  }

  // DASHBOARD MODAL LOGIC
  const modal = document.getElementById('userDetailsModal');
  const closeBtn = modal.querySelector('.close-button');
  if (user) {
    document.getElementById('userName').textContent = user.name || 'N/A';
    document.getElementById('userEmail').textContent = user.email || 'N/A';

    myAccountHeader && myAccountHeader.addEventListener('click', (e) => {
      e.preventDefault();
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden', 'false');
      closeBtn.focus();
    });
    myAccountHeader && myAccountHeader.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        closeBtn.focus();
      }
    });

    closeBtn.addEventListener('click', () => {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
      myAccountHeader.focus();
    });

    modal.addEventListener('click', e => {
      if (e.target === modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        myAccountHeader.focus();
      }
    });

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && modal.style.display === 'flex') {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        myAccountHeader.focus();
      }
    });
  }

  // BALANCE MODAL LOGIC
  const balanceType = document.getElementById('balanceType');
  const inputGcash = document.getElementById('inputGcash');
  const inputPaymaya = document.getElementById('inputPaymaya');
  const inputBank = document.getElementById('inputBank');
  const inputAmount = document.getElementById('inputAmount');
  const cashInAmount = document.getElementById('cashInAmount');

  balanceType.addEventListener('change', function () {
    inputGcash.style.display = this.value === 'gcash' ? 'block' : 'none';
    inputPaymaya.style.display = this.value === 'paymaya' ? 'block' : 'none';
    inputBank.style.display = this.value === 'bank' ? 'block' : 'none';
    inputAmount.style.display = (this.value === 'gcash' || this.value === 'paymaya' || this.value === 'bank') ? 'block' : 'none';
  });

  // Only allow digits for number fields
  ['gcashNumber', 'paymayaNumber', 'bankNumber'].forEach(function(fieldId){
    const input = document.getElementById(fieldId);
    input && input.addEventListener('input', function(){
      this.value = this.value.replace(/\D/g, '');
      if (this.id === 'gcashNumber' || this.id === 'paymayaNumber')
        this.value = this.value.slice(0, 11); // max 11 digits
      if (this.id === 'bankNumber')
        this.value = this.value.slice(0, 12); // max 12 digits
    });
  });

  document.getElementById('balanceModalForm').addEventListener('submit', function (e) {
    e.preventDefault();
    let selectedType = balanceType.value;
    let number = '';
    let amount = cashInAmount.value.trim();

    // Enhanced validation for account fields
    if (selectedType === 'gcash') {
      number = document.getElementById('gcashNumber').value.trim();
      if (!/^[0-9]{11}$/.test(number)) {
        showBalanceMessage("Please enter a valid 11-digit GCash number (numbers only).", true);
        return;
      }
    } else if (selectedType === 'paymaya') {
      number = document.getElementById('paymayaNumber').value.trim();
      if (!/^[0-9]{11}$/.test(number)) {
        showBalanceMessage("Please enter a valid 11-digit PayMaya number (numbers only).", true);
        return;
      }
    } else if (selectedType === 'bank') {
      number = document.getElementById('bankNumber').value.trim();
      if (!/^[0-9]{12}$/.test(number)) {
        showBalanceMessage("Please enter a valid 12-digit Bank Account number (numbers only).", true);
        return;
      }
    } else {
      showBalanceMessage("Please select an account type.", true);
      return;
    }

    if (!amount || isNaN(amount) || parseFloat(amount) < 1) {
      showBalanceMessage("Please enter a valid amount to cash in (minimum ₱1.00).", true);
      return;
    }

    fetch('update_balance.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `amount=${encodeURIComponent(amount)}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        userBalance = Number(data.balance);
        updateBalanceDisplay(userBalance);
        showBalanceMessage("Cash in successful!", false);
        setTimeout(() => {
          document.getElementById('balanceModalForm').reset();
          inputGcash.style.display = 'none';
          inputPaymaya.style.display = 'none';
          inputBank.style.display = 'none';
          inputAmount.style.display = 'none';
        }, 1700);
      } else {
        showBalanceMessage(data.message || "Cash in failed.", true);
      }
    })
    .catch(() => {
      showBalanceMessage("Network error.", true);
    });
  });

  function showBalanceMessage(msg, isError) {
    const messageDiv = document.getElementById('balanceModalMessage');
    messageDiv.style.display = 'block';
    messageDiv.textContent = msg;
    messageDiv.style.color = isError ? '#c0392b' : '#008b13';
    setTimeout(() => { messageDiv.style.display = 'none'; }, 2000);
  }

  // ORDER HISTORY FULL POPUP LOGIC
  document.addEventListener('DOMContentLoaded', function () {
    const showBtn = document.getElementById('showFullOrderHistoryBtn');
    const modalBackdrop = document.getElementById('orderHistoryModalBackdrop');
    const closeBtn = modalBackdrop.querySelector('.close-order-history');
    const modalContent = document.getElementById('orderHistoryModalContent');

    if (showBtn && modalBackdrop && closeBtn && modalContent) {
      showBtn.addEventListener('click', function () {
        modalBackdrop.style.display = 'flex';
        modalBackdrop.classList.add('active');
        modalBackdrop.setAttribute('aria-hidden', 'false');
        modalContent.innerHTML = '<div style="text-align:center; color:#b94505; margin:2.5em 0;">Loading...</div>';
        fetch('order_history.php')
          .then(resp => resp.ok ? resp.text() : Promise.reject())
          .then(html => { modalContent.innerHTML = html; })
          .catch(() => {
            modalContent.innerHTML = '<div style="color:red; text-align:center; margin:2em 0;">Failed to load order history.</div>';
          });
        closeBtn.focus();
      });

      closeBtn.addEventListener('click', function() {
        modalBackdrop.style.display = 'none';
        modalBackdrop.classList.remove('active');
        modalBackdrop.setAttribute('aria-hidden', 'true');
        showBtn.focus();
      });
      modalBackdrop.addEventListener('click', function(e){
        if (e.target === modalBackdrop) {
          modalBackdrop.style.display = 'none';
          modalBackdrop.classList.remove('active');
          modalBackdrop.setAttribute('aria-hidden', 'true');
          showBtn.focus();
        }
      });
      document.addEventListener('keydown', function(e){
        if (e.key === 'Escape' && modalBackdrop.classList.contains('active')) {
          modalBackdrop.style.display = 'none';
          modalBackdrop.classList.remove('active');
          modalBackdrop.setAttribute('aria-hidden', 'true');
          showBtn.focus();
        }
      });
    }
  });
</script>