
<?php
session_start();

// Konfigurasi database
$dbHost = '192.168.99.173';
$dbUser = 'flamingo';
$dbPass = 'fid1234';
$dbName = 'monitoring_db';

// License key yang ingin dicek
$licenseKey = 'b9f9272161782644b86dbf7b0e24f36d';

// Koneksi ke database
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Ambil data license dari DB
$stmt = $conn->prepare("SELECT status, expiry_date FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->bind_param("s", $licenseKey);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("License tidak ditemukan di database.");
}

$row = $result->fetch_assoc();
$status = $row['status'];
$expiryDate = $row['expiry_date'];

// Cek status dan expiry
$currentDate = date('Y-m-d');
if ($status !== 'active' || $expiryDate < $currentDate) {
    die("License tidak valid atau sudah expired.");
}

// License valid, simpan session
$_SESSION['license_valid'] = true;
