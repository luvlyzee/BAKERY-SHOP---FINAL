let products = {};

function renderProducts() {
  for (const category in products) {
    const grid = document.getElementById(`${category}-grid`);
    grid.innerHTML = ''; // clear previous, if any

    products[category].forEach(product => {
      const productCard = document.createElement('div');
      productCard.className = 'product-card';
      productCard.innerHTML = `
        <img src="${product.img}" alt="${product.name}" class="product-img">
        <div class="product-info">
          <h3 class="product-name">${product.name}</h3>
          <p class="product-price">&#8369;${product.price.toFixed(2)}</p>
          <button class="add-to-cart">Add to Cart</button>
        </div>
      `;
      grid.appendChild(productCard);
    });
  }
}

function fetchProductsFromDB() {
  fetch('get_products.php')
    .then(res => res.json())
    .then(data => {
      // Optional: If backend returns an error (like {success:false}), handle it
      if (!data || (Array.isArray(data) && data.length === 0) || (data.success === false)) {
  
        return;
      }
      products = data;
      renderProducts();
      // After rendering, re-init cart buttons!
      initCart();
    })
    .catch(err => {
      console.error(err);
    });
}

document.addEventListener('DOMContentLoaded', function() {
  fetchProductsFromDB();
  initSlideshow();
});

// Cart functionality
let cart = [];

function getProductInfo(productCard) {
  return {
    id: productCard.querySelector('.product-name').textContent.toLowerCase().replace(/\s+/g, '-'),
    name: productCard.querySelector('.product-name').textContent,
    price: parseFloat(productCard.querySelector('.product-price').textContent.replace(/[^0-9.]/g, '')),
    img: productCard.querySelector('.product-img').src
  };
}

function addToCart(productCard) {
  const product = getProductInfo(productCard);

  // Check if item already exists in cart
  const existingItem = cart.find(item => item.id === product.id);

  if (existingItem) {
    existingItem.quantity += 1;
  } else {
    cart.push({
      ...product,
      quantity: 1
    });
  }

  updateCartDisplay();
  saveCartToLocalStorage();
}

function updateCartDisplay() {
  const cartItemsElement = document.getElementById('cart-items');
  const cartTotalElement = document.getElementById('cart-total');
  const checkoutButton = document.getElementById('checkout-button');

  // Clear current cart display
  cartItemsElement.innerHTML = '';

  // Add each item to cart display
  let total = 0;
  cart.forEach(item => {
    const itemElement = document.createElement('div');
    itemElement.className = 'cart-item';
    itemElement.innerHTML = `
      <img src="${item.img}" alt="${item.name}" width="50">
      <div class="cart-item-details">
        <span class="item-name">${item.name}</span>
        <span class="item-quantity">Quantity: ${item.quantity}</span>
      </div>
      <div class="cart-item-controls">
        <button class="qty-btn minus" data-id="${item.id}">−</button>
        <button class="qty-btn plus" data-id="${item.id}">+</button>
      </div>
      <span class="item-price">₱${(item.price * item.quantity).toFixed(2)}</span>
      <button class="remove-item" data-id="${item.id}">×</button>
    `;
    cartItemsElement.appendChild(itemElement);

    total += item.price * item.quantity;
  });

  // Update total
  cartTotalElement.textContent = `Total: ₱${total.toFixed(2)}`;

  // Enable/disable checkout button
  checkoutButton.disabled = cart.length === 0;
  checkoutButton.setAttribute('aria-disabled', cart.length === 0);
}

function saveCartToLocalStorage() {
  localStorage.setItem('bakeryCart', JSON.stringify(cart));
}

function loadCartFromLocalStorage() {
  const savedCart = localStorage.getItem('bakeryCart');
  if (savedCart) {
    cart = JSON.parse(savedCart);
    updateCartDisplay();
  }
}

function handleQuantityChange(productId, change) {
  const itemIndex = cart.findIndex(item => item.id === productId);
  if (itemIndex !== -1) {
    const newQuantity = cart[itemIndex].quantity + change;

    if (newQuantity <= 0) {
      cart.splice(itemIndex, 1); // Remove item if quantity reaches 0
    } else {
      cart[itemIndex].quantity = newQuantity;
    }

    updateCartDisplay();
    saveCartToLocalStorage();
    updateModalAmountPayable();
  }
}

