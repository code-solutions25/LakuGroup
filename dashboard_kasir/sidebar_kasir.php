<!-- Sidebar Kasir -->
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
  }
  .sidebar a:hover, .sidebar a.active { background-color: #a61f0b; }

  .menu-group { width: 100%; margin-top: 10px; }
  .menu-title {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    cursor: pointer;
  }
  .submenu-container { display: none; flex-direction: column; background-color: #b22209; }
  .submenu-container.show { display: flex; }
  .submenu-item { padding-left: 40px !important; }
  .rotate { transform: rotate(180deg); transition: transform 0.3s ease; }

  .main-content { transition: transform 0.35s ease, filter 0.35s ease; }
  .main-content.shifted { transform: translateX(250px); filter: brightness(0.6); }

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
</style>

<div class="sidebar" id="sidebar">
  <img src="../images/logo_1.png" alt="Logo LakuGroup">

  <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
    <i class="bi bi-house-door"></i> Dashboard
  </a>

  <div class="menu-group">
    <div class="menu-title" id="transaksiMenu">
      <i class="bi bi-cart"></i> Transaksi
      <i class="bi bi-chevron-down ms-auto" id="transaksiArrow"></i>
    </div>
    <div class="submenu-container" id="transaksiSubmenu">
      <a href="transaksi_baru.php" class="submenu-item <?= $current_page == 'transaksi_baru.php' ? 'active' : '' ?>">
        <i class="bi bi-plus-circle"></i> Transaksi Baru
      </a>
      <a href="riwayat_transaksi.php" class="submenu-item <?= $current_page == 'riwayat_transaksi.php' ? 'active' : '' ?>">
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
      <a href="laporan_harian.php" class="submenu-item <?= $current_page == 'laporan_harian.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar-day"></i> Laporan Harian
      </a>
      <a href="laporan_bulanan.php" class="submenu-item <?= $current_page == 'laporan_bulanan.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar-month"></i> Laporan Bulanan
      </a>
    </div>
  </div>
</div>

<script>
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const mainContent = document.getElementById('main-content');

  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
      mainContent.classList.toggle('shifted');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('active');
      overlay.classList.remove('active');
      mainContent.classList.remove('shifted');
    });
  }

  const transaksiMenu = document.getElementById('transaksiMenu');
  const transaksiSubmenu = document.getElementById('transaksiSubmenu');
  const transaksiArrow = document.getElementById('transaksiArrow');
  transaksiMenu.addEventListener('click', () => {
    transaksiSubmenu.classList.toggle('show');
    transaksiArrow.classList.toggle('rotate');
  });

  const laporanMenu = document.getElementById('laporanMenu');
  const laporanSubmenu = document.getElementById('laporanSubmenu');
  const laporanArrow = document.getElementById('laporanArrow');
  laporanMenu.addEventListener('click', () => {
    laporanSubmenu.classList.toggle('show');
    laporanArrow.classList.toggle('rotate');
  });

  const currentPage = "<?= $current_page ?>";
  if (['transaksi_baru.php','riwayat_transaksi.php'].includes(currentPage)) {
    transaksiSubmenu.classList.add('show');
    transaksiArrow.classList.add('rotate');
  }
  if (['laporan_harian.php','laporan_bulanan.php'].includes(currentPage)) {
    laporanSubmenu.classList.add('show');
    laporanArrow.classList.add('rotate');
  }
</script>
