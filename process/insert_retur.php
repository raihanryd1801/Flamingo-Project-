<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

// Ambil data dari form
$tanggal       = $_POST['tanggal'];
$item_id       = intval($_POST['item_id']);
$qty           = intval($_POST['qty']);
$sn            = $_POST['sn'];
$kondisi       = $_POST['kondisi'];
$digunakan_ulang = ($kondisi === 'baik') ? 1 : 0;
$client_tujuan = $_POST['client_tujuan'] ?? null;

// Insert ke barang_retur
$stmt = $koneksi->prepare("INSERT INTO barang_retur (tanggal, item_id, qty, sn, kondisi, digunakan_ulang) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("siissi", $tanggal, $item_id, $qty, $sn, $kondisi, $digunakan_ulang);
$stmt->execute();
$stmt->close();

// Jika barang bisa digunakan ulang, tambahkan lagi ke sn_stock
if ($digunakan_ulang === 1) {
    // Cek dulu, apakah SN sudah ada di sn_stock
    $cek = $koneksi->prepare("SELECT COUNT(*) FROM sn_stock WHERE sn = ?");
    $cek->bind_param("s", $sn);
    $cek->execute();
    $cek->bind_result($count);
    $cek->fetch();
    $cek->close();

    if ($count == 0) {
        $stmt2 = $koneksi->prepare("INSERT INTO sn_stock (item_id, sn) VALUES (?, ?)");
        $stmt2->bind_param("is", $item_id, $sn);
        $stmt2->execute();
        $stmt2->close();
    }
}

header("Location: ../views/barang_retur.php");
exit;
