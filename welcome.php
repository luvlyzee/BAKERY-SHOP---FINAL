<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Welcome to Sweet Haven Bakery</title>
  
  <!-- Elegant yet simple fonts: Playfair Display for headers, Inter for body -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    html, body {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100%;
      font-family: 'Inter', 'Poppins', Arial, sans-serif;
      background: linear-gradient(135deg, #f9e4c8, #f48c06);
      color: #4a3c31;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
      overflow-x: hidden;
    }
    .topbar {
      width: 100%;
      padding: 1rem 2rem;
      display: flex;
      justify-content: flex-end;
      background: rgba(255, 255, 255, 0.5);
      position: sticky;
      top: 0;
      z-index: 1000;
      gap: 1.5rem;
      font-family: 'Inter', sans-serif;
    }
    .topbar a {
      text-decoration: none;
      color: #b94505;
      font-weight: 600;
      font-size: 1.08rem;
      padding: 0.45rem 1.1rem;
      border: 2px solid #b94505;
      border-radius: 25px;
      transition: background-color 0.3s, color 0.3s;
      font-family: 'Inter', sans-serif;
      letter-spacing: 0.02em;
    }
    .topbar a:hover {
      background-color: #b94505;
      color: white;
    }
    .container {
      width: 100%;
      max-width: 1200px;
      padding: 3rem 2rem;
      margin: 2rem auto 0;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      box-shadow: 0 12px 30px rgba(180, 130, 80, 0.5);
      animation: fadeInUp 1.2s ease forwards;
      text-align: center;
    }
    h1 {
      font-family: 'Playfair Display', serif;
      font-size: 3.1rem;
      margin-bottom: 0.6rem;
      font-weight: 700;
      color: #b94505;
      text-shadow: 0 3px 10px rgba(244, 140, 6, 0.18);
      letter-spacing: 0.04em;
    }
    p {
      font-family: 'Inter', 'Poppins', Arial, sans-serif;
      font-size: 1.23rem;
      margin-bottom: 2rem;
      color: #6b4f34;
      line-height: 1.55;
    }
    button.enter-btn {
      background: #f48c06;
      border: none;
      color: white;
      font-family: 'Inter', sans-serif;
      font-weight: 700;
      font-size: 1.18rem;
      padding: 0.85rem 2.5rem;
      border-radius: 40px;
      cursor: pointer;
      box-shadow: 0 6px 12px rgba(244, 140, 6, 0.6);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      letter-spacing: 0.02em;
    }
    button.enter-btn:hover {
      background: #b94505;
      box-shadow: 0 10px 20px rgba(177, 69, 5, 0.8);
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(50px);}
      to { opacity: 1; transform: translateY(0);}
    }
    @keyframes float {
      0%,100%{ transform:translateY(0);}
      50%{ transform:translateY(-15px);}
    }
    .categories-section,
    .customer-reviews,
    .newsletter-signup {
      width: 100%;
      max-width: 1200px;
      margin: 3rem auto;
      padding: 2rem;
      background-color: #fff8f0;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(180, 130, 80, 0.2);
    }
    .categories-section h2,
    .customer-reviews h2,
    .newsletter-signup h2 {
      font-family: 'Playfair Display', serif;
      font-size: 2.15rem;
      margin-bottom: 30px;
      color: #b94505;
      text-shadow: 0 1px 3px rgba(244, 140, 6, 0.18);
      letter-spacing: 0.03em;
    }
    .categories-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
    }
    .category-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.08);
      overflow: hidden;
      transition: transform 0.3s ease;
      text-decoration: none;
      color: inherit;
    }
    .category-card:hover { transform: scale(1.05);}
    .category-card img {
      width: 100%;
      height: 150px;
      object-fit: cover;
    }
    .category-card h3 {
      margin: 12px 0;
      font-size: 1.13rem;
      color: #4a3c31;
      font-family: 'Inter', 'Poppins', Arial, sans-serif;
      font-weight: 600;
      letter-spacing: 0.01em;
    }
    .reviews-grid {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
      justify-content: center;
    }
    .review-card {
      background: #fff8f0;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.10);
      padding: 1rem;
      max-width: 220px;
      font-style: italic;
      color: #4a3c31;
      text-align: left;
      font-family: 'Inter', serif;
    }
    .review-card footer {
      margin-top: 1rem;
      font-weight: 700;
      text-align: right;
      color: #b94505;
      font-family: 'Inter', sans-serif;
    }
    .newsletter-signup p { margin-bottom: 1rem;}
    .newsletter-signup form {
      margin-top: 1rem;
      display: flex;
      justify-content: center;
      gap: 0.75rem;
      flex-wrap: wrap;
      font-family: 'Inter', sans-serif;
    }
    .newsletter-signup input[type="email"] {
      padding: 0.6rem 1rem;
      border: 2px solid #b94505;
      border-radius: 25px;
      width: 250px;
      font-size: 1rem;
      outline-offset: 2px;
      font-family: 'Inter', sans-serif;
    }
    .newsletter-signup button {
      background-color: #f48c06;
      border: none;
      color: white;
      font-weight: 700;
      padding: 0.6rem 1.5rem;
      border-radius: 25px;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(244, 140, 6, 0.6);
      transition: background-color 0.3s ease;
      font-family: 'Inter', sans-serif;
    }
    .newsletter-signup button:hover { background-color: #b94505;}
    #newsletter-message {
      margin-top: 0.5rem;
      color: green;
      font-weight: 600;
      display: none;
      font-family: 'Inter', sans-serif;
    }
    @media (max-width: 480px) {
      h1 { font-size: 2rem;}
      p { font-size: 1rem;}
      button.enter-btn { font-size: 1rem; padding: 0.75rem 2rem;}
      .reviews-grid { flex-direction: column; gap: 1rem;}
      .newsletter-signup form { flex-direction: column; gap: 1rem;}
      .newsletter-signup input[type="email"] { width: 100%; max-width: 300px;}
    }
  </style>
