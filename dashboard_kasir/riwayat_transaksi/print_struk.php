<?php
session_start();
include "../../database.php";

// Pastikan hanya petugas yang bisa mengakses
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Petugas') {
  header("Location: ../../");
  exit();
}

$id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_outlet = $_SESSION['id_outlet'];

// Ambil data transaksi
$query = "SELECT t.*, u.nama_lengkap, o.nama_outlet, o.alamat 
          FROM transaksi t 
          LEFT JOIN user u ON t.id_user = u.id_user 
          LEFT JOIN outlet o ON t.id_outlet = o.id_outlet
          WHERE t.id_transaksi = ? AND t.id_outlet = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $id_transaksi, $id_outlet);
$stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();

if (!$transaksi) {
  die("Transaksi tidak ditemukan");
}

// Ambil detail transaksi
$query_detail = "SELECT dt.* 
                 FROM detail_transaksi dt 
                 WHERE dt.id_transaksi = ?";
$stmt_detail = $conn->prepare($query_detail);
$stmt_detail->bind_param("i", $id_transaksi);
$stmt_detail->execute();
$items = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);

// Hitung total item
$total_items = 0;
foreach($items as $item) {
  $total_items += $item['jumlah'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title></title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    @page {
      size: A4;
      margin: 0;
    }
    
    html, body {
      width: 100%;
      height: 100vh;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Courier New', Courier, monospace;
      background: #fff;
      color: #000;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px;
    }
    
    .struk-container {
      width: 100%;
      max-width: 700px;
      height: 100%;
      padding: 40px 60px;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    /* Header */
    .header {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .logo {
      font-size: 48px;
      font-weight: bold;
      letter-spacing: 10px;
      margin-bottom: 8px;
    }
    
    .outlet-name {
      font-size: 32px;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .outlet-info {
      font-size: 20px;
      margin: 3px 0;
    }
    
    /* Divider */
    .divider {
      border-top: 2px dashed #000;
      margin: 15px 0;
    }
    
    /* Transaction Info */
    .transaction-info {
      font-size: 22px;
      margin-bottom: 15px;
      text-align: center;
    }
    
    .info-row {
      margin: 5px 0;
      font-weight: 600;
    }
    
    /* Status */
    .items-header {
      text-align: center;
      margin: 20px 0;
      font-weight: bold;
      font-size: 42px;
      letter-spacing: 12px;
      padding: 20px;
      background: #000;
      color: #fff;
    }
    
    /* Items */
    .items-section {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .item {
      margin: 12px 0;
      font-size: 24px;
      padding: 12px 0;
      border-bottom: 2px solid #eee;
    }
    
    .item:last-child {
      border-bottom: none;
    }
    
    .item-name {
      font-weight: bold;
      margin-bottom: 8px;
      text-align: center;
    }
    
    .item-detail {
      display: flex;
      justify-content: center;
      gap: 40px;
      margin-top: 8px;
      font-size: 22px;
    }
    
    .item-detail span {
      min-width: 80px;
      text-align: center;
    }
    
    /* Totals */
    .totals {
      margin-top: 20px;
      padding-top: 15px;
      border-top: 3px solid #000;
      font-size: 24px;
    }
    
    .total-row {
      margin: 10px 0;
      display: flex;
      justify-content: space-between;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .total-row.grand {
      font-weight: bold;
      font-size: 32px;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 2px dashed #000;
    }
    
    .total-row.payment {
      margin-top: 15px;
      padding-top: 15px;
      border-top: 2px dashed #000;
    }
    
    /* Footer */
    .footer {
      text-align: center;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 2px dashed #000;
      font-size: 22px;
    }
    
    .footer div {
      margin: 8px 0;
      font-weight: bold;
    }
    
    .queue-number {
      text-align: center;
      margin-top: 20px;
      font-size: 32px;
      font-weight: bold;
      padding: 25px;
      border: 3px solid #000;
      background: #f8f8f8;
    }
    
    /* Print specific */
    @media print {
      @page {
        size: A4;
        margin: 0;
      }
      
      html, body {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
      }
      
      body {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
      }
      
      .struk-container {
        padding: 30px 50px;
        max-width: 700px;
      }
    }
  </style>
</head>
<body>
  <div class="struk-container">
    <div>
      <!-- Header -->
      <div class="header">
        <div class="logo">TJAP LAKU</div>
        <div class="outlet-name"><?= htmlspecialchars($transaksi['nama_outlet']); ?></div>
        <div class="outlet-info"><?= htmlspecialchars($transaksi['alamat']); ?></div>
        <div class="outlet-info">Pass WiFi: tjaplaku3</div>
      </div>
      
      <div class="divider"></div>
      
      <!-- Transaction Info -->
      <div class="transaction-info">
        <div class="info-row">Struk: <?= $transaksi['id_transaksi']; ?></div>
        <div class="info-row"><?= date('d/m/Y H:i', strtotime($transaksi['tanggal'])); ?></div>
      </div>
      
      <!-- Items Header -->
      <div class="items-header">LUNAS</div>
      
      <div class="divider"></div>
    </div>
    
    <!-- Items -->
    <div class="items-section">
      <?php foreach($items as $item): ?>
        <div class="item">
          <div class="item-name"><?= htmlspecialchars($item['nama_produk']); ?></div>
          <div class="item-detail">
            <span>Qty: <?= $item['jumlah']; ?></span>
            <span>@ Rp <?= number_format($item['harga_satuan'], 0, ',', '.'); ?></span>
            <span>Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <div>
      <div class="divider"></div>
      
      <!-- Totals -->
      <div class="totals">
        <div class="total-row">
          <span>SubTotal</span>
          <span>Rp <?= number_format($transaksi['total_harga'], 0, ',', '.'); ?></span>
        </div>
        <div class="total-row grand">
          <span>TOTAL (<?= $total_items; ?> item)</span>
          <span>Rp <?= number_format($transaksi['total_harga'], 0, ',', '.'); ?></span>
        </div>
        <div class="total-row payment">
          <span>Bayar</span>
          <span>Rp <?= number_format($transaksi['jumlah_bayar'], 0, ',', '.'); ?></span>
        </div>
        <div class="total-row">
          <span>Kembali</span>
          <span>Rp <?= number_format($transaksi['kembalian'], 0, ',', '.'); ?></span>
        </div>
      </div>
      
      <div class="divider"></div>
      
      <!-- Footer -->
      <div class="footer">
        <div>Makan Bakmie Biar Happy</div>
        <div style="font-weight: normal; font-size: 18px; margin-top: 6px;">Terima kasih atas kunjungan Anda</div>
      </div>
      
      <!-- Queue Number -->
      <div class="queue-number">
        No. Antrian: <?= str_pad($transaksi['id_transaksi'] % 100, 2, '0', STR_PAD_LEFT); ?>
      </div>
    </div>
  </div>

  <script>
    window.onload = function() {
      document.title = '';
      setTimeout(function() {
        window.print();
      }, 250);
      window.onafterprint = function() {
        window.close();
      };
    }
  </script>
</body>
</html>