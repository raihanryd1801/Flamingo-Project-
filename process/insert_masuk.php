<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin_it') {
    header('Location: ../unauthorized.php');
    exit;
}

require '../config/db.php';

// Ambil input
$tanggal    = $_POST['tanggal'];
$item_id    = intval($_POST['item_id']);
$qty        = intval($_POST['qty']);
$keterangan = $_POST['keterangan'] ?? '';

// Ambil kategori_sn dari items
$query = $koneksi->prepare("SELECT kategori_sn FROM items WHERE id = ?");
$query->bind_param("i", $item_id);
$query->execute();
$query->bind_result($kategori);
$query->fetch();
$query->close();

if (!$kategori) {
    $kategori = 'OTH';
}

// Format dasar SN prefix: DMA.AS140007.0625.SVR
$now     = new DateTime();
$bulan   = $now->format('m');
$tahun   = $now->format('y');
$prefix  = "DMA.AS140007.{$bulan}{$tahun}.{$kategori}";

// Cari urutan terakhir SN dengan prefix yang sama
$latest = $koneksi->prepare("SELECT sn FROM sn_stock WHERE sn LIKE CONCAT(?, '-%') ORDER BY sn DESC LIMIT 1");
$likePrefix = $prefix;
$latest->bind_param("s", $likePrefix);
$latest->execute();
$latest->bind_result($last_sn);
$latest->fetch();
$latest->close();

$startUrut = 1;
if ($last_sn) {
    // Ambil urutan angka terakhir dari SN terakhir, misalnya DMA.AS140007.0625.SVR-015
    $parts = explode('-', $last_sn);
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $startUrut = (int)$parts[1] + 1;
    }
}

// Simpan SN pertama sebagai dummy_sn untuk dicatat di barang_masuk
$dummy_sn = $prefix . '-' . str_pad($startUrut, 3, '0', STR_PAD_LEFT);
$stmtMasuk = $koneksi->prepare("INSERT INTO barang_masuk (tanggal, item_id, qty, sn, keterangan) VALUES (?, ?, ?, ?, ?)");
$stmtMasuk->bind_param("ssiis", $tanggal, $item_id, $qty, $dummy_sn, $keterangan);
$stmtMasuk->execute();
$stmtMasuk->close();

// Masukkan seluruh SN ke sn_stock
for ($i = 0; $i < $qty; $i++) {
    $urut = str_pad((string)($startUrut + $i), 3, '0', STR_PAD_LEFT);
    $final_sn = $prefix . '-' . $urut;

    $stmtSN = $koneksi->prepare("INSERT INTO sn_stock (item_id, sn) VALUES (?, ?)");
    $stmtSN->bind_param("is", $item_id, $final_sn);
    $stmtSN->execute();
    $stmtSN->close();
}

// Update stok barang
$stmtUpdate = $koneksi->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
$stmtUpdate->bind_param("ii", $qty, $item_id);
$stmtUpdate->execute();
$stmtUpdate->close();

header("Location: ../views/barang_masuk.php");
exit;
