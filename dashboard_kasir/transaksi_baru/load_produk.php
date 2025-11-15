<?php
session_start();
include "../../database.php";

// Pastikan hanya petugas yang bisa mengakses
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Petugas') {
  echo json_encode(['error' => 'Unauthorized']);
  exit();
}

$id_outlet = $_SESSION['id_outlet'];

// Ambil daftar produk
$query_produk = "SELECT id_produk, nama_produk, kategori, harga, stok FROM produk WHERE id_outlet = ? AND stok > 0 ORDER BY kategori, nama_produk";
$stmt = $conn->prepare($query_produk);
$stmt->bind_param("i", $id_outlet);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while($row = $result->fetch_assoc()) {
  $products[] = $row;
}

// Fungsi untuk mendapatkan icon berdasarkan kategori
function getIconByCategory($kategori) {
  switch($kategori) {
    case 'Makanan':
      return 'bi-egg-fried';
    case 'Minuman':
      return 'bi-cup-straw';
    case 'Topping':
      return 'bi-plus-circle-dotted';
    case 'Bundle':
      return 'bi-box-seam';
    default:
      return 'bi-basket';
  }
}

// Generate HTML untuk produk
$html = '';
foreach($products as $produk) {
  $icon = getIconByCategory($produk['kategori']);
  $html .= '
    <div class="product-card" 
         data-id="' . $produk['id_produk'] . '"
         data-name="' . htmlspecialchars($produk['nama_produk']) . '"
         data-price="' . $produk['harga'] . '"
         data-stock="' . $produk['stok'] . '"
         data-category="' . htmlspecialchars($produk['kategori']) . '">
      <div class="product-icon">
        <i class="bi ' . $icon . '"></i>
      </div>
      <div class="product-name">' . htmlspecialchars($produk['nama_produk']) . '</div>
      <div class="product-price">Rp ' . number_format($produk['harga'], 0, ',', '.') . '</div>
      <span class="product-category category-' . strtolower($produk['kategori']) . '">
        ' . htmlspecialchars($produk['kategori']) . '
      </span>
    </div>
  ';
}

echo $html;
?>