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

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Filter settings
$tanggal_dari = isset($_GET['tanggal_dari']) ? $_GET['tanggal_dari'] : '';
$tanggal_sampai = isset($_GET['tanggal_sampai']) ? $_GET['tanggal_sampai'] : '';
$metode_pembayaran = isset($_GET['metode_pembayaran']) ? $_GET['metode_pembayaran'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$where_conditions = ["t.id_outlet = ?"];
$params = [$id_outlet];
$types = "i";

if (!empty($tanggal_dari)) {
  $where_conditions[] = "DATE(t.tanggal) >= ?";
  $params[] = $tanggal_dari;
  $types .= "s";
}

if (!empty($tanggal_sampai)) {
  $where_conditions[] = "DATE(t.tanggal) <= ?";
  $params[] = $tanggal_sampai;
  $types .= "s";
}

if (!empty($metode_pembayaran)) {
  $where_conditions[] = "t.metode_pembayaran = ?";
  $params[] = $metode_pembayaran;
  $types .= "s";
}

if (!empty($search)) {
  $where_conditions[] = "(t.id_transaksi LIKE ? OR t.kode_transaksi LIKE ? OR u.nama_lengkap LIKE ?)";
  $search_param = "%$search%";
  $params[] = $search_param;
  $params[] = $search_param;
  $params[] = $search_param;
  $types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total records
$count_query = "SELECT COUNT(*) as total 
                FROM transaksi t 
                LEFT JOIN user u ON t.id_user = u.id_user 
                WHERE $where_clause";
$stmt_count = $conn->prepare($count_query);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get transactions with pagination
$query = "SELECT t.*, u.nama_lengkap 
          FROM transaksi t 
          LEFT JOIN user u ON t.id_user = u.id_user 
          WHERE $where_clause 
          ORDER BY t.tanggal DESC 
          LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Riwayat Transaksi | LakuGroup</title>
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

    /* Filter Card dengan tema merah */
    .filter-card {
      background: linear-gradient(135deg, #c7290f 0%, #a61f0b 100%);
      border-radius: 14px;
      padding: 25px;
      box-shadow: 0 5px 20px rgba(199, 41, 15, 0.3);
      margin-bottom: 20px;
      position: relative;
      overflow: hidden;
    }

    .filter-card::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      pointer-events: none;
    }

    .filter-card h5 {
      color: #fff;
      margin-bottom: 20px;
      font-weight: 700;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .filter-card h5 i {
      color: #fff;
      opacity: 0.9;
    }

    .filter-row {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      align-items: end;
      position: relative;
      z-index: 1;
    }

    .filter-group {
      flex: 1;
      min-width: 200px;
    }

    .filter-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      color: #fff;
      text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .filter-group label i {
      color: #fff;
      opacity: 0.9;
    }

    .filter-group input,
    .filter-group select {
      width: 100%;
      padding: 10px 14px;
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 8px;
      font-size: 0.9rem;
      background: rgba(255,255,255,0.95);
      color: #333;
      transition: all 0.3s;
    }

    .filter-group input:focus,
    .filter-group select:focus {
      outline: none;
      border-color: #fff;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(255,255,255,0.2);
    }

    .filter-group input::placeholder {
      color: #999;
    }

    .btn-filter {
      padding: 10px 24px;
      background: #fff;
      color: #c7290f;
      border: 2px solid #fff;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .btn-filter:hover {
      background: transparent;
      color: #fff;
      border-color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }

    .btn-reset {
      padding: 10px 24px;
      background: rgba(255,255,255,0.2);
      color: #fff;
      border: 2px solid rgba(255,255,255,0.5);
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
    }

    .btn-reset:hover {
      background: rgba(255,255,255,0.3);
      border-color: #fff;
      color: #fff;
      transform: translateY(-2px);
    }

    .table-card {
      background: #fff;
      border-radius: 14px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .table-responsive {
      border-radius: 10px;
      overflow: hidden;
    }

    table {
      width: 100%;
      margin-bottom: 0;
    }

    table thead {
      background: #c7290f;
      color: #fff;
    }

    table thead th {
      padding: 12px;
      font-weight: 600;
      text-align: center;
      border: none;
    }

    table tbody td {
      padding: 12px;
      text-align: center;
      vertical-align: middle;
      border-bottom: 1px solid #dee2e6;
    }

    table tbody tr:hover {
      background-color: #f8f9fa;
    }

    .badge {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .badge-tunai { background: #198754; color: #fff; }
    .badge-transfer { background: #0d6efd; color: #fff; }
    .badge-qris { background: #6f42c1; color: #fff; }
    .badge-debit { background: #fd7e14; color: #fff; }

    .btn-action {
      padding: 6px 12px;
      border-radius: 6px;
      border: none;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.3s;
      text-decoration: none;
      display: inline-block;
      margin: 0 2px;
    }

    .btn-detail {
      background: #0d6efd;
      color: #fff;
    }

    .btn-detail:hover {
      background: #0b5ed7;
      color: #fff;
    }

    .btn-print {
      background: #198754;
      color: #fff;
    }

    .btn-print:hover {
      background: #157347;
      color: #fff;
    }

    /* Pagination */
    .pagination-wrapper {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #dee2e6;
    }

    .pagination-info {
      font-size: 0.9rem;
      color: #6c757d;
    }

    .pagination {
      display: flex;
      gap: 5px;
      margin: 0;
    }

    .pagination a,
    .pagination span {
      padding: 8px 12px;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      text-decoration: none;
      color: #495057;
      transition: all 0.3s;
    }

    .pagination a:hover {
      background: #c7290f;
      color: #fff;
      border-color: #c7290f;
    }

    .pagination .active {
      background: #c7290f;
      color: #fff;
      border-color: #c7290f;
      font-weight: 600;
    }

    .pagination .disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #6c757d;
    }

    .empty-state i {
      font-size: 4rem;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .empty-state h5 {
      font-weight: 600;
      margin-bottom: 10px;
    }
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
      <!-- Filter Card -->
      <div class="filter-card">
        <h5 class="fw-bold mb-3"><i class="bi bi-funnel me-2"></i>Filter Transaksi</h5>
        <form method="GET" action="">
          <div class="filter-row">
            <div class="filter-group">
              <label><i class="bi bi-search me-1"></i>Cari</label>
              <input type="text" name="search" placeholder="ID / Kode / Kasir" value="<?= htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
              <label><i class="bi bi-calendar me-1"></i>Tanggal Dari</label>
              <input type="date" name="tanggal_dari" value="<?= htmlspecialchars($tanggal_dari); ?>">
            </div>
            <div class="filter-group">
              <label><i class="bi bi-calendar-check me-1"></i>Tanggal Sampai</label>
              <input type="date" name="tanggal_sampai" value="<?= htmlspecialchars($tanggal_sampai); ?>">
            </div>
            <div class="filter-group">
              <label><i class="bi bi-credit-card me-1"></i>Metode Pembayaran</label>
              <select name="metode_pembayaran">
                <option value="">Semua</option>
                <option value="Tunai" <?= $metode_pembayaran === 'Tunai' ? 'selected' : ''; ?>>Tunai</option>
                <option value="Transfer" <?= $metode_pembayaran === 'Transfer' ? 'selected' : ''; ?>>Transfer</option>
                <option value="QRIS" <?= $metode_pembayaran === 'QRIS' ? 'selected' : ''; ?>>QRIS</option>
                <option value="Debit" <?= $metode_pembayaran === 'Debit' ? 'selected' : ''; ?>>Debit</option>
              </select>
            </div>
            <div class="filter-group" style="min-width: auto;">
              <button type="submit" class="btn-filter">
                <i class="bi bi-search me-1"></i>Cari
              </button>
            </div>
            <div class="filter-group" style="min-width: auto;">
              <a href="index.php" class="btn-reset">
                <i class="bi bi-arrow-clockwise me-1"></i>Reset
              </a>
            </div>
          </div>
        </form>
      </div>

      <!-- Table Card -->
      <div class="table-card">
        <h5 class="fw-bold mb-3">
          <i class="bi bi-clock-history me-2"></i>Riwayat Transaksi
          <span class="badge bg-secondary ms-2"><?= $total_records; ?> Transaksi</span>
        </h5>

        <?php if ($result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>ID Transaksi</th>
                  <th>Kode Transaksi</th>
                  <th>Tanggal & Waktu</th>
                  <th>Kasir</th>
                  <th>Total</th>
                  <th>Pembayaran</th>
                  <th>Kembalian</th>
                  <th>Metode</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $no = $offset + 1;
                while($row = $result->fetch_assoc()): 
                ?>
                  <tr>
                    <td><?= $no++; ?></td>
                    <td><strong>#<?= $row['id_transaksi']; ?></strong></td>
                    <td><code><?= htmlspecialchars($row['kode_transaksi'] ?? '-'); ?></code></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                    <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><strong>Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></strong></td>
                    <td>Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.'); ?></td>
                    <td>Rp <?= number_format($row['kembalian'], 0, ',', '.'); ?></td>
                    <td>
                      <?php
                      $badge_class = 'badge-tunai';
                      if ($row['metode_pembayaran'] === 'Transfer') $badge_class = 'badge-transfer';
                      elseif ($row['metode_pembayaran'] === 'QRIS') $badge_class = 'badge-qris';
                      elseif ($row['metode_pembayaran'] === 'Debit') $badge_class = 'badge-debit';
                      ?>
                      <span class="badge <?= $badge_class; ?>"><?= htmlspecialchars($row['metode_pembayaran']); ?></span>
                    </td>
                    <td>
                      <button class="btn-action btn-detail" onclick="viewDetail(<?= $row['id_transaksi']; ?>)">
                        <i class="bi bi-eye"></i> Detail
                      </button>
                      <a href="print_struk.php?id=<?= $row['id_transaksi']; ?>" target="_blank" class="btn-action btn-print">
                        <i class="bi bi-printer"></i> Cetak
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="pagination-wrapper">
            <div class="pagination-info">
              Menampilkan <?= $offset + 1; ?> - <?= min($offset + $records_per_page, $total_records); ?> dari <?= $total_records; ?> transaksi
            </div>
            <div class="pagination">
              <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1; ?><?= !empty($tanggal_dari) ? '&tanggal_dari=' . $tanggal_dari : ''; ?><?= !empty($tanggal_sampai) ? '&tanggal_sampai=' . $tanggal_sampai : ''; ?><?= !empty($metode_pembayaran) ? '&metode_pembayaran=' . $metode_pembayaran : ''; ?><?= !empty($search) ? '&search=' . $search : ''; ?>">
                  <i class="bi bi-chevron-left"></i>
                </a>
              <?php else: ?>
                <span class="disabled"><i class="bi bi-chevron-left"></i></span>
              <?php endif; ?>

              <?php
              $start_page = max(1, $page - 2);
              $end_page = min($total_pages, $page + 2);

              if ($start_page > 1) {
                echo '<a href="?page=1' . (!empty($tanggal_dari) ? '&tanggal_dari=' . $tanggal_dari : '') . (!empty($tanggal_sampai) ? '&tanggal_sampai=' . $tanggal_sampai : '') . (!empty($metode_pembayaran) ? '&metode_pembayaran=' . $metode_pembayaran : '') . (!empty($search) ? '&search=' . $search : '') . '">1</a>';
                if ($start_page > 2) {
                  echo '<span class="disabled">...</span>';
                }
              }

              for ($i = $start_page; $i <= $end_page; $i++) {
                if ($i == $page) {
                  echo '<span class="active">' . $i . '</span>';
                } else {
                  echo '<a href="?page=' . $i . (!empty($tanggal_dari) ? '&tanggal_dari=' . $tanggal_dari : '') . (!empty($tanggal_sampai) ? '&tanggal_sampai=' . $tanggal_sampai : '') . (!empty($metode_pembayaran) ? '&metode_pembayaran=' . $metode_pembayaran : '') . (!empty($search) ? '&search=' . $search : '') . '">' . $i . '</a>';
                }
              }

              if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                  echo '<span class="disabled">...</span>';
                }
                echo '<a href="?page=' . $total_pages . (!empty($tanggal_dari) ? '&tanggal_dari=' . $tanggal_dari : '') . (!empty($tanggal_sampai) ? '&tanggal_sampai=' . $tanggal_sampai : '') . (!empty($metode_pembayaran) ? '&metode_pembayaran=' . $metode_pembayaran : '') . (!empty($search) ? '&search=' . $search : '') . '">' . $total_pages . '</a>';
              }
              ?>

              <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1; ?><?= !empty($tanggal_dari) ? '&tanggal_dari=' . $tanggal_dari : ''; ?><?= !empty($tanggal_sampai) ? '&tanggal_sampai=' . $tanggal_sampai : ''; ?><?= !empty($metode_pembayaran) ? '&metode_pembayaran=' . $metode_pembayaran : ''; ?><?= !empty($search) ? '&search=' . $search : ''; ?>">
                  <i class="bi bi-chevron-right"></i>
                </a>
              <?php else: ?>
                <span class="disabled"><i class="bi bi-chevron-right"></i></span>
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h5>Tidak Ada Transaksi</h5>
            <p>Belum ada transaksi yang tercatat<?= (!empty($tanggal_dari) || !empty($tanggal_sampai) || !empty($metode_pembayaran) || !empty($search)) ? ' dengan filter yang dipilih' : ''; ?>.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Detail Modal -->
  <div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Detail Transaksi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="detailContent">
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function viewDetail(id) {
      const modal = new bootstrap.Modal(document.getElementById('detailModal'));
      const content = document.getElementById('detailContent');
      
      content.innerHTML = `
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      `;
      
      modal.show();
      
      fetch(`get_detail.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
          content.innerHTML = html;
        })
        .catch(error => {
          content.innerHTML = '<div class="alert alert-danger">Gagal memuat detail transaksi</div>';
        });
    }
  </script>
</body>
</html>