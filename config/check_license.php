<?php
session_start();

// Konfigurasi database
require __DIR__ . '/db.php';

// Ambil license aktif (hanya 1 yg dipakai aplikasi)
$sql = "SELECT la.license_key, l.status, l.expiry_date, l.domain 
        FROM license_active la 
        JOIN licenses l ON la.license_key = l.license_key 
        LIMIT 1";
$result = $koneksi->query($sql);

if ($result->num_rows === 0) {
    // Tidak ada license aktif
    header("Location: config/invalid.html");
    exit;
}

$row         = $result->fetch_assoc();
$licenseKey  = $row['license_key'];
$status      = $row['status'];
$expiryDate  = $row['expiry_date'];
$licensedIP  = trim($row['domain']); // domain/IP yg sah

// Ambil IP server saat ini
$currentIP   = $_SERVER['SERVER_ADDR'];

// Tanggal sekarang & batas maksimal expiry
$currentDate = date('Y-m-d');
$maxDate     = date('Y-m-d', strtotime('+20 years'));

// ? Cek apakah expired
if ($expiryDate < $currentDate) {
    header("Location: config/expired.html");
    exit;
}

// ? Cek kondisi lain (invalid license)
if (
    $status !== 'active' ||
    $expiryDate > $maxDate ||                // tidak boleh lebih dari 20 tahun
    ($licensedIP !== '' && $licensedIP !== $currentIP)
) {
    header("Location: config/invalid.html");
    exit;
}

// Jika lolos validasi
$_SESSION['license_valid'] = true;
?>
