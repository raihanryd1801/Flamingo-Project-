<?php include_once '../config/db.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Laporan Teknisi</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
<h2>Form Input Aktivitas Teknisi</h2>
<form method="post" action="../process/insert.php">
    <label>Jenis Pekerjaan:</label>
    <select name="jenis_pekerjaan" required>
        <option value="">-- Pilih --</option>
        <option>Maintenance</option>
        <option>Aktivasi Baru</option>
        <option>Dismantle</option>
        <option>Preventive</option>
        <option>Relokasi</option>
        <option>Inquery</option>
        <option>Reactivations</option>
    </select><br>

    <label>Request By:</label>
    <select name="request_by" required>
        <option value="">-- Pilih --</option>
        <option>Client</option>
        <option>Customer Service</option>
        <option>NOC</option>
    </select><br>

    <label>ID Pelanggan:</label><input type="text" name="id_pelanggan" required><br>
    <label>Nama Pelanggan:</label><input type="text" name="nama_pelanggan" required><br>
    <label>Nama Teknisi:</label><input type="text" name="nama_teknisi" required><br>
    <label>Lokasi:</label><input type="text" name="lokasi" required><br>

    <label>Waktu Mulai:</label><input type="datetime-local" name="waktu_mulai" required><br>
    <label>Waktu Selesai:</label><input type="datetime-local" name="waktu_selesai" required><br>

    <label>Indikasi Case:</label><input type="text" name="indikasi_case"><br>
    <label>Action:</label><input type="text" name="action"><br>

    <label>Status Ticket:</label>
    <select name="status_ticket" required>
        <option value="">-- Pilih --</option>
        <option>Open</option>
        <option>Closed</option>
        <option>Pending</option>
        <option>Dalam proses</option>
        <option>Dalam monitoring</option>
        <option>Cancel</option>
    </select><br>

    <label>Catatan:</label><textarea name="catatan"></textarea><br>

    <button type="submit">Simpan</button>
</form>
</body>
</html>

