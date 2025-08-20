<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header("Location: ../unauthorized.php");
    exit;
}

require '../config/db.php';

// Ambil daftar barang + kategori
$reportData = [];
$result = $koneksi->query("
    SELECT i.id, i.name, c.name AS kategori
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.id
");

while ($row = $result->fetch_assoc()) {
    $item_id = $row['id'];

    // Total Masuk
    $masukRes = $koneksi->query("SELECT SUM(qty) AS total_masuk FROM barang_masuk WHERE item_id = $item_id");
    $masuk = $masukRes->fetch_assoc()['total_masuk'] ?? 0;

    // Total Keluar
    $keluarRes = $koneksi->query("SELECT COUNT(*) AS total_keluar FROM barang_keluar WHERE item_id = $item_id");
    $keluar = $keluarRes->fetch_assoc()['total_keluar'] ?? 0;

    // Stok akhir dari tabel items
    $stokRes = $koneksi->query("SELECT stock FROM items WHERE id = $item_id");
    $stok = $stokRes->fetch_assoc()['stock'] ?? 0;

    $reportData[] = [
        'name' => $row['name'],
        'kategori' => $row['kategori'],
        'masuk' => $masuk,
        'keluar' => $keluar,
        'stok' => $stok,
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Stok</title>
</head>
<body>
    <h2>Laporan Stok Barang</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th>Total Masuk</th>
            <th>Total Keluar</th>
            <th>Stok Akhir</th>
        </tr>
        <?php foreach ($reportData as $i => $data): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($data['name']) ?></td>
            <td><?= htmlspecialchars($data['kategori']) ?></td>
            <td><?= $data['masuk'] ?></td>
            <td><?= $data['keluar'] ?></td>
            <td><?= $data['stok'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
