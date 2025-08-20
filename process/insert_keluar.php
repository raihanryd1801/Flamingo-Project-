<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

// Ambil data dari form
$tanggal    = $_POST['tanggal'];
$client     = $_POST['client'];
$item_id    = intval($_POST['item_id']);
$qty        = intval($_POST['qty']);
$keterangan = $_POST['keterangan'] ?? '';
$sn_list    = $_POST['sn'] ?? [];

// Validasi jumlah SN dan qty
if (count($sn_list) !== $qty) {
    die("? Jumlah SN yang dipilih tidak sama dengan Qty yang diinput.");
}

// Loop simpan SN ke barang_keluar
foreach ($sn_list as $sn) {
    $stmt = $koneksi->prepare("
        INSERT INTO barang_keluar (tanggal, client, item_id, qty, sn, keterangan)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssiiss", $tanggal, $client, $item_id, $qty, $sn, $keterangan);
    $stmt->execute();
    $stmt->close();

    // Coba hapus dari sn_stock (jika itu barang baru)
    $del = $koneksi->prepare("DELETE FROM sn_stock WHERE sn = ?");
    $del->bind_param("s", $sn);
    $del->execute();
    $del->close();
}

// Update stok barang
$update = $koneksi->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
$update->bind_param("ii", $qty, $item_id);
$update->execute();
$update->close();

header("Location: ../views/barang_keluar.php");
exit;
