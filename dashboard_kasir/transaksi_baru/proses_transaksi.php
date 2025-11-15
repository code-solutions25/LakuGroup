<?php
// Jangan ada spasi atau karakter apapun sebelum tag <?php

session_start();

// Bersihkan semua output buffer yang mungkin ada
while (ob_get_level()) {
    ob_end_clean();
}

// Start output buffering
ob_start();

// Set header JSON
header('Content-Type: application/json; charset=utf-8');

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Cek session
    if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Petugas') {
        throw new Exception('Unauthorized - Session invalid');
    }

    // Include database
    include "../../database.php";

    // Ambil data dari request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    $cart = isset($data['cart']) ? $data['cart'] : [];
    $payment_method = isset($data['payment_method']) ? $data['payment_method'] : '';
    $payment_amount = isset($data['payment_amount']) ? floatval($data['payment_amount']) : 0;
    $change_amount = isset($data['change_amount']) ? floatval($data['change_amount']) : 0;
    $id_outlet = isset($_SESSION['id_outlet']) ? $_SESSION['id_outlet'] : 0;
    $nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : '';

    // Validasi session
    if (empty($id_outlet) || empty($nama_user)) {
        throw new Exception('Session data tidak lengkap');
    }

    // Cari id_user berdasarkan nama_lengkap dari tabel user
    $query_user = "SELECT id_user FROM user WHERE nama_lengkap = ? AND id_outlet = ?";
    $stmt_user = $conn->prepare($query_user);

    if (!$stmt_user) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt_user->bind_param("si", $nama_user, $id_outlet);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($row_user = $result_user->fetch_assoc()) {
        $id_user = $row_user['id_user'];
    } else {
        throw new Exception('User tidak ditemukan: ' . $nama_user);
    }

    // Validasi data
    if (empty($cart)) {
        throw new Exception('Keranjang kosong');
    }

    if (empty($payment_method)) {
        throw new Exception('Metode pembayaran belum dipilih');
    }

    // Start transaction
    $conn->begin_transaction();

    // Hitung total harga
    $total_harga = 0;
    foreach ($cart as $item) {
        $total_harga += floatval($item['price']) * intval($item['qty']);
    }

    // Validasi jumlah bayar
    if ($payment_amount < $total_harga) {
        throw new Exception('Jumlah bayar tidak mencukupi');
    }

    // Generate kode transaksi unik
    $kode_transaksi = 'TRX' . date('YmdHis') . rand(100, 999);

    // Insert ke tabel transaksi dengan semua kolom yang diperlukan
    $query_transaksi = "INSERT INTO transaksi 
                        (kode_transaksi, id_outlet, id_user, tanggal, total_harga, jumlah_bayar, kembalian, metode_pembayaran, status) 
                        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'Selesai')";
    $stmt = $conn->prepare($query_transaksi);
    
    if (!$stmt) {
        throw new Exception('Prepare transaksi failed: ' . $conn->error);
    }
    
    $stmt->bind_param("siiddds", $kode_transaksi, $id_outlet, $id_user, $total_harga, $payment_amount, $change_amount, $payment_method);
    
    if (!$stmt->execute()) {
        if ($conn->errno == 1062) {
            throw new Exception('Terjadi konflik data. Silakan coba lagi.');
        }
        throw new Exception('Insert transaksi failed: ' . $stmt->error);
    }

    $id_transaksi = $conn->insert_id;
    
    if ($id_transaksi <= 0) {
        throw new Exception('Gagal mendapatkan ID transaksi');
    }

    // Insert detail transaksi dengan nama_produk
    $query_detail = "INSERT INTO detail_transaksi 
                     (id_transaksi, id_produk, nama_produk, jumlah, harga_satuan, subtotal) 
                     VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_detail = $conn->prepare($query_detail);
    
    if (!$stmt_detail) {
        throw new Exception('Prepare detail failed: ' . $conn->error);
    }

    $query_update_stok = "UPDATE produk SET stok = stok - ? WHERE id_produk = ?";
    $stmt_update = $conn->prepare($query_update_stok);
    
    if (!$stmt_update) {
        throw new Exception('Prepare update stok failed: ' . $conn->error);
    }

    foreach ($cart as $item) {
        $id_produk = intval($item['id']);
        $nama_produk = $item['name'];
        $qty = intval($item['qty']);
        $price = floatval($item['price']);
        $subtotal = $price * $qty;
        
        // Cek stok dengan FOR UPDATE untuk lock
        $query_check = "SELECT stok, nama_produk FROM produk WHERE id_produk = ? FOR UPDATE";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("i", $id_produk);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result()->fetch_assoc();
        
        if (!$result_check) {
            throw new Exception('Produk ID ' . $id_produk . ' tidak ditemukan');
        }
        
        if ($result_check['stok'] < $qty) {
            throw new Exception('Stok ' . $result_check['nama_produk'] . ' tidak cukup. Sisa: ' . $result_check['stok']);
        }
        
        // Insert detail dengan nama_produk
        $stmt_detail->bind_param("iisidd", $id_transaksi, $id_produk, $nama_produk, $qty, $price, $subtotal);
        if (!$stmt_detail->execute()) {
            throw new Exception('Insert detail failed: ' . $stmt_detail->error);
        }

        // Update stok
        $stmt_update->bind_param("ii", $qty, $id_produk);
        if (!$stmt_update->execute()) {
            throw new Exception('Update stok failed: ' . $stmt_update->error);
        }
    }

    // Commit
    $conn->commit();

    // Clear output buffer
    ob_end_clean();

    $response = [
        'success' => true, 
        'message' => 'Transaksi berhasil',
        'id_transaksi' => $id_transaksi,
        'kode_transaksi' => $kode_transaksi,
        'nama_kasir' => $nama_user,
        'total_harga' => $total_harga,
        'jumlah_bayar' => $payment_amount,
        'kembalian' => $change_amount
    ];

    echo json_encode($response);

} catch (Exception $e) {
    // Rollback jika ada transaksi
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Clear output buffer
    ob_end_clean();
    
    $error_response = [
        'success' => false, 
        'message' => $e->getMessage()
    ];

    echo json_encode($error_response);
}

// Pastikan script berhenti di sini
if (isset($conn)) {
    $conn->close();
}

exit();
?>