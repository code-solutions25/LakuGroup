<?php
$conn = new mysqli("localhost", "root", "", "lakugroup");

if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}
?>
