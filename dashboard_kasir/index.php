<?php
session_start();
include "../database.php";

// Pastikan hanya petugas yang bisa mengakses
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Petugas') {
  header("Location: ../");
  exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$id_outlet = $_SESSION['id_outlet'];

// Ambil waktu dari database
$query_time = "SELECT 
                DATE_FORMAT(NOW(), '%H') as current_hour,
                DATE(NOW()) as today,
                DATE_FORMAT(NOW(), '%Y-%m') as current_month,
                NOW() as current_datetime";
$result_time = $conn->query($query_time);
$time_data = $result_time->fetch_assoc();

$current_hour = $time_data['current_hour'];
$today = $time_data['today'];
$current_month = $time_data['current_month'];
$current_datetime = $time_data['current_datetime'];

// Jumlah transaksi hari ini (status Selesai saja)
// Transaksi dihitung dari jam 00:00:00 sampai 23:59:59 hari ini
$query_transaksi = "SELECT COUNT(*) as total 
                    FROM transaksi 
                    WHERE id_outlet = ? 
                    AND DATE(tanggal) = ? 
                    AND status = 'Selesai'";
$stmt = $conn->prepare($query_transaksi);
$stmt->bind_param("is", $id_outlet, $today);
$stmt->execute();
$result_transaksi = $stmt->get_result()->fetch_assoc();
$jumlah_transaksi = $result_transaksi['total'];

// Pendapatan hari ini (status Selesai saja)
$query_pendapatan = "SELECT COALESCE(SUM(total_harga), 0) as pendapatan 
                     FROM transaksi 
                     WHERE id_outlet = ? 
                     AND DATE(tanggal) = ? 
                     AND status = 'Selesai'";
$stmt = $conn->prepare($query_pendapatan);
$stmt->bind_param("is", $id_outlet, $today);
$stmt->execute();
$result_pendapatan = $stmt->get_result()->fetch_assoc();
$pendapatan = $result_pendapatan['pendapatan'];

// Pendapatan bulan ini (status Selesai saja)
$query_pendapatan_bulan = "SELECT COALESCE(SUM(total_harga), 0) as pendapatan 
                           FROM transaksi 
                           WHERE id_outlet = ? 
                           AND DATE_FORMAT(tanggal, '%Y-%m') = ? 
                           AND status = 'Selesai'";
$stmt = $conn->prepare($query_pendapatan_bulan);
$stmt->bind_param("is", $id_outlet, $current_month);
$stmt->execute();
$result_pendapatan_bulan = $stmt->get_result()->fetch_assoc();
$pendapatan_bulan = $result_pendapatan_bulan['pendapatan'];

// Ambil target dari database
$query_target = "SELECT target_harian, target_bulanan 
                 FROM target 
                 WHERE id_outlet = ? 
                 AND DATE_FORMAT(bulan, '%Y-%m') = ? 
                 LIMIT 1";
$stmt = $conn->prepare($query_target);
$stmt->bind_param("is", $id_outlet, $current_month);
$stmt->execute();
$result_target = $stmt->get_result()->fetch_assoc();

$target_harian = $result_target['target_harian'] ?? 0;
$target_bulanan = $result_target['target_bulanan'] ?? 0;

// Hitung persentase target
$persentase_target_harian = ($target_harian > 0) ? ($pendapatan / $target_harian) * 100 : 0;
$persentase_target_harian = min($persentase_target_harian, 100);

$persentase_target_bulanan = ($target_bulanan > 0) ? ($pendapatan_bulan / $target_bulanan) * 100 : 0;
$persentase_target_bulanan = min($persentase_target_bulanan, 100);

// Status info jika baru berganti hari (jam 00:00 - 00:59)
$is_new_day = ($current_hour == '00');
$new_day_message = $is_new_day ? " (Hari Baru)" : "";

// Format tanggal untuk ditampilkan
$display_date = date('d M Y', strtotime($current_datetime));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Kasir | LakuGroup</title>
  <link rel="icon" type="image/png" href="../images/logo_2.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      margin: 0;
      overflow-x: hidden;
      min-height: 100vh;
    }

    .main-content {
      margin-left: 250px;
      transition: all 0.3s ease;
    }

    .header {
      background: linear-gradient(135deg, #c7290f 0%, #a61f0b 100%);
      color: #fff;
      padding: 20px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 999;
      box-shadow: 0 4px 20px rgba(199, 41, 15, 0.3);
    }

    .username { 
      font-weight: 600;
      font-size: 1.1rem;
    }

    .logout-btn {
      background-color: #fff;
      color: #c7290f;
      border: none;
      padding: 10px 20px;
      border-radius: 10px;
      transition: all 0.3s;
      text-decoration: none;
      font-weight: 600;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .logout-btn:hover { 
      background-color: #f8f9fa;
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }

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

    .menu-toggle:hover {
      background-color: #f8f9fa;
    }

    @media (max-width: 991px) {
      .main-content {
        margin-left: 0;
      }
      .menu-toggle { 
        display: inline-block; 
      }
    }

    /* Welcome Banner */
    .welcome-banner {
      background: linear-gradient(135deg, #c7290f 0%, #a61f0b 100%);
      border-radius: 20px;
      padding: 40px;
      margin-bottom: 30px;
      color: #fff;
      box-shadow: 0 10px 30px rgba(199, 41, 15, 0.3);
      position: relative;
      overflow: hidden;
    }

    .welcome-banner::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      pointer-events: none;
    }

    .welcome-banner h2 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 10px;
      position: relative;
    }

    .welcome-banner p {
      font-size: 1.1rem;
      opacity: 0.9;
      position: relative;
    }

    .welcome-banner .date-info {
      position: absolute;
      right: 40px;
      top: 50%;
      transform: translateY(-50%);
      text-align: right;
    }

    .welcome-banner .date-info .date {
      font-size: 1.5rem;
      font-weight: 700;
    }

    .welcome-banner .date-info .time {
      font-size: 1rem;
      opacity: 0.9;
    }

    .new-day-badge {
      display: inline-block;
      background: rgba(255, 255, 255, 0.2);
      padding: 4px 12px;
      border-radius: 15px;
      font-size: 0.85rem;
      margin-left: 10px;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.6; }
    }

    /* Stat Cards */
    .stat-card {
      border: none;
      border-radius: 20px;
      background: #fff;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      overflow: hidden;
      position: relative;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #c7290f, #a61f0b);
    }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .stat-card .icon-box {
      width: 70px;
      height: 70px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      transition: all 0.3s;
    }

    .stat-card:hover .icon-box {
      transform: scale(1.1) rotate(5deg);
    }

    .stat-card.primary .icon-box {
      background: linear-gradient(135deg, rgba(199, 41, 15, 0.15) 0%, rgba(199, 41, 15, 0.05) 100%);
      color: #c7290f;
    }

    .stat-card.success .icon-box {
      background: linear-gradient(135deg, rgba(25, 135, 84, 0.15) 0%, rgba(25, 135, 84, 0.05) 100%);
      color: #198754;
    }

    .stat-card.warning .icon-box {
      background: linear-gradient(135deg, rgba(255, 193, 7, 0.15) 0%, rgba(255, 193, 7, 0.05) 100%);
      color: #ffc107;
    }

    .stat-card.info .icon-box {
      background: linear-gradient(135deg, rgba(13, 110, 253, 0.15) 0%, rgba(13, 110, 253, 0.05) 100%);
      color: #0d6efd;
    }

    .stat-card.purple .icon-box {
      background: linear-gradient(135deg, rgba(111, 66, 193, 0.15) 0%, rgba(111, 66, 193, 0.05) 100%);
      color: #6f42c1;
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #212529;
      margin: 15px 0 8px 0;
    }

    .stat-label {
      color: #6c757d;
      font-size: 0.95rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-sublabel {
      color: #adb5bd;
      font-size: 0.85rem;
      margin-top: 5px;
    }

    /* Progress Bar */
    .progress-custom {
      height: 12px;
      border-radius: 10px;
      background-color: #e9ecef;
      overflow: hidden;
      margin-top: 12px;
    }

    .progress-bar-custom {
      background: linear-gradient(90deg, #c7290f 0%, #ff4d2d 100%);
      border-radius: 10px;
      transition: width 1s ease;
      box-shadow: 0 2px 8px rgba(199, 41, 15, 0.3);
    }

    .progress-bar-custom.success {
      background: linear-gradient(90deg, #198754 0%, #20c997 100%);
      box-shadow: 0 2px 8px rgba(25, 135, 84, 0.3);
    }

    /* Menu Cards */
    .menu-card {
      border: none;
      border-radius: 20px;
      transition: all 0.3s ease;
      background: #fff;
      cursor: pointer;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      position: relative;
    }

    .menu-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(199, 41, 15, 0.05) 0%, transparent 100%);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .menu-card:hover::before {
      opacity: 1;
    }

    .menu-card:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .menu-card i { 
      color: #c7290f;
      transition: all 0.3s;
    }

    .menu-card:hover i {
      transform: scale(1.2);
    }

    .menu-card h6 {
      color: #212529;
      font-weight: 700;
    }

    .menu-card p {
      color: #6c757d;
    }

    .section-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #212529;
      margin-bottom: 25px;
      position: relative;
      padding-left: 20px;
    }

    .section-title::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 5px;
      height: 30px;
      background: linear-gradient(180deg, #c7290f 0%, #a61f0b 100%);
      border-radius: 3px;
    }

    .content-wrapper {
      background: #fff;
      border-radius: 25px;
      padding: 40px;
      box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
    }

    /* Target Info */
    .target-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 8px;
    }

    .target-badge {
      background: linear-gradient(135deg, #c7290f 0%, #a61f0b 100%);
      color: #fff;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .no-target {
      color: #dc3545;
      font-size: 0.85rem;
      font-style: italic;
      margin-top: 8px;
    }

    /* Info Card */
    .info-card {
      background: linear-gradient(135deg, rgba(199, 41, 15, 0.05) 0%, rgba(199, 41, 15, 0.02) 100%);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
      border-left: 4px solid #c7290f;
    }

    .info-card h6 {
      color: #c7290f;
      font-weight: 700;
      margin-bottom: 15px;
    }

    .info-item {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px dashed #e9ecef;
    }

    .info-item:last-child {
      border-bottom: none;
    }

    .info-item .label {
      color: #6c757d;
      font-weight: 500;
    }

    .info-item .value {
      color: #212529;
      font-weight: 700;
    }

    @media (max-width: 768px) {
      .welcome-banner .date-info {
        position: relative;
        right: 0;
        transform: none;
        margin-top: 20px;
      }

      .stat-value {
        font-size: 1.5rem;
      }

      .content-wrapper {
        padding: 20px;
      }
    }
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
        </div>
      </div>
      <a href="../logout.php" class="btn logout-btn">
        <i class="bi bi-box-arrow-right me-2"></i>Logout
      </a>
    </div>

    <!-- Konten utama -->
    <div class="container-fluid mt-4 px-4">
      <!-- Welcome Banner -->
      <div class="welcome-banner">
        <h2>
          <i class="bi bi-speedometer2 me-3"></i>Dashboard Kasir
          <?php if ($is_new_day): ?>
            <span class="new-day-badge">
              <i class="bi bi-sunrise me-1"></i>Hari Baru Dimulai!
            </span>
          <?php endif; ?>
        </h2>
        <p>Pantau performa penjualan outlet Anda secara real-time</p>
        <div class="date-info">
          <div class="date"><?= $display_date ?></div>
          <div class="time" id="current-time"></div>
        </div>
      </div>

      <div class="content-wrapper">
        <!-- Statistik Hari Ini -->
        <h5 class="section-title">Statistik Hari Ini<?= $new_day_message ?></h5>
        <div class="row mb-5">
          <!-- Jumlah Transaksi -->
          <div class="col-lg-6 col-md-6 mb-4">
            <div class="stat-card primary p-4">
              <div class="d-flex align-items-center">
                <div class="icon-box">
                  <i class="bi bi-cart-check-fill"></i>
                </div>
                <div class="ms-3 flex-grow-1">
                  <div class="stat-label">Transaksi Hari Ini</div>
                  <div class="stat-value"><?= number_format($jumlah_transaksi) ?></div>
                  <div class="stat-sublabel">
                    Total transaksi selesai
                    <?php if ($is_new_day): ?>
                      <span style="color: #c7290f; font-weight: 600;"> - Mulai dari 00:00</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pendapatan Hari Ini -->
          <div class="col-lg-6 col-md-6 mb-4">
            <div class="stat-card success p-4">
              <div class="d-flex align-items-center">
                <div class="icon-box">
                  <i class="bi bi-cash-stack"></i>
                </div>
                <div class="ms-3 flex-grow-1">
                  <div class="stat-label">Pendapatan Hari Ini</div>
                  <div class="stat-value" style="font-size: 1.6rem;">Rp <?= number_format($pendapatan, 0, ',', '.') ?></div>
                  <div class="stat-sublabel">
                    Total penjualan hari ini
                    <?php if ($is_new_day): ?>
                      <span style="color: #198754; font-weight: 600;"> - Fresh Start!</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Target -->
        <h5 class="section-title">Pencapaian Target</h5>
        <div class="row mb-5">
          <!-- Target Harian -->
          <div class="col-lg-6 col-md-6 mb-4">
            <div class="stat-card warning p-4">
              <div class="d-flex align-items-center">
                <div class="icon-box">
                  <i class="bi bi-trophy-fill"></i>
                </div>
                <div class="ms-3 flex-grow-1">
                  <div class="stat-label">Target Harian</div>
                  <?php if ($target_harian > 0): ?>
                    <div class="stat-value"><?= number_format($persentase_target_harian, 1) ?>%</div>
                    <div class="progress-custom">
                      <div class="progress-bar-custom" style="width: <?= $persentase_target_harian ?>%"></div>
                    </div>
                    <div class="target-info">
                      <small class="stat-sublabel">Rp <?= number_format($pendapatan, 0, ',', '.') ?></small>
                      <span class="target-badge">Target: Rp <?= number_format($target_harian, 0, ',', '.') ?></span>
                    </div>
                  <?php else: ?>
                    <div class="stat-value">-</div>
                    <div class="no-target"><i class="bi bi-exclamation-triangle me-1"></i>Target belum diatur</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Target Bulanan -->
          <div class="col-lg-6 col-md-6 mb-4">
            <div class="stat-card info p-4">
              <div class="d-flex align-items-center">
                <div class="icon-box">
                  <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="ms-3 flex-grow-1">
                  <div class="stat-label">Target Bulanan</div>
                  <?php if ($target_bulanan > 0): ?>
                    <div class="stat-value"><?= number_format($persentase_target_bulanan, 1) ?>%</div>
                    <div class="progress-custom">
                      <div class="progress-bar-custom success" style="width: <?= $persentase_target_bulanan ?>%"></div>
                    </div>
                    <div class="target-info">
                      <small class="stat-sublabel">Rp <?= number_format($pendapatan_bulan, 0, ',', '.') ?></small>
                      <span class="target-badge">Target: Rp <?= number_format($target_bulanan, 0, ',', '.') ?></span>
                    </div>
                  <?php else: ?>
                    <div class="stat-value">-</div>
                    <div class="no-target"><i class="bi bi-exclamation-triangle me-1"></i>Target belum diatur</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Menu Cepat -->
        <h5 class="section-title">Menu Cepat</h5>
        <div class="row">
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="menu-card" onclick="window.location='transaksi_baru/'">
              <div class="card-body text-center py-5">
                <i class="bi bi-cart-plus fs-1 mb-3"></i>
                <h6 class="mt-3 fw-bold">Transaksi Baru</h6>
                <p class="text-muted mb-0">Mulai transaksi penjualan baru</p>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="menu-card" onclick="window.location='riwayat_transaksi/'">
              <div class="card-body text-center py-5">
                <i class="bi bi-clock-history fs-1 mb-3"></i>
                <h6 class="mt-3 fw-bold">Riwayat Transaksi</h6>
                <p class="text-muted mb-0">Lihat daftar transaksi sebelumnya</p>
              </div>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="menu-card" onclick="window.location='laporan_harian/'">
              <div class="card-body text-center py-5">
                <i class="bi bi-bar-chart-line fs-1 mb-3"></i>
                <h6 class="mt-3 fw-bold">Laporan Penjualan</h6>
                <p class="text-muted mb-0">Lihat performa outlet Anda</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Update time dari database
    function updateTime() {
      fetch('get_server_time.php')
        .then(response => response.json())
        .then(data => {
          document.getElementById('current-time').textContent = data.time;
        })
        .catch(error => {
          // Fallback ke waktu lokal jika fetch gagal
          const now = new Date();
          const hours = String(now.getHours()).padStart(2, '0');
          const minutes = String(now.getMinutes()).padStart(2, '0');
          const seconds = String(now.getSeconds()).padStart(2, '0');
          document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
        });
    }
    
    setInterval(updateTime, 1000);
    updateTime();
  </script>
</body>
</html>
