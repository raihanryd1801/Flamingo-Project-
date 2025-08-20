<?php include '../config/db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Laporan Teknisi</title>
</head>
<body>
<h2>Data Laporan Teknisi</h2>
<table border="1" cellpadding="6">
    <tr>
        <th>No</th>
        <th>Jenis Pekerjaan</th>
        <th>Request By</th>
        <th>ID Pelanggan</th>
        <th>Nama Pelanggan</th>
        <th>Nama Teknisi</th>
        <th>Lokasi</th>
        <th>Waktu Mulai</th>
        <th>Waktu Selesai</th>
        <th>Total Waktu</th>
        <th>Indikasi Case</th>
        <th>Action</th>
        <th>Status Ticket</th>
        <th>Catatan</th>
    </tr>
    <?php
    $result = $conn->query("SELECT * FROM laporan ORDER BY id DESC");
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>{$no++}</td>
            <td>{$row['jenis_pekerjaan']}</td>
            <td>{$row['request_by']}</td>
            <td>{$row['id_pelanggan']}</td>
            <td>{$row['nama_pelanggan']}</td>
            <td>{$row['nama_teknisi']}</td>
            <td>{$row['lokasi']}</td>
            <td>{$row['waktu_mulai']}</td>
            <td>{$row['waktu_selesai']}</td>
            <td>{$row['total_waktu']}</td>
            <td>{$row['indikasi_case']}</td>
            <td>{$row['action']}</td>
            <td>{$row['status_ticket']}</td>
            <td>{$row['catatan']}</td>
        </tr>";
    }
    ?>
</table>
</body>
</html>

