<?php
// Deteksi level direktori untuk path yang tepat
$is_subfolder = (strpos($_SERVER['PHP_SELF'], '/transaksi_baru/') !== false || 
                 strpos($_SERVER['PHP_SELF'], '/riwayat_transaksi/') !== false ||
                 strpos($_SERVER['PHP_SELF'], '/laporan_harian/') !== false ||
                 strpos($_SERVER['PHP_SELF'], '/laporan_bulanan/') !== false);

$path_level = $is_subfolder ? '../' : '';

// Deteksi halaman aktif
$current_folder = basename(dirname($_SERVER['PHP_SELF']));
$current_file = basename($_SERVER['PHP_SELF']);

// Fungsi untuk menentukan apakah menu aktif
function isActive($page) {
  global $current_file, $current_folder;
  
  if ($page === 'dashboard') {
    return ($current_file === 'index.php' && $current_folder === 'dashboard_kasir');
  } elseif ($page === 'transaksi_baru') {
    return ($current_folder === 'transaksi_baru');
  } elseif ($page === 'riwayat_transaksi') {
    return ($current_folder === 'riwayat_transaksi');
  } elseif ($page === 'laporan_harian') {
    return ($current_folder === 'laporan_harian');
  } elseif ($page === 'laporan_bulanan') {
    return ($current_folder === 'laporan_bulanan');
  }
  
  return false;
}
?>
<style>
  .sidebar {
    width: 250px;
    height: 100vh;
    background-color: #c7290f;
    color: #fff;
    position: fixed;
    top: 0;
    left: -250px;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 20px;
    transition: all 0.35s ease;
    z-index: 1100;
    overflow-y: auto;
  }
  .sidebar.active { left: 0; }

  .sidebar img {
    width: 200px;
    margin-bottom: 10px;
  }

  .sidebar a {
    color: #fff;
    text-decoration: none;
    padding: 12px 20px;
    width: 100%;
    display: flex;
    align-items: center;
    transition: background-color 0.3s;
    position: relative;
  }
  
  .sidebar a i {
    margin-right: 12px;
    font-size: 1.1rem;
  }
  
  .sidebar a:hover { background-color: #a61f0b; }
  
  .sidebar a.active { 
    background-color: #a61f0b;
    border-left: 4px solid #fff;
    font-weight: 600;
  }

  .menu-group { width: 100%; margin-top: 10px; }
  
  .menu-title {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: background-color 0.3s;
  }
  
  .menu-title:hover {
    background-color: #a61f0b;
  }
  
  .menu-title i:first-child {
    margin-right: 12px;
    font-size: 1.1rem;
  }
  
  .menu-title i.ms-auto {
    margin-left: auto;
    font-size: 0.9rem;
  }
  
  .submenu-container { 
    display: none; 
    flex-direction: column; 
    background-color: #b22209; 
  }
  
  .submenu-container.show { display: flex; }
  
  .submenu-item { 
    padding-left: 50px !important; 
    font-size: 0.95rem;
  }
  
  .submenu-item i {
    margin-right: 10px;
    font-size: 1rem;
  }
  
  .rotate { 
    transform: rotate(180deg); 
    transition: transform 0.3s ease; 
  }

  .main-content { 
    transition: transform 0.35s ease, filter 0.35s ease; 
  }
  
  .main-content.shifted { 
    transform: translateX(250px); 
    filter: brightness(0.6); 
  }

  .overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.35s ease;
    z-index: 1000;
  }
  
  .overlay.active {
    opacity: 1;
    pointer-events: auto;
  }

  @media (min-width: 992px) {
    .sidebar { left: 0; }
    .overlay { display: none; }
    .main-content.shifted { transform: none; filter: none; }
  }

  /* Scrollbar untuk sidebar */
  .sidebar::-webkit-scrollbar { width: 6px; }
  .sidebar::-webkit-scrollbar-track { background: #a61f0b; }
  .sidebar::-webkit-scrollbar-thumb { background: #fff; border-radius: 10px; }
</style>

<div class="sidebar" id="sidebar">
  <img src="<?= $path_level ?>../images/logo_1.png" alt="Logo LakuGroup">

  <a href="<?= $path_level ?>../dashboard_kasir/" class="<?= isActive('dashboard') ? 'active' : '' ?>">
    <i class="bi bi-house-door"></i> Dashboard
  </a>

  <div class="menu-group">
    <div class="menu-title" id="transaksiMenu">
      <i class="bi bi-cart"></i> Transaksi
      <i class="bi bi-chevron-down ms-auto" id="transaksiArrow"></i>
    </div>
    <div class="submenu-container" id="transaksiSubmenu">
      <a href="<?= $path_level ?>transaksi_baru/" class="submenu-item <?= isActive('transaksi_baru') ? 'active' : '' ?>">
        <i class="bi bi-plus-circle"></i> Transaksi Baru
      </a>
      <a href="<?= $path_level ?>riwayat_transaksi/" class="submenu-item <?= isActive('riwayat_transaksi') ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i> Riwayat Transaksi
      </a>
    </div>
  </div>

  <div class="menu-group">
    <div class="menu-title" id="laporanMenu">
      <i class="bi bi-bar-chart"></i> Laporan
      <i class="bi bi-chevron-down ms-auto" id="laporanArrow"></i>
    </div>
    <div class="submenu-container" id="laporanSubmenu">
      <a href="<?= $path_level ?>laporan_harian/" class="submenu-item <?= isActive('laporan_harian') ? 'active' : '' ?>">
        <i class="bi bi-calendar-day"></i> Laporan Harian
      </a>
      <a href="<?= $path_level ?>laporan_bulanan/" class="submenu-item <?= isActive('laporan_bulanan') ? 'active' : '' ?>">
        <i class="bi bi-calendar-month"></i> Laporan Bulanan
      </a>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const mainContent = document.getElementById('main-content');

    // Toggle sidebar on burger menu click
    if (menuToggle) {
      menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        mainContent.classList.toggle('shifted');
      });
    }

    // Close sidebar when clicking overlay
    if (overlay) {
      overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        mainContent.classList.remove('shifted');
      });
    }

    // Transaksi submenu toggle
    const transaksiMenu = document.getElementById('transaksiMenu');
    const transaksiSubmenu = document.getElementById('transaksiSubmenu');
    const transaksiArrow = document.getElementById('transaksiArrow');
    
    if (transaksiMenu) {
      transaksiMenu.addEventListener('click', function() {
        transaksiSubmenu.classList.toggle('show');
        transaksiArrow.classList.toggle('rotate');
      });
    }

    // Laporan submenu toggle
    const laporanMenu = document.getElementById('laporanMenu');
    const laporanSubmenu = document.getElementById('laporanSubmenu');
    const laporanArrow = document.getElementById('laporanArrow');
    
    if (laporanMenu) {
      laporanMenu.addEventListener('click', function() {
        laporanSubmenu.classList.toggle('show');
        laporanArrow.classList.toggle('rotate');
      });
    }

    // Auto-expand submenu based on current page
    const currentPath = window.location.pathname;
    
    // Auto expand Transaksi menu jika ada submenu yang aktif
    if (currentPath.includes('transaksi_baru') || currentPath.includes('riwayat_transaksi')) {
      transaksiSubmenu.classList.add('show');
      transaksiArrow.classList.add('rotate');
    }
    
    // Auto expand Laporan menu jika ada submenu yang aktif
    if (currentPath.includes('laporan_harian') || currentPath.includes('laporan_bulanan')) {
      laporanSubmenu.classList.add('show');
      laporanArrow.classList.add('rotate');
    }
  });
</script>
