<?php
session_start();
require __DIR__ . '/db.php'; // $koneksi = new mysqli(...);

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputKey = trim($_POST['license_key']);

    $stmt = $koneksi->prepare("SELECT id, status, expiry_date FROM licenses WHERE license_key = ? LIMIT 1");
    $stmt->bind_param("s", $inputKey);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $message = "? License tidak ditemukan!";
        $message_type = "error";
    } else {
        $data = $res->fetch_assoc();
        $status = $data['status'];
        $expiry = $data['expiry_date'];

        if ($status !== 'active') {
            $message = "? License tidak aktif!";
            $message_type = "error";
        } elseif ($expiry < date('Y-m-d')) {
            $message = "License sudah expired!";
            $message_type = "error";
        } else {
            // Simpan ke table license_active (replace license lama)
            $koneksi->query("TRUNCATE TABLE license_active");
            $stmt2 = $koneksi->prepare("INSERT INTO license_active (license_key) VALUES (?)");
            $stmt2->bind_param("s", $inputKey);
            $stmt2->execute();

            $message = "License berhasil diaktifkan!";
            $message_type = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Aktivasi License</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f4f9;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .container {
        background: #fff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        text-align: center;
        width: 400px;
    }
    input[type="text"] {
        width: 80%;
        padding: 10px;
        margin: 15px 0;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 16px;
        transition: 0.3s;
    }
    input[type="text"]:focus {
        border-color: #4a90e2;
        box-shadow: 0 0 5px #4a90e2;
        outline: none;
    }
    button {
        padding: 10px 20px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin: 5px;
    }
    button:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .submit-btn {
        background: #4a90e2;
        color: #fff;
    }
    .refresh-btn {
        background: #7ed321;
        color: #fff;
    }
    .message {
        margin-top: 15px;
        padding: 10px;
        border-radius: 6px;
        font-weight: bold;
    }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
</style>
</head>
<body>
<div class="container">
    <h2>Aktivasi License</h2>
    <form method="post">
        <input type="text" name="license_key" placeholder="Masukkan License Key" required>
        <br>
        <button type="submit" class="submit-btn">Aktifkan</button>
        <button type="button" onclick="window.location.href='/index.php';" class="refresh-btn">Refresh / Kembali</button>
    </form>

    <?php if($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

