<?php
// Tampilkan semua error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mulai session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/config/db.php';

// Cek akses: jika sudah login tapi bukan administrator, lempar ke index.php
if (isset($_SESSION['username']) && $_SESSION['role'] !== 'administrator') {
    header('Location: index.php');
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];
    $role     = $_POST['role'];

    if (empty($username) || empty($password) || empty($confirm) || empty($role)) {
        $error = "Semua field wajib diisi.";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $stmt = $koneksi->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmt) {
            die("Error prepare SELECT: " . $koneksi->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username sudah digunakan.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $koneksi->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            if (!$stmt) {
                die("Error prepare INSERT: " . $koneksi->error);
            }
            $stmt->bind_param("sss", $username, $hashed, $role);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil. Silakan <a href='login.php'>login</a>.";
            } else {
                $error = "Gagal menyimpan ke database: " . $stmt->error;
            }
        }
        $stmt->close();
    }

    $koneksi->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="register-box">
        <div class="register-header">
            <h2>Daftar Akun Baru</h2>
            <p>Silakan isi form berikut untuk mendaftar</p>
        </div>

        <?php
        if (!empty($error)) echo "<div class='message error'>$error</div>";
        if (!empty($success)) echo "<div class='message success'>$success</div>";
        ?>

        <form method="POST" class="register-form">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm" placeholder="Konfirmasi Password" required>
            </div>
            <div class="form-group">
                <select name="role" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="administrator">Administrator</option>
                    <option value="noc_voip">NOC VoIP</option>
                    <option value="noc_internet">NOC Internet</option>
                    <option value="admin_it">Admin Dankom</option>
                </select>
            </div>
            <button type="submit" class="register-button">Daftar</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login disini</a>
        </div>
    </div>
</div>
</body>
</html>