<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

// Tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $koneksi->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        header("Location: kategori.php");
        exit;
    }
}

// Ambil semua kategori
$kategoriList = [];
$result = $koneksi->query("SELECT * FROM categories ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $kategoriList[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manajemen Kategori Barang</title>
</head>
<body>
    <h2>Tambah Kategori</h2>
    <form method="POST" action="">
        <label>Nama Kategori:</label><br>
        <input type="text" name="name" required>
        <button type="submit">Tambah</button>
    </form>

    <hr>
    <h3>Daftar Kategori</h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Aksi</th>
        </tr>
        <?php foreach ($kategoriList as $i => $kat): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($kat['name']) ?></td>
            <td>
                <a href="edit_kategori.php?id=<?= $kat['id'] ?>">Edit</a> |
                <a href="hapus_kategori.php?id=<?= $kat['id'] ?>" onclick="return confirm('Hapus kategori ini?')">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

