<?php
session_start();
include "../database.php";

// Pastikan hanya petugas yang bisa mengakses
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Petugas') {
  header("Location: ../");
  exit();
}

$id_outlet = $_SESSION['id_outlet'];
$current_page = basename($_SERVER['PHP_SELF']);

// Statistik transaksi hari ini per outlet
$query = $conn->prepare("
  SELECT 
    COUNT(id_transaksi) AS total_transaksi,
    COALESCE(SUM(total_harga), 0) AS total_pendapatan,
    COALESCE(SUM(total_item), 0) AS total_item
  FROM transaksi
  WHERE id_outlet = ? AND DATE(tanggal_transaksi) = CURDATE()
");
$query->bind_param("i", $id_outlet);
$query->execute();
$stat = $query->get_result()->fetch_assoc();

$total_transaksi = $stat['total_transaksi'] ?? 0;
$total_pendapatan = $stat['total_pendapatan'] ?? 0;
$total_item = $stat['total_item'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Kasir | LakuGroup</title>
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
    }

    @media (max-width: 991px) {
      .main-content {
        margin-left: 0;
        transition: transform 0.3s ease;
      }
      .menu-toggle { display: inline-block; }
    }

    .card {
      border: none;
      border-radius: 14px;
      transition: transform 0.25s ease, box-shadow 0.35s ease;
      background: #fff;
    }

    .card:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .card i { color: #c7290f; }
  </style>
</head>
<body>
  <?php include "sidebar_kasir.php"; ?>

  <div class="overlay" id="overlay"></div>

  <div class="main-content" id="main-content">
    <!-- Header -->
    <div class="header">
      <div class="d-flex align-items-center">
        <button class="menu-toggle" id="menu-toggle"><i class="bi bi-list"></i></button>
        <div class="username">
          <i class="bi bi-person-circle me-2"></i> 
          Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']); ?></strong> 
          (<?= htmlspecialchars($_SESSION['role']); ?> - <?= htmlspecialchars($_SESSION['outlet']); ?>)
        </div>
      </div>
      <a href="../logout.php" class="btn logout-btn">Logout</a>
    </div>

    <!-- Konten utama -->
    <div class="container-fluid mt-4">
      <div class="p-4 bg-white rounded shadow-sm">
        <h4 class="fw-bold mb-3">Dashboard Kasir</h4>
        <p>Halo, <?= htmlspecialchars($_SESSION['nama']); ?>! Berikut ringkasan transaksi outlet hari ini.</p>
        <hr>

        <!-- Statistik Kasir -->
        <div class="row mb-4">
          <div class="col-md-4 mb-3">
            <div class="card text-center py-4">
              <i class="bi bi-receipt fs-1"></i>
              <h5 class="mt-2 fw-bold"><?= number_format($total_transaksi); ?></h5>
              <p>Total Transaksi Hari Ini</p>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card text-center py-4">
              <i class="bi bi-box-seam fs-1"></i>
              <h5 class="mt-2 fw-bold"><?= number_format($total_item); ?></h5>
              <p>Total Menu Terjual</p>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card text-center py-4">
              <i class="bi bi-cash-stack fs-1"></i>
              <h5 class="mt-2 fw-bold">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></h5>
              <p>Total Pendapatan Hari Ini</p>
            </div>
          </div>
        </div>

        <!-- Menu Cepat -->
        <h5 class="fw-bold mt-4 mb-3">Menu Cepat</h5>
        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="card" onclick="window.location='transaksi_baru.php'">
              <div class="card-body text-center">
                <i class="bi bi-cart-plus fs-1 text-danger"></i>
                <h6>Transaksi Baru</h6>
                <p>Mulai transaksi penjualan baru.</p>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card" onclick="window.location='riwayat_transaksi.php'">
              <div class="card-body text-center">
                <i class="bi bi-clock-history fs-1 text-danger"></i>
                <h6>Riwayat Transaksi</h6>
                <p>Lihat daftar transaksi sebelumnya.</p>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card" onclick="window.location='laporan_harian.php'">
              <div class="card-body text-center">
                <i class="bi bi-bar-chart-line fs-1 text-danger"></i>
                <h6>Laporan Penjualan</h6>
                <p>Lihat performa outlet Anda.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
