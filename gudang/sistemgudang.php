<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('error_reporting', (string)E_ALL);
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: ../login.php');
    exit;
}

// Batasi akses hanya untuk role tertentu
if ($_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

// ? QUERY untuk notifikasi stok habis/minimum
$query = "SELECT * FROM items WHERE stock <= stok_minimum";
$result = $koneksi->query($query);
$lowStockItems = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lowStockItems[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Sistem Gudang</title>
</head>
<body>
    <h2>Sistem Gudang Dankom (SISTEM DALAM MAINTENANCE)</h2>
    
    <h3>?? Notifikasi Stok Habis / Minimum</h3>
    <?php if (count($lowStockItems) > 0): ?>
        <ul>
            <?php foreach ($lowStockItems as $item): ?>
                <li>
                    <strong><?= htmlspecialchars($item['name']) ?></strong> stok tinggal <strong><?= $item['stock'] ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Tidak ada barang dengan stok minimum saat ini.</p>
    <?php endif; ?>

    <hr>
    <h3>?? Menu Sistem</h3>
    <ul>
        <li><a href="../views/kategori.php">Kategori Barang</a></li>
        <li><a href="../views/barang.php">Data Barang</a></li>
        <li><a href="../views/barang_masuk.php">Barang Masuk</a></li>
        <li><a href="../views/barang_keluar.php">Barang Keluar</a></li>
        <li><a href="../views/barang_retur.php">Barang Retur</a></li>
        <li><a href="../views/laporan.php">Laporan Stok</a></li>
    </ul>
</body>
</html>