</head>
<body>
  <!-- Top bar -->
  <div class="topbar">
    <a href="about.php">About Us</a>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  </div>

  <!-- Welcome Container -->
  <div class="container" role="main">
    <h1>Welcome to Sweet Haven Bakery</h1>
    <p>Discover the freshest breads, pastries, and cakes baked with love. Order online and enjoy delightful treats delivered straight to your door!</p>
    <button class="enter-btn" onclick="window.location.href='index.php'">Enter Shop</button>
  </div>

  <!-- Shop by Category Section -->
  <section class="categories-section" aria-label="Shop by Category">
    <h2>Shop by Category</h2>
    <div class="categories-grid">
      <a href="index.php#breads" class="category-card">
        <img src="https://i.pinimg.com/736x/ec/c1/6c/ecc16ca8321dd727f5707eb0989064e2.jpg" alt="Breads" />
        <h3>Breads</h3>
      </a>
      <a href="index.php#pastries" class="category-card">
        <img src="https://eskipaper.com/images/pastries-2.jpg" alt="Pastries" />
        <h3>Pastries</h3>
      </a>
      <a href="index.php#cakes" class="category-card">
        <img src="https://th.bing.com/th/id/OIP.zA-4yS5Lox8cwGrDGCj28gHaFj?rs=1&pid=ImgDetMain" alt="Cakes" />
        <h3>Cakes</h3>
      </a>
      <a href="index.php#cookies" class="category-card">
        <img src="https://th.bing.com/th/id/OIP.5fSd00-ahKzP75bQmBajaQHaHa?rs=1&pid=ImgDetMain" alt="Cookies" />
        <h3>Cookies</h3>
      </a>
    </div>
  </section>

  <!-- Customer Reviews Section -->
  <section class="customer-reviews" aria-label="Customer Reviews">
    <h2>What Our Customers Say</h2>
    <div class="reviews-grid">
      <blockquote class="review-card">
        <p>"The breads are always fresh and the pastries melt in my mouth! Highly recommend Sweet Treats Bakery."</p>
        <footer>- Alyanna Ria</footer>
      </blockquote>
      <blockquote class="review-card">
        <p>"Delicious cakes and friendly service. I always order for my family’s special occasions."</p>
        <footer>- Christine.</footer>
      </blockquote>
      <blockquote class="review-card">
        <p>"Great quality, quick delivery, and tasty cookies! Will order again."</p>
        <footer>- Andrea.</footer>
      </blockquote>
    </div>
  </section>

  <!-- Newsletter Signup Section -->
  <section class="newsletter-signup" aria-label="Newsletter Signup">
    <h2>Stay in Touch</h2>
    <p>Subscribe to our newsletter for exclusive offers and new product updates!</p>
    
    <form id="newsletter-form" method="POST">
      <input type="email" id="newsletter-email" name="newsletter-email" placeholder="Enter your email" required />
      <button type="submit">Subscribe</button>
    </form>
    
    <p id="newsletter-message" role="alert" aria-live="polite" style="margin-top: 10px; font-weight: bold;"></p>
    <?php
      // Simple PHP handler for newsletter form (fallback for JS-disabled users)
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter-email'])) {
        $email = trim($_POST['newsletter-email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          echo "<script>document.addEventListener('DOMContentLoaded', function(){ 
            var msg = document.getElementById('newsletter-message'); 
            if(msg){msg.textContent='Invalid email address.'; msg.style.color='red'; msg.style.display='block';} 
          });</script>";
        } else {
          // Here you should actually save the email to a database or send it somewhere
          echo "<script>document.addEventListener('DOMContentLoaded', function(){ 
            var msg = document.getElementById('newsletter-message'); 
            if(msg){msg.textContent='Thank you for subscribing!'; msg.style.color='green'; msg.style.display='block'; 
            document.getElementById('newsletter-email').value = '';} 
          });</script>";
        }
      }
    ?>
  </section>

  <script>
  const newsletterForm = document.getElementById('newsletter-form');
  const newsletterMessage = document.getElementById('newsletter-message');

  newsletterForm && newsletterForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const emailInput = document.getElementById('newsletter-email');
    const email = emailInput.value;

    fetch('subscriber.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ 'newsletter-email': email })
    })
    .then(response => response.text())
    .then(result => {
      const trimmed = result.trim();
      newsletterMessage.style.display = "block";
      if (trimmed === 'success') {
        newsletterMessage.textContent = "Thank you for subscribing!";
        newsletterMessage.style.color = "green";
        emailInput.value = '';
      } else if (trimmed === 'invalid') {
        newsletterMessage.textContent = "Invalid email address.";
        newsletterMessage.style.color = "red";
      } else {
        newsletterMessage.textContent = "Subscription failed. Please try again.";
        newsletterMessage.style.color = "red";
      }
    })
    .catch(error => {
      console.error('Error:', error);
      newsletterMessage.style.display = "block";
      newsletterMessage.textContent = "Something went wrong.";
      newsletterMessage.style.color = "red";
    });
  });
  </script>
</body>
</html>