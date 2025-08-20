<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header("Location: ../unauthorized.php");
    exit;
}

require '../config/db.php';

// Ambil kategori untuk dropdown
$kategoriList = [];
$katQuery = $koneksi->query("SELECT id, name FROM categories");
while ($row = $katQuery->fetch_assoc()) {
    $kategoriList[] = $row;
}

// Simpan barang jika ada submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $stock = intval($_POST['stock']);
    $category_id = $_POST['category_id'];
    $kategori_sn = $_POST['kategori_sn'];

    $stmt = $koneksi->prepare("INSERT INTO items (name, stock, category_id, kategori_sn) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siis", $name, $stock, $category_id, $kategori_sn);
    $stmt->execute();
    header("Location: barang.php");
    exit;
}

// Ambil semua barang
$barangList = [];
$res = $koneksi->query("
    SELECT i.*, c.name AS kategori 
    FROM items i 
    LEFT JOIN categories c ON i.category_id = c.id
    ORDER BY i.id DESC
");
while ($row = $res->fetch_assoc()) {
    $barangList[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Barang</title>
</head>
<body>
    <h2>Tambah Barang Baru</h2>
    <form method="POST">
        <label>Nama Barang:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Kategori:</label><br>
        <select name="category_id" required>
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($kategoriList as $kat): ?>
                <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Kategori SN (Contoh: SVR, MDM, RTR):</label><br>
        <input type="text" name="kategori_sn" required><br><br>

        <label>Stok Awal:</label><br>
        <input type="number" name="stock" min="0" value="0" required><br><br>

        <button type="submit">Simpan Barang</button>
    </form>

    <hr>
    <h3>Daftar Barang</h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Kategori SN</th>
            <th>Stok</th>
        </tr>
        <?php foreach ($barangList as $i => $b): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($b['name']) ?></td>
            <td><?= htmlspecialchars($b['kategori']) ?></td>
            <td><?= htmlspecialchars($b['kategori_sn']) ?></td>
            <td><?= $b['stock'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
