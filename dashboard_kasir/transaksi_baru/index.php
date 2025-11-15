<?php
session_start();
include "../../database.php";

// Pastikan hanya petugas yang bisa mengakses
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Petugas') {
  header("Location: ../../");
  exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$id_outlet = $_SESSION['id_outlet'];

// Ambil kategori unik
$query_kategori = "SELECT DISTINCT kategori FROM produk WHERE id_outlet = ? ORDER BY kategori";
$stmt_kat = $conn->prepare($query_kategori);
$stmt_kat->bind_param("i", $id_outlet);
$stmt_kat->execute();
$result_kategori = $stmt_kat->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transaksi Baru | LakuGroup</title>
  <link rel="icon" type="image/png" href="../../images/logo_2.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      overflow-x: hidden;
    }

    .main-content {
      margin-left: 250px;
      transition: all 0.3s ease;
    }

    .header {
      background-color: #c7290f;
      color: #fff;
      padding: 15px 25px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 999;
    }

    .username { font-weight: 600; }

    .logout-btn {
      background-color: #fff;
      color: #c7290f;
      border: none;
      padding: 6px 14px;
      border-radius: 6px;
      transition: 0.3s;
      text-decoration: none;
    }

    .logout-btn:hover { background-color: #eee; }

    .menu-toggle {
      display: none;
      background-color: #fff;
      color: #c7290f;
      border: none;
      font-size: 1.5rem;
      padding: 8px;
      border-radius: 6px;
      margin-right: 15px;
      cursor: pointer;
    }

    @media (max-width: 991px) {
      .main-content { margin-left: 0; }
      .menu-toggle { display: inline-block; }
    }

    /* Product Section */
    .product-section {
      background: #fff;
      border-radius: 14px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .search-box {
      position: relative;
      margin-bottom: 20px;
    }

    .search-box input {
      padding-left: 40px;
      border-radius: 10px;
      border: 1px solid #dee2e6;
    }

    .search-box i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
    }

    .category-filter {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .category-btn {
      padding: 8px 16px;
      border-radius: 20px;
      border: 2px solid #c7290f;
      background: #fff;
      color: #c7290f;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
    }

    .category-btn:hover, .category-btn.active {
      background: #c7290f;
      color: #fff;
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 15px;
      max-height: 60vh;
      overflow-y: auto;
      padding-right: 10px;
    }

    .product-card {
      background: #fff;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      padding: 15px;
      cursor: pointer;
      transition: all 0.3s;
      text-align: center;
    }

    .product-card:hover {
      border-color: #c7290f;
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(199, 41, 15, 0.2);
    }

    .product-card.selected {
      border-color: #c7290f;
      background: rgba(199, 41, 15, 0.05);
    }

    .product-icon {
      font-size: 2.5rem;
      color: #c7290f;
      margin-bottom: 10px;
    }

    .product-name {
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 8px;
      color: #212529;
      min-height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .product-price {
      color: #c7290f;
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 8px;
    }

    .product-category {
      display: inline-block;
      font-size: 0.7rem;
      padding: 4px 10px;
      border-radius: 12px;
      font-weight: 600;
    }

    .category-makanan {
      background: rgba(199, 41, 15, 0.1);
      color: #c7290f;
    }

    .category-minuman {
      background: rgba(13, 110, 253, 0.1);
      color: #0d6efd;
    }

    .category-topping {
      background: rgba(25, 135, 84, 0.1);
      color: #198754;
    }

    .category-bundle {
      background: rgba(255, 193, 7, 0.1);
      color: #ffc107;
    }

    .loading {
      text-align: center;
      padding: 40px;
      color: #6c757d;
    }

    .loading i {
      font-size: 2rem;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Cart Section */
    .cart-section {
      background: #fff;
      border-radius: 14px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      position: sticky;
      top: 85px;
      max-height: calc(100vh - 100px);
      overflow-y: auto;
    }

    .cart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e9ecef;
    }

    .cart-header h5 {
      margin: 0;
      font-weight: 700;
    }

    .clear-cart {
      background: none;
      border: none;
      color: #dc3545;
      font-weight: 500;
      cursor: pointer;
    }

    .cart-items {
      max-height: 30vh;
      overflow-y: auto;
      margin-bottom: 20px;
    }

    .cart-item {
      display: flex;
      align-items: center;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 10px;
      background: #f8f9fa;
    }

    .cart-item-info {
      flex: 1;
      margin-right: 10px;
    }

    .cart-item-name {
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 3px;
    }

    .cart-item-price {
      color: #6c757d;
      font-size: 0.85rem;
    }

    .cart-item-qty {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .qty-btn {
      width: 28px;
      height: 28px;
      border-radius: 6px;
      border: 1px solid #dee2e6;
      background: #fff;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
    }

    .qty-btn:hover {
      background: #c7290f;
      color: #fff;
      border-color: #c7290f;
    }

    .qty-input {
      width: 40px;
      text-align: center;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 4px;
    }

    .remove-item {
      background: none;
      border: none;
      color: #dc3545;
      font-size: 1.2rem;
      cursor: pointer;
      margin-left: 10px;
    }

    .cart-summary {
      border-top: 2px solid #e9ecef;
      padding-top: 15px;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      font-size: 0.95rem;
    }

    .summary-row.total {
      font-size: 1.2rem;
      font-weight: 700;
      color: #c7290f;
      padding-top: 10px;
      border-top: 2px solid #e9ecef;
      margin-bottom: 15px;
    }

    .payment-section {
      margin-top: 15px;
    }

    .payment-section label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .payment-section select,
    .payment-section input {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #dee2e6;
      margin-bottom: 12px;
      font-size: 0.95rem;
    }

    .payment-section input:focus {
      outline: none;
      border-color: #c7290f;
      box-shadow: 0 0 0 0.2rem rgba(199, 41, 15, 0.1);
    }

    .change-amount {
      background: #f8f9fa;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 15px;
    }

    .change-amount .label {
      font-size: 0.85rem;
      color: #6c757d;
      margin-bottom: 5px;
    }

    .change-amount .amount {
      font-size: 1.1rem;
      font-weight: 700;
      color: #198754;
    }

    .change-amount.insufficient {
      background: #fff5f5;
    }

    .quick-amount {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      margin-bottom: 15px;
    }

    .quick-amount-btn {
      padding: 8px;
      border: 1px solid #dee2e6;
      background: #fff;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 500;
      transition: all 0.3s;
    }

    .quick-amount-btn:hover {
      background: #c7290f;
      color: #fff;
      border-color: #c7290f;
    }

    .btn-checkout {
      width: 100%;
      padding: 12px;
      background: #c7290f;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn-checkout:hover:not(:disabled) {
      background: #a61f0b;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(199, 41, 15, 0.3);
    }

    .btn-checkout:disabled {
      background: #6c757d;
      cursor: not-allowed;
      transform: none;
    }

    .empty-cart {
      text-align: center;
      padding: 40px 20px;
      color: #6c757d;
    }

    .empty-cart i {
      font-size: 3rem;
      margin-bottom: 15px;
      opacity: 0.5;
    }

    /* Success Modal */
    .modal-success .modal-header {
      background: linear-gradient(135deg, #198754 0%, #157347 100%);
      color: white;
      border-radius: 14px 14px 0 0;
      padding: 20px 25px;
    }

    .modal-success .modal-body {
      padding: 30px 25px;
    }

    .success-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #198754 0%, #157347 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      animation: scaleIn 0.5s ease;
    }

    .success-icon i {
      font-size: 3rem;
      color: white;
    }

    @keyframes scaleIn {
      0% {
        transform: scale(0);
        opacity: 0;
      }
      50% {
        transform: scale(1.1);
      }
      100% {
        transform: scale(1);
        opacity: 1;
      }
    }

    .transaction-details {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 20px;
      margin: 20px 0;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid #dee2e6;
    }

    .detail-row:last-child {
      border-bottom: none;
    }

    .detail-label {
      font-weight: 600;
      color: #495057;
      font-size: 0.95rem;
    }

    .detail-value {
      font-weight: 700;
      color: #212529;
      font-size: 0.95rem;
      text-align: right;
    }

    .detail-row.highlight {
      background: rgba(25, 135, 84, 0.1);
      padding: 15px;
      border-radius: 8px;
      margin-top: 10px;
    }

    .detail-row.highlight .detail-label,
    .detail-row.highlight .detail-value {
      font-size: 1.1rem;
      color: #198754;
    }

    .items-list {
      max-height: 200px;
      overflow-y: auto;
      margin: 15px 0;
    }

    .item-row {
      display: flex;
      justify-content: space-between;
      padding: 10px;
      background: white;
      border-radius: 8px;
      margin-bottom: 8px;
    }

    .item-name {
      font-weight: 600;
      font-size: 0.9rem;
      color: #212529;
    }

    .item-qty {
      color: #6c757d;
      font-size: 0.85rem;
    }

    .item-price {
      font-weight: 700;
      color: #c7290f;
      font-size: 0.9rem;
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    .btn-print {
      flex: 1;
      padding: 12px;
      background: #c7290f;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-print:hover {
      background: #a61f0b;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(199, 41, 15, 0.3);
    }

    .btn-new {
      flex: 1;
      padding: 12px;
      background: #198754;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-new:hover {
      background: #157347;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
    }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #c7290f; border-radius: 10px; }
  </style>
</head>
<body>
  <?php include "../sidebar_kasir.php"; ?>

  <div class="overlay" id="overlay"></div>

  <div class="main-content" id="main-content">
    <!-- Header -->
    <div class="header">
      <div class="d-flex align-items-center">
        <button class="menu-toggle" id="menu-toggle"><i class="bi bi-list"></i></button>
        <div class="username">
          <i class="bi bi-person-circle me-2"></i> 
          Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']); ?></strong> 
          (<?= htmlspecialchars($_SESSION['role']); ?>)
        </div>
      </div>
      <a href="../../logout.php" class="btn logout-btn">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
      <div class="row">
        <!-- Product Section -->
        <div class="col-lg-8 mb-4">
          <div class="product-section">
            <h5 class="fw-bold mb-3">Pilih Produk</h5>
            
            <!-- Search Box -->
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="text" class="form-control" id="searchProduct" placeholder="Cari produk...">
            </div>

            <!-- Category Filter -->
            <div class="category-filter">
              <button class="category-btn active" data-category="all">Semua</button>
              <?php while($kat = $result_kategori->fetch_assoc()): ?>
                <button class="category-btn" data-category="<?= htmlspecialchars($kat['kategori']) ?>">
                  <?= htmlspecialchars($kat['kategori']) ?>
                </button>
              <?php endwhile; ?>
            </div>

            <!-- Product Grid -->
            <div class="product-grid" id="productGrid">
              <div class="loading">
                <i class="bi bi-arrow-repeat"></i>
                <p>Memuat produk...</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Cart Section -->
        <div class="col-lg-4">
          <div class="cart-section">
            <div class="cart-header">
              <h5><i class="bi bi-cart3 me-2"></i>Keranjang</h5>
              <button class="clear-cart" onclick="clearCart()">
                <i class="bi bi-trash"></i> Kosongkan
              </button>
            </div>

            <div class="cart-items" id="cartItems">
              <div class="empty-cart">
                <i class="bi bi-cart-x"></i>
                <p>Keranjang masih kosong</p>
              </div>
            </div>

            <div class="cart-summary" id="cartSummary" style="display: none;">
              <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">Rp 0</span>
              </div>
              <div class="summary-row total">
                <span>Total:</span>
                <span id="total">Rp 0</span>
              </div>

              <div class="payment-section">
                <label><i class="bi bi-credit-card me-2"></i>Metode Pembayaran:</label>
                <select id="paymentMethod" class="form-select">
                  <option value="Tunai">Tunai</option>
                  <option value="Transfer">Transfer Bank</option>
                  <option value="QRIS">QRIS</option>
                  <option value="Debit">Kartu Debit</option>
                </select>

                <label><i class="bi bi-cash me-2"></i>Jumlah Bayar:</label>
                <input type="number" id="paymentAmount" class="form-control" placeholder="Masukkan jumlah bayar" min="0" step="1000">

                <!-- Quick Amount Buttons -->
                <div class="quick-amount" id="quickAmountButtons" style="display: none;">
                  <button type="button" class="quick-amount-btn" onclick="setQuickAmount('exact')">Uang Pas</button>
                  <button type="button" class="quick-amount-btn" onclick="setQuickAmount(50000)">50K</button>
                  <button type="button" class="quick-amount-btn" onclick="setQuickAmount(100000)">100K</button>
                </div>

                <!-- Change Amount -->
                <div class="change-amount" id="changeAmount" style="display: none;">
                  <div class="label">Kembalian:</div>
                  <div class="amount" id="changeValue">Rp 0</div>
                </div>
              </div>

              <button class="btn-checkout" id="btnCheckout" onclick="checkout()" disabled>
                <i class="bi bi-check-circle me-2"></i>Proses Pembayaran
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content modal-success">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Transaksi Berhasil</h5>
        </div>
        <div class="modal-body text-center">
          <div class="success-icon">
            <i class="bi bi-check-lg"></i>
          </div>
          
          <h4 class="mb-3">Pembayaran Berhasil!</h4>
          
          <div class="transaction-details">
            <div class="detail-row">
              <span class="detail-label">ID Transaksi</span>
              <span class="detail-value" id="modalTransactionId">#-</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Tanggal & Waktu</span>
              <span class="detail-value" id="modalDateTime">-</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Kasir</span>
              <span class="detail-value" id="modalCashier">-</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Metode Pembayaran</span>
              <span class="detail-value" id="modalPaymentMethod">-</span>
            </div>
            
            <h6 class="mt-3 mb-2 text-start">Item Dibeli:</h6>
            <div class="items-list" id="modalItemsList"></div>
            
            <div class="detail-row">
              <span class="detail-label">Total Belanja</span>
              <span class="detail-value" id="modalTotal">Rp 0</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Jumlah Bayar</span>
              <span class="detail-value" id="modalPayment">Rp 0</span>
            </div>
            <div class="detail-row highlight">
              <span class="detail-label">Kembalian</span>
              <span class="detail-value" id="modalChange">Rp 0</span>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-print" onclick="printReceipt()">
              <i class="bi bi-printer"></i>
              Cetak Struk
            </button>
            <button type="button" class="btn-new" onclick="newTransaction()">
              <i class="bi bi-plus-circle"></i>
              Transaksi Baru
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let cart = [];
    let totalAmount = 0;
    let currentTransactionId = null;

    // Load products on page load
    document.addEventListener('DOMContentLoaded', function() {
      loadProducts();
      
      // Payment amount input event
      document.getElementById('paymentAmount').addEventListener('input', calculateChange);
      document.getElementById('paymentMethod').addEventListener('change', handlePaymentMethodChange);
    });

    // Load products from server
    function loadProducts() {
      fetch('load_produk.php')
        .then(response => response.text())
        .then(html => {
          document.getElementById('productGrid').innerHTML = html;
          
          // Add click event to product cards
          document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', function() {
              addToCart(this);
            });
          });
        })
        .catch(error => {
          document.getElementById('productGrid').innerHTML = `
            <div class="loading">
              <i class="bi bi-exclamation-triangle text-danger"></i>
              <p>Gagal memuat produk</p>
            </div>
          `;
        });
    }

    // Add to cart
    function addToCart(card) {
      const id = card.dataset.id;
      const name = card.dataset.name;
      const price = parseFloat(card.dataset.price);
      const stock = parseInt(card.dataset.stock);

      // Check if item already in cart
      const existingItem = cart.find(item => item.id === id);
      
      if (existingItem) {
        if (existingItem.qty < stock) {
          existingItem.qty++;
        } else {
          alert('Stok tidak mencukupi!');
          return;
        }
      } else {
        cart.push({ id, name, price, qty: 1, stock });
      }

      updateCart();
    }

    // Update cart display
    function updateCart() {
      const cartItems = document.getElementById('cartItems');
      const cartSummary = document.getElementById('cartSummary');

      if (cart.length === 0) {
        cartItems.innerHTML = `
          <div class="empty-cart">
            <i class="bi bi-cart-x"></i>
            <p>Keranjang masih kosong</p>
          </div>
        `;
        cartSummary.style.display = 'none';
        return;
      }

      cartSummary.style.display = 'block';
      
      let html = '';
      let subtotal = 0;

      cart.forEach((item, index) => {
        const itemTotal = item.price * item.qty;
        subtotal += itemTotal;

        html += `
          <div class="cart-item">
            <div class="cart-item-info">
              <div class="cart-item-name">${item.name}</div>
              <div class="cart-item-price">Rp ${item.price.toLocaleString('id-ID')}</div>
            </div>
            <div class="cart-item-qty">
              <button class="qty-btn" onclick="decreaseQty(${index})">-</button>
              <input type="number" class="qty-input" value="${item.qty}" readonly>
              <button class="qty-btn" onclick="increaseQty(${index})">+</button>
            </div>
            <button class="remove-item" onclick="removeItem(${index})">
              <i class="bi bi-x-circle"></i>
            </button>
          </div>
        `;
      });

      cartItems.innerHTML = html;
      totalAmount = subtotal;
      document.getElementById('subtotal').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
      document.getElementById('total').textContent = `Rp ${subtotal.toLocaleString('id-ID')}`;
      
      handlePaymentMethodChange();
      calculateChange();
    }

    // Handle payment method change
    function handlePaymentMethodChange() {
      const paymentMethod = document.getElementById('paymentMethod').value;
      const quickAmountButtons = document.getElementById('quickAmountButtons');
      const paymentAmountInput = document.getElementById('paymentAmount');
      
      if (paymentMethod === 'Tunai') {
        quickAmountButtons.style.display = 'grid';
        paymentAmountInput.removeAttribute('readonly');
      } else {
        quickAmountButtons.style.display = 'none';
        paymentAmountInput.value = totalAmount;
        paymentAmountInput.setAttribute('readonly', true);
        calculateChange();
      }
    }

    // Set quick amount
    function setQuickAmount(amount) {
      const paymentInput = document.getElementById('paymentAmount');
      
      if (amount === 'exact') {
        paymentInput.value = totalAmount;
      } else {
        paymentInput.value = amount;
      }
      
      calculateChange();
    }

    // Calculate change
    function calculateChange() {
      const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
      const changeAmountDiv = document.getElementById('changeAmount');
      const changeValue = document.getElementById('changeValue');
      const btnCheckout = document.getElementById('btnCheckout');
      
      if (cart.length === 0 || paymentAmount === 0) {
        changeAmountDiv.style.display = 'none';
        btnCheckout.disabled = true;
        return;
      }
      
      const change = paymentAmount - totalAmount;
      changeAmountDiv.style.display = 'block';
      
      if (change < 0) {
        changeAmountDiv.classList.add('insufficient');
        changeValue.textContent = `Kurang Rp ${Math.abs(change).toLocaleString('id-ID')}`;
        btnCheckout.disabled = true;
      } else {
        changeAmountDiv.classList.remove('insufficient');
        changeValue.textContent = `Rp ${change.toLocaleString('id-ID')}`;
        btnCheckout.disabled = false;
      }
    }

    // Increase quantity
    function increaseQty(index) {
      if (cart[index].qty < cart[index].stock) {
        cart[index].qty++;
        updateCart();
      } else {
        alert('Stok tidak mencukupi!');
      }
    }

    // Decrease quantity
    function decreaseQty(index) {
      if (cart[index].qty > 1) {
        cart[index].qty--;
        updateCart();
      } else {
        removeItem(index);
      }
    }

    // Remove item
    function removeItem(index) {
      cart.splice(index, 1);
      updateCart();
    }

    // Clear cart
    function clearCart() {
      if (cart.length === 0) return;
      if (confirm('Yakin ingin mengosongkan keranjang?')) {
        cart = [];
        document.getElementById('paymentAmount').value = '';
        updateCart();
      }
    }

    // Checkout
    function checkout() {
      if (cart.length === 0) {
        alert('Keranjang masih kosong!');
        return;
      }

      const paymentMethod = document.getElementById('paymentMethod').value;
      const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
      
      if (paymentAmount < totalAmount) {
        alert('Jumlah bayar tidak mencukupi!');
        return;
      }
      
      const change = paymentAmount - totalAmount;
      
      // Disable button during processing
      const btnCheckout = document.getElementById('btnCheckout');
      btnCheckout.disabled = true;
      btnCheckout.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Memproses...';
      
      // Kirim data ke server
      fetch('proses_transaksi.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          cart: cart,
          payment_method: paymentMethod,
          payment_amount: paymentAmount,
          change_amount: change
        })
      })
      .then(response => response.text())
      .then(text => {
        try {
          const data = JSON.parse(text);
          
          if (data.success) {
            // Store transaction data
            currentTransactionId = data.id_transaksi;
            
            // Show success modal
            showSuccessModal(data, paymentMethod, paymentAmount, change);
            
            // Clear cart after showing modal
            cart = [];
            document.getElementById('paymentAmount').value = '';
            updateCart();
            loadProducts();
          } else {
            alert('Transaksi gagal: ' + data.message);
          }
        } catch (e) {
          console.error('JSON Parse Error:', e);
          alert('Error: Response bukan JSON valid.');
        }
      })
      .catch(error => {
        console.error('Fetch Error:', error);
        alert('Terjadi kesalahan jaringan: ' + error.message);
      })
      .finally(() => {
        // Re-enable button
        btnCheckout.disabled = false;
        btnCheckout.innerHTML = '<i class="bi bi-check-circle me-2"></i>Proses Pembayaran';
      });
    }

    // Show success modal
    function showSuccessModal(data, paymentMethod, paymentAmount, change) {
      // Set transaction details
      document.getElementById('modalTransactionId').textContent = '#' + data.id_transaksi;
      document.getElementById('modalDateTime').textContent = new Date().toLocaleString('id-ID');
      document.getElementById('modalCashier').textContent = data.nama_kasir;
      document.getElementById('modalPaymentMethod').textContent = paymentMethod;
      document.getElementById('modalTotal').textContent = 'Rp ' + data.total_harga.toLocaleString('id-ID');
      document.getElementById('modalPayment').textContent = 'Rp ' + paymentAmount.toLocaleString('id-ID');
      document.getElementById('modalChange').textContent = 'Rp ' + change.toLocaleString('id-ID');
      
      // Set items list
      let itemsHtml = '';
      cart.forEach(item => {
        itemsHtml += `
          <div class="item-row">
            <div>
              <div class="item-name">${item.name}</div>
              <div class="item-qty">${item.qty} x Rp ${item.price.toLocaleString('id-ID')}</div>
            </div>
            <div class="item-price">Rp ${(item.qty * item.price).toLocaleString('id-ID')}</div>
          </div>
        `;
      });
      document.getElementById('modalItemsList').innerHTML = itemsHtml;
      
      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('successModal'));
      modal.show();
    }

    // Print receipt
    function printReceipt() {
      if (currentTransactionId) {
        window.open(`../riwayat_transaksi/print_struk.php?id=${currentTransactionId}`, '_blank', 'width=300,height=600');
      }
    }

    // New transaction
    function newTransaction() {
      const modal = bootstrap.Modal.getInstance(document.getElementById('successModal'));
      modal.hide();
      currentTransactionId = null;
    }

    // Search product
    document.getElementById('searchProduct').addEventListener('input', function(e) {
      const search = e.target.value.toLowerCase();
      document.querySelectorAll('.product-card').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        card.style.display = name.includes(search) ? 'block' : 'none';
      });
    });

    // Filter by category
    document.querySelectorAll('.category-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const category = this.dataset.category;
        document.querySelectorAll('.product-card').forEach(card => {
          if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
  </script>
</body>
</html>