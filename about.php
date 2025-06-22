<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>About Us - Sweet Haven Bakery</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body {
      font-family: 'Quicksand', sans-serif;
      background: linear-gradient(135deg, #f9e4c8, #f48c06);
      color: #4a3c31;
      margin: 0;
      padding: 2rem;
      text-align: center;
    }

    .container {
      background-color: #fff8f0;
      padding: 2rem;
      border-radius: 20px;
      max-width: 800px;
      margin: auto;
      box-shadow: 0 10px 25px rgba(180, 130, 80, 0.2);
    }

    h1 {
      color: #b94505;
      margin-bottom: 1rem;
    }

    .member {
      margin-bottom: 1.5rem;
    }

    .member h3 {
      margin: 0.5rem 0;
    }

    .back-btn {
      margin-top: 2rem;
      padding: 0.7rem 2rem;
      background-color: #f48c06;
      color: #fff;
      font-weight: bold;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(244, 140, 6, 0.6);
    }

    .back-btn:hover {
      background-color: #b94505;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>About Us</h1>
    <div class="member">
      <h3>Aquino, Christine Allysa T.</h3>
      <p>Website Developer and Marketing</p>
      <p>📞 0963-649-1596<br>📧 allysa@sweethaven.com</p>
    </div>
    <div class="member">
      <h3>Magpantay, Alyanna Ria O.</h3>
      <p>Website Developer and Marketing</p>
      <p>📞 0969-081-2180<br>📧 alyanna@sweethaven.com</p>
    </div>
    <div class="member">
      <h3>Malabuyoc, Andrea M.</h3>
      <p>Website Developer & Marketing</p>
      <p>📞 0969-210-7175<br>📧 andrea@sweethaven.com</p>
    </div>
    <button class="back-btn" onclick="window.location.href='welcome.php'">← Back to Home</button>
  </div>
</body>
</html>