// Initialize the cart functionality
function initCart() {
  // Load cart from localStorage when page loads
  loadCartFromLocalStorage();

  // Add click handlers to all "Add to Cart" buttons
  document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
      const productCard = this.closest('.product-card');
      addToCart(productCard);

      // Add visual feedback
      this.textContent = 'Added!';
      setTimeout(() => {
        this.textContent = 'Add to Cart';
      }, 1000);
    });
  });

  // Handle quantity changes and removals
  document.getElementById('cart-items').addEventListener('click', function(e) {
    if (e.target.classList.contains('minus')) {
      handleQuantityChange(e.target.getAttribute('data-id'), -1);
    } else if (e.target.classList.contains('plus')) {
      handleQuantityChange(e.target.getAttribute('data-id'), 1);
    } else if (e.target.classList.contains('remove-item')) {
      const productId = e.target.getAttribute('data-id');
      cart = cart.filter(item => item.id !== productId);
      updateCartDisplay();
      saveCartToLocalStorage();
    }
  });

  // Checkout button functionality
  document.getElementById('checkout-button').addEventListener('click', function() {
    if (cart.length > 0) {
      document.getElementById('checkout-modal').style.display = 'flex';
      updateModalAmountPayable();
    }
  });

  // Close modal functionality
  document.querySelector('.close-btn').addEventListener('click', function() {
    document.getElementById('checkout-modal').style.display = 'none';
  });

  // Delivery mode toggle
  document.querySelectorAll('input[name="delivery-mode"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const addressField = document.querySelector('.delivery-address-group');
      if (this.value === 'delivery') {
        addressField.style.display = 'block';
        document.getElementById('cust-address').required = true;
      } else {
        addressField.style.display = 'none';
        document.getElementById('cust-address').required = false;
      }
    });
  });

  // Close confirmation message
  document.getElementById('confirm-close-btn').addEventListener('click', function() {
    document.getElementById('confirmation-message').style.display = 'none';
  });

  // Set minimum date to today
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('delivery-date').min = today;
}

// Initialize slideshow
function initSlideshow() {
  let slideIndex = 0;
  const slides = document.querySelectorAll('.slide');

  function showSlides() {
    slides.forEach((slide, i) => {
      slide.style.display = i === slideIndex ? 'block' : 'none';
    });
    slideIndex = (slideIndex + 1) % slides.length;
    setTimeout(showSlides, 3000);
  }

  showSlides();
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  renderProducts(); // Render products first
  initCart();       // Then initialize cart functionality
  initSlideshow();  // Then initialize slideshow
});

function updateModalAmountPayable() {
  const amount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const payableElem = document.getElementById('modal-amount-payable');
  if (payableElem) {
    payableElem.textContent = '₱' + amount.toFixed(2);
  }
}

// --- Checkout Form Submission for AJAX Order (SINGLE INSTANCE ONLY!!!) ---
document.addEventListener("DOMContentLoaded", function() {
  const checkoutForm = document.getElementById("checkout-form");
  const checkoutModal = document.getElementById("checkout-modal");

  if (checkoutForm) {
    checkoutForm.addEventListener("submit", async function(e) {
      e.preventDefault();

      // Gather user data from form
      const name = document.getElementById('cust-name').value.trim();
      const email = document.getElementById('cust-email').value.trim();
      const phone = document.getElementById('cust-phone').value.trim();
      const address = document.getElementById('cust-address').value.trim();
      const deliveryMode = checkoutForm.querySelector('input[name="delivery-mode"]:checked').value;
      const deliveryDate = document.getElementById('delivery-date').value;
      const deliveryTime = document.getElementById('delivery-time').value;

      // Validate required fields (you may want to do more thorough validation)
      if (!name || !email || !phone || !deliveryDate || !deliveryTime) {
        alert('Please fill in all required fields.');
        return;
      }

      // Get cart data from localStorage or however your cart is managed
      let cartData = JSON.parse(localStorage.getItem("bakeryCart") || "[]");
      if (!Array.isArray(cartData) || cartData.length === 0) {
        alert("Your cart is empty.");
        return;
      }

      // Compute total
      let total = 0;
      cartData.forEach(item => {
        total += (parseFloat(item.price) || 0) * (parseInt(item.quantity) || 0);
      });

      // Prepare payload for place_order.php
      const payload = {
        items: cartData,
        total: total,
        delivery_mode: deliveryMode,
        address: address,
        phone: phone,
        delivery_date: deliveryDate,
        delivery_time: deliveryTime,
        cust_name: name,
        cust_email: email
      };

      try {
        // Disable button to prevent double submit
        const payBtn = checkoutForm.querySelector('.pay-btn');
        if (payBtn) payBtn.disabled = true;

        const response = await fetch('place_order.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await response.json();

        // Enable button again
        if (payBtn) payBtn.disabled = false;

        if (result && result.success && result.order_id) {
          // Clear the cart
          localStorage.removeItem("bakeryCart");

          // Hide the modal
          if (checkoutModal) checkoutModal.style.display = "none";

          // Redirect to receipt page (show receipt immediately)
          window.location.href = 'receipt.php?order_id=' + encodeURIComponent(result.order_id);

        } else if (result && result.error) {
          alert("Order failed: " + result.error + (result.details ? '\n' + result.details : ''));
        } else {
          alert("Something went wrong while placing your order.");
        }
      } catch (err) {
        if (checkoutForm.querySelector('.pay-btn')) checkoutForm.querySelector('.pay-btn').disabled = false;
        alert("Failed to place order. Please try again.\n" + err);
      }
    });
  }
});