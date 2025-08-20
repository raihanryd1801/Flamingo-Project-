<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

// Ambil data item untuk dropdown (include kategori_sn)
$itemList = [];
$result = $koneksi->query("SELECT id, name, kategori_sn FROM items");
while ($row = $result->fetch_assoc()) {
    $itemList[] = $row;
}

// Ambil data barang masuk untuk ditampilkan
$barangMasuk = [];
$result2 = $koneksi->query("
    SELECT bm.*, i.name AS item_name 
    FROM barang_masuk bm 
    JOIN items i ON bm.item_id = i.id 
    ORDER BY bm.tanggal DESC
");
while ($row = $result2->fetch_assoc()) {
    $barangMasuk[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barang Masuk</title>
</head>
<body>
    <h2>Form Barang Masuk</h2>
    <form action="../process/insert_masuk.php" method="POST">
        <label>Tanggal:</label><br>
        <input type="date" name="tanggal" required><br><br>

        <label>Nama Barang:</label><br>
        <select name="item_id" id="item_id" required>
            <option value="">-- Pilih Barang --</option>
            <?php foreach ($itemList as $item): ?>
                <option value="<?= $item['id'] ?>" data-kategori-sn="<?= htmlspecialchars($item['kategori_sn']) ?>">
                    <?= htmlspecialchars($item['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Qty:</label><br>
        <input type="number" name="qty" min="1" required><br><br>

        <p><i>Serial Number akan digenerate otomatis berdasarkan jumlah (Qty)</i></p>

        <label>Keterangan:</label><br>
        <textarea name="keterangan"></textarea><br><br>

        <button type="submit">Simpan</button>
    </form>

    <hr>
    <h3>Riwayat Barang Masuk</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>Tanggal</th>
            <th>Nama Barang</th>
            <th>Qty</th>
            <th>SN</th>
            <th>Keterangan</th>
        </tr>
        <?php foreach ($barangMasuk as $bm): ?>
        <tr>
            <td><?= $bm['tanggal'] ?></td>
            <td><?= htmlspecialchars($bm['item_name']) ?></td>
            <td><?= $bm['qty'] ?></td>
            <td><?= htmlspecialchars($bm['sn']) ?></td>
            <td><?= htmlspecialchars($bm['keterangan']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

<script>
function generateSN() {
    const now = new Date();
    const bulan = String(now.getMonth() + 1).padStart(2, '0');
    const tahun = String(now.getFullYear()).slice(-2);
    const nomorUrut = '.AS140007'; // Bisa dibuat dinamis nanti

    const itemSelect = document.getElementById('item_id');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];

    const kategori = selectedOption.getAttribute('data-kategori-sn') || 'OTH';

    const sn = `DMA${nomorUrut}.${bulan}${tahun}.${kategori}`;
    document.getElementById('sn').value = sn;
}
</script>
