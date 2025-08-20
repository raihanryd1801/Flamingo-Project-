<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

// Ambil data barang
$itemList = [];
$result = $koneksi->query("SELECT id, name, stock FROM items");
while ($row = $result->fetch_assoc()) {
    $itemList[] = $row;
}

// Ambil data barang keluar
$barangKeluar = [];
$result2 = $koneksi->query("SELECT bk.*, i.name AS item_name FROM barang_keluar bk JOIN items i ON bk.item_id = i.id ORDER BY bk.tanggal DESC");
while ($row = $result2->fetch_assoc()) {
    $barangKeluar[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barang Keluar</title>
</head>
<body>
    <h2>Form Barang Keluar</h2>
    <form action="../process/insert_keluar.php" method="POST">
        <label>Tanggal:</label><br>
        <input type="date" name="tanggal" required><br><br>

        <label>Client:</label><br>
        <input type="text" name="client" required><br><br>

        <label>Nama Barang:</label><br>
        <select name="item_id" id="item_id" onchange="loadSN()" required>
            <option value="">-- Pilih Barang --</option>
            <?php foreach ($itemList as $b): ?>
                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Qty:</label><br>
        <input type="number" name="qty" id="qty" min="1" required><br><br>

        <label>Serial Number (SN):</label><br>
        <div id="sn_container"></div><br>

        <label>Keterangan:</label><br>
        <textarea name="keterangan"></textarea><br><br>

        <button type="submit">Keluarkan Barang</button>
    </form>

    <hr>
    <h3>Riwayat Barang Keluar</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>Tanggal</th>
            <th>Client</th>
            <th>Nama Barang</th>
            <th>Qty</th>
            <th>SN</th>
            <th>Keterangan</th>
        </tr>
        <?php foreach ($barangKeluar as $bk): ?>
        <tr>
            <td><?= $bk['tanggal'] ?></td>
            <td><?= htmlspecialchars($bk['client']) ?></td>
            <td><?= htmlspecialchars($bk['item_name']) ?></td>
            <td><?= $bk['qty'] ?></td>
            <td><?= htmlspecialchars($bk['sn']) ?></td>
            <td><?= htmlspecialchars($bk['keterangan']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

<script>
function loadSN() {
    const itemId = document.getElementById('item_id').value;
    if (!itemId) return;

    fetch('ajax_get_sn.php?item_id=' + itemId)
        .then(response => response.json())
        .then(data => {
            const snContainer = document.getElementById('sn_container');
            snContainer.innerHTML = '';

            data.forEach(sn => {
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'sn[]';
                checkbox.value = sn;

                const label = document.createElement('label');
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(' ' + sn));

                const br = document.createElement('br');

                snContainer.appendChild(label);
                snContainer.appendChild(br);
            });
        });
}
</script>
