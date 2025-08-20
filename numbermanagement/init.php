<?php
// Koneksi ke database
require __DIR__ . '/config/db.php';

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Buat database
$sql = "CREATE DATABASE IF NOT EXISTS manajemen_nomor";
if ($conn->query($sql) === TRUE) {
    echo "Database berhasil dibuat<br>";
} else {
    echo "Error membuat database: " . $conn->error;
}

// Gunakan database
$conn->select_db("manajemen_nomor");

// Buat tabel phone_numbers
$sql = "CREATE TABLE IF NOT EXISTS phone_numbers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operator VARCHAR(50) NOT NULL,
    number VARCHAR(20) NOT NULL,
    prefix VARCHAR(10),
    client VARCHAR(100),
    UNIQUE KEY unique_number (number)
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabel phone_numbers berhasil dibuat<br>";
} else {
    echo "Error membuat tabel: " . $conn->error;
}

// Contoh data awal untuk Indosat
$numbers = [
    '622131117900', '622131117901', '622131117902', '622131117903', '622131117904',
    '622131117905', '622131117906', '622131117907', '622131117908', '622131117909',
    '622131117910', '622131117911', '622131117912', '622131117913', '622131117914',
    '622131117915', '622131117916', '622131117917', '622131117918', '622131117919'
];

foreach ($numbers as $number) {
    $sql = "INSERT IGNORE INTO phone_numbers (operator, number) VALUES ('Indosat', '$number')";
    $conn->query($sql);
}

echo "Data awal berhasil dimasukkan";

$conn->close();
?>
