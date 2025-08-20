<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include '../helper/history_helper.php';
include '../config/db.php';

// Debug data POST
echo "<pre>POST Data:\n";
print_r($_POST);
echo "</pre>";

// Pastikan field required terisi
$required = ['jenis_pekerjaan', 'request_by', 'id_pelanggan', 'nama_pelanggan', 
             'nama_teknisi', 'lokasi', 'tanggal_mulai', 'waktu_mulai', 
             'waktu_selesai', 'status_tiket'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Field $field harus diisi");
    }
}

// Gabungkan tanggal dan waktu
$waktu_mulai = $_POST['tanggal_mulai'] . ' ' . $_POST['waktu_mulai'] . ':00';
$waktu_selesai = $_POST['tanggal_mulai'] . ' ' . $_POST['waktu_selesai'] . ':00';

// Hitung total waktu jika tidak ada input
if (empty($_POST['total_waktu'])) {
    $start = new DateTime($waktu_mulai);
    $end = new DateTime($waktu_selesai);
    $diff = $start->diff($end);
    $total_waktu = $diff->format('%H:%I:%S');
} else {
    $total_waktu = $_POST['total_waktu'];
}

// Siapkan data untuk binding
$bind_params = [
    $_POST['jenis_pekerjaan'],
    $_POST['request_by'],
    $_POST['id_pelanggan'],
    $_POST['nama_pelanggan'],
    $_POST['nama_teknisi'],
    $_POST['lokasi'],
    $_POST['site'] ?? null,
    $waktu_mulai,
    $waktu_selesai,
    $total_waktu,
    $_POST['indikasi_case'] ?? null,
    $_POST['action'] ?? null,
    $_POST['status_tiket'],
    $_POST['catatan'] ?? null,
    $_POST['tanggal_input'] ?? date('Y-m-d')
];

// Query SQL dengan kolom site
$sql = "INSERT INTO dailywork (
    jenis_pekerjaan, request_by, id_pelanggan, nama_pelanggan, nama_teknisi,
    lokasi, site, waktu_mulai, waktu_selesai, total_waktu,
    indikasi_case, action, status_tiket, catatan, tanggal_input
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Persiapkan statement
$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    die("Query gagal dipersiapkan: " . $koneksi->error);
}

// Binding parameter dengan cara yang benar
$types = str_repeat('s', count($bind_params));
$stmt->bind_param($types, ...$bind_params);

// Eksekusi
if ($stmt->execute()) {
    // Log History disimpan setelah berhasil eksekusi
    logHistory($koneksi, 'INSERT', "Menambahkan daily work untuk pelanggan {$_POST['nama_pelanggan']} dengan teknisi {$_POST['nama_teknisi']}");

    $_SESSION['success'] = "Data berhasil disimpan";
    header("Location: ../dailywork/laporan.php");
    exit;
} else {
    $_SESSION['error'] = "Gagal menyimpan data: " . $stmt->error;
    header("Location: ../dailywork/dailyworkteknisi.php");
    exit;
}

$stmt->close();
$koneksi->close();
?>
