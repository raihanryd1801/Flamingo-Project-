<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

// Ambil data item untuk dropdown
$itemList = [];
$result = $koneksi->query("SELECT id, name FROM items");
while ($row = $result->fetch_assoc()) {
    $itemList[] = $row;
}

// Ambil data retur
$returList = [];
$query = "
    SELECT br.*, i.name AS item_name
    FROM barang_retur br
    JOIN items i ON br.item_id = i.id
    ORDER BY br.tanggal DESC
";
$result = $koneksi->query($query);
while ($row = $result->fetch_assoc()) {
    $returList[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barang Retur</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px 12px; border: 1px solid #ccc; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Form Barang Retur</h2>
    <form action="../process/insert_retur.php" method="POST">
        <label>Tanggal:</label><br>
        <input type="date" name="tanggal" required><br><br>

        <label>Client Asal:</label><br>
        <input type="text" name="client_asal" required><br><br>

        <label>Nama Barang:</label><br>
        <select name="item_id" id="item_id" onchange="loadSN()" required>
            <option value="">-- Pilih Barang --</option>
            <?php foreach ($itemList as $item): ?>
                <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Serial Number (SN):</label><br>
        <select name="sn" id="sn_select" required>
            <option value="">-- Pilih SN --</option>
        </select><br><br>

        <label>Kondisi Barang:</label><br>
        <select name="kondisi" required>
            <option value="baik">Baik</option>
            <option value="rusak">Rusak</option>
        </select><br><br>

        <label>Digunakan Ulang?</label><br>
        <select name="digunakan_ulang" required>
            <option value="1">Ya</option>
            <option value="0">Tidak</option>
        </select><br><br>

        <label>Keterangan:</label><br>
        <textarea name="keterangan"></textarea><br><br>

        <button type="submit">Simpan Retur</button>
    </form>

    <hr>
    <h3>Riwayat Retur Barang</h3>
    <table>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Client Asal</th>
            <th>Nama Barang</th>
            <th>SN</th>
            <th>Kondisi</th>
            <th>Digunakan Ulang</th>
            <th>Keterangan</th>
        </tr>
        <?php foreach ($returList as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($r['tanggal']) ?></td>
            <td><?= htmlspecialchars($r['client_asal']) ?></td>
            <td><?= htmlspecialchars($r['item_name']) ?></td>
            <td><?= htmlspecialchars($r['sn']) ?></td>
            <td><?= htmlspecialchars($r['kondisi']) ?></td>
            <td><?= $r['digunakan_ulang'] ? 'Ya' : 'Tidak' ?></td>
            <td><?= htmlspecialchars($r['keterangan'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <script>
    function loadSN() {
        const itemId = document.getElementById('item_id').value;
        if (!itemId) return;

        fetch('ajax_get_sn_retur.php?item_id=' + itemId)
            .then(response => response.json())
            .then(data => {
                const snSelect = document.getElementById('sn_select');
                snSelect.innerHTML = '<option value="">-- Pilih SN --</option>';
                data.forEach(sn => {
                    const opt = document.createElement('option');
                    opt.value = sn;
                    opt.text = sn;
                    snSelect.appendChild(opt);
                });
            });
    }
    </script>
</body>
</html>