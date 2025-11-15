<?php
session_start();
include "../../database.php";

// Pastikan hanya petugas yang bisa mengakses
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Petugas') {
  echo '<div class="alert alert-danger">Unauthorized</div>';
  exit();
}

$id_transaksi = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_outlet = $_SESSION['id_outlet'];

if ($id_transaksi <= 0) {
  echo '<div class="alert alert-danger">ID transaksi tidak valid</div>';
  exit();
}

try {
  // Ambil data transaksi
  $query = "SELECT t.*, u.nama_lengkap 
            FROM transaksi t 
            LEFT JOIN user u ON t.id_user = u.id_user 
            WHERE t.id_transaksi = ? AND t.id_outlet = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ii", $id_transaksi, $id_outlet);
  $stmt->execute();
  $transaksi = $stmt->get_result()->fetch_assoc();

  if (!$transaksi) {
    echo '<div class="alert alert-danger">Transaksi tidak ditemukan</div>';
    exit();
  }

  // Ambil detail transaksi
  $query_detail = "SELECT dt.* 
                   FROM detail_transaksi dt 
                   WHERE dt.id_transaksi = ?";
  $stmt_detail = $conn->prepare($query_detail);
  $stmt_detail->bind_param("i", $id_transaksi);
  $stmt_detail->execute();
  $result_detail = $stmt_detail->get_result();

  $items = [];
  while ($row = $result_detail->fetch_assoc()) {
    $items[] = $row;
  }

} catch (Exception $e) {
  echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
  exit();
}
?>

<style>
  .detail-header {
    background: linear-gradient(135deg, #c7290f 0%, #a61f0b 100%);
    color: #fff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
  }

  .header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(255,255,255,0.3);
  }

  .header-code {
    font-size: 0.9rem;
  }

  .header-code small {
    opacity: 0.8;
  }

  .header-code strong {
    font-size: 1.1rem;
    letter-spacing: 1px;
  }

  .detail-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
  }

  .info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #c7290f;
  }

  .info-label {
    font-weight: 600;
    color: #495057;
  }

  .info-value {
    color: #212529;
    font-weight: 500;
  }

  .detail-table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
  }

  .detail-table thead {
    background: #c7290f;
    color: #fff;
  }

  .detail-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
  }

  .detail-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
  }

  .detail-table tbody tr:hover {
    background: #f8f9fa;
  }

  .total-section {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 2px solid #c7290f;
  }

  .total-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 1rem;
  }

  .total-row.grand-total {
    border-top: 2px solid #c7290f;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 1.2rem;
    font-weight: 700;
    color: #c7290f;
  }

  .badge-method {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
  }

  .badge-tunai { background: #198754; color: #fff; }
  .badge-transfer { background: #0d6efd; color: #fff; }
  .badge-qris { background: #6f42c1; color: #fff; }
  .badge-debit { background: #fd7e14; color: #fff; }

  @media (max-width: 768px) {
    .detail-info {
      grid-template-columns: 1fr;
    }
    .header-top {
      flex-direction: column;
      gap: 10px;
      text-align: center;
    }
  }
</style>

<!-- Detail Header -->
<div class="detail-header">
  <div class="header-top">
    <div>
      <h5 class="mb-0">
        <i class="bi bi-receipt-cutoff me-2"></i>
        Detail Transaksi #<?= $transaksi['id_transaksi']; ?>
      </h5>
    </div>
    <div class="header-code text-end">
      <small>Kode Transaksi</small><br>
      <strong><?= htmlspecialchars($transaksi['kode_transaksi'] ?? '-'); ?></strong>
    </div>
  </div>
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <small>Tanggal & Waktu</small><br>
      <strong><?= date('d M Y, H:i', strtotime($transaksi['tanggal'])); ?></strong>
    </div>
    <div class="text-end">
      <small>Status</small><br>
      <span class="badge bg-success">Selesai</span>
    </div>
  </div>
</div>

<!-- Info Transaksi -->
<div class="detail-info">
  <div class="info-item">
    <span class="info-label"><i class="bi bi-person me-2"></i>Kasir</span>
    <span class="info-value"><?= htmlspecialchars($transaksi['nama_lengkap']); ?></span>
  </div>
  <div class="info-item">
    <span class="info-label"><i class="bi bi-credit-card me-2"></i>Metode Pembayaran</span>
    <span class="info-value">
      <?php
      $badge_class = 'badge-tunai';
      if ($transaksi['metode_pembayaran'] === 'Transfer') $badge_class = 'badge-transfer';
      elseif ($transaksi['metode_pembayaran'] === 'QRIS') $badge_class = 'badge-qris';
      elseif ($transaksi['metode_pembayaran'] === 'Debit') $badge_class = 'badge-debit';
      ?>
      <span class="badge-method <?= $badge_class; ?>"><?= htmlspecialchars($transaksi['metode_pembayaran']); ?></span>
    </span>
  </div>
  <div class="info-item">
    <span class="info-label"><i class="bi bi-box-seam me-2"></i>Total Item</span>
    <span class="info-value"><?= count($items); ?> Item</span>
  </div>
  <div class="info-item">
    <span class="info-label"><i class="bi bi-cash-stack me-2"></i>Total Harga</span>
    <span class="info-value"><strong>Rp <?= number_format($transaksi['total_harga'], 0, ',', '.'); ?></strong></span>
  </div>
</div>

<!-- Tabel Detail Produk -->
<h6 class="mt-4 mb-3 fw-bold">
  <i class="bi bi-cart3 me-2"></i>Detail Produk
</h6>
<div class="table-responsive">
  <table class="detail-table">
    <thead>
      <tr>
        <th>No</th>
        <th>Nama Produk</th>
        <th class="text-center">Qty</th>
        <th class="text-end">Harga Satuan</th>
        <th class="text-end">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      $no = 1;
      foreach($items as $item): 
      ?>
        <tr>
          <td><?= $no++; ?></td>
          <td>
            <strong><?= htmlspecialchars($item['nama_produk']); ?></strong>
          </td>
          <td class="text-center">
            <span class="badge bg-secondary"><?= $item['jumlah']; ?></span>
          </td>
          <td class="text-end">Rp <?= number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
          <td class="text-end">
            <strong>Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></strong>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Total Section -->
<div class="total-section">
  <div class="total-row">
    <span>Subtotal</span>
    <span><strong>Rp <?= number_format($transaksi['total_harga'], 0, ',', '.'); ?></strong></span>
  </div>
  <div class="total-row grand-total">
    <span>TOTAL</span>
    <span>Rp <?= number_format($transaksi['total_harga'], 0, ',', '.'); ?></span>
  </div>
  <div class="total-row mt-3" style="border-top: 1px dashed #dee2e6; padding-top: 15px;">
    <span>Jumlah Bayar</span>
    <span>Rp <?= number_format($transaksi['jumlah_bayar'], 0, ',', '.'); ?></span>
  </div>
  <div class="total-row">
    <span>Kembalian</span>
    <span class="text-success">
      <strong>Rp <?= number_format($transaksi['kembalian'], 0, ',', '.'); ?></strong>
    </span>
  </div>
</div>

<!-- Footer Button -->
<div class="mt-4 text-end">
  <a href="print_struk.php?id=<?= $transaksi['id_transaksi']; ?>" target="_blank" class="btn btn-success">
    <i class="bi bi-printer me-2"></i>Cetak Struk
  </a>
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
    <i class="bi bi-x-circle me-2"></i>Tutup
  </button>
</div>

<?php $conn->close(); ?>