<?php
session_start();

if (isset($_SESSION['user'])) {
  header("Location: dashboard_kasir");
  exit();
}

include "database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['username'];
  $plain_password = $_POST['password'];

  $sql = "SELECT user.*, outlet.nama_outlet 
          FROM user 
          LEFT JOIN outlet ON user.id_outlet = outlet.id_outlet 
          WHERE username = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if (password_verify($plain_password, $row['password'])) {
      $_SESSION['user'] = $row['username'];
      $_SESSION['nama'] = $row['nama_lengkap'];
      $_SESSION['role'] = $row['role'];
      $_SESSION['outlet'] = $row['nama_outlet'];
      $_SESSION['id_outlet'] = $row['id_outlet'];

      header("Location: dashboard_kasir/index.php");
      exit();
    } else {
      $error = "Kata sandi salah!";
    }
  } else {
    $error = "Username tidak ditemukan!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LakuGroup Kasir | Login</title>
  <link rel="icon" type="image/png" href="images/logo_2.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      height: 100vh;
      background-color: #fff;
      font-family: 'Poppins', sans-serif;
      overflow-x: hidden;
    }

    .login-container {
      display: flex;
      height: 100vh;
    }

    .left-side {
      background-color: #c7290f;
      color: white;
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      padding: 40px;
      text-align: center;
    }

    .left-side img {
      max-width: 300px;
      width: 85%;
      margin-bottom: 30px;
    }

    .left-side h1 {
      font-size: 2.4rem;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .left-side p {
      font-size: 1rem;
      opacity: 0.9;
    }

    .right-side {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px;
      background-color: #fff;
    }

    .login-card {
      width: 100%;
      max-width: 400px;
    }

    .btn-login {
      background-color: #c7290f;
      color: white;
      transition: 0.3s;
    }

    .btn-login:hover {
      background-color: #a61f0b;
    }

    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
        height: auto;
      }

      .left-side {
        height: 100vh;
        justify-content: center;
        text-align: center;
      }

      .left-side img {
        max-width: 280px;
        margin-bottom: 20px;
      }

      .right-side {
        height: auto;
        padding: 60px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <!-- BAGIAN KIRI -->
    <div class="left-side">
      <img src="images/logo_1.png" alt="Logo Kasir">
      <h1>Selamat Datang di<br>LakuGroup Kasir</h1>
      <p>Sistem digital untuk pengelolaan transaksi outlet Anda.</p>
    </div>

    <!-- BAGIAN KANAN -->
    <div class="right-side">
      <div class="login-card">
        <h3 class="text-center mb-4 fw-bold">Login Kasir</h3>
        <form method="POST" action="">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Kata Sandi</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan kata sandi" required>
          </div>
          <?php if (isset($error)): ?>
            <div class="alert alert-danger py-2 mt-2 mb-3 text-center"><?= $error ?></div>
          <?php endif; ?>
          <button type="submit" class="btn btn-login w-100 py-2">Masuk</button>
        </form>
        <p class="text-center mt-3 text-muted" style="font-size: 0.9rem;">
          © <?= date('Y') ?> LakuGroup Kasir. Semua Hak Dilindungi.
        </p>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
