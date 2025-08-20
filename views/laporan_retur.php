<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

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
    <title>Laporan Barang Retur</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px 12px; border: 1px solid #ccc; }
        th { background-color: #f0f0f0; }
        .baik { color: green; font-weight: bold; }
        .rusak { color: red; font-weight: bold; }
        .digunakan { background-color: #d0f0d0; }
        .td-center { text-align: center; }
    </style>
</head>
<body>

    <h2>Laporan Barang Retur</h2>
    <table>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Nama Barang</th>
            <th>Serial Number</th>
            <th>Kondisi</th>
            <th>Digunakan Ulang</th>
            <th>Keterangan</th>
        </tr>
        <?php foreach ($returList as $i => $retur): ?>
        <tr class="<?= $retur['digunakan_ulang'] ? 'digunakan' : '' ?>">
            <td class="td-center"><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($retur['tanggal']) ?></td>
            <td><?= htmlspecialchars($retur['item_name']) ?></td>
            <td><?= htmlspecialchars($retur['sn']) ?></td>
            <td class="<?= $retur['kondisi'] === 'baik' ? 'baik' : 'rusak' ?>">
                <?= ucfirst($retur['kondisi']) ?>
            </td>
            <td class="td-center"><?= $retur['digunakan_ulang'] ? '? Ya' : '? Tidak' ?></td>
            <td><?= htmlspecialchars($retur['keterangan'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

</body>
</html>

