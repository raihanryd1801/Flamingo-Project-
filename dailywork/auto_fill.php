<?php
require '../config/db.php';
require '../config/session.php';

// Contoh data otomatis (bisa diganti dengan query database)
$autoData = [
    'jenis_pekerjaan' => 'Maintenance',
    'request_by' => 'NOC',
    'id_pelanggan' => 'CUST'.rand(1000,9999),
    'nama_pelanggan' => 'Pelanggan Otomatis',
    'nama_teknisi' => $_SESSION['username'],
    'site' => 'SITE-'.rand(1,10),
    'lokasi' => 'Gedung '.rand(1,20).', Lantai '.rand(1,5),
    'indikasi_case' => 'Gangguan jaringan secara periodic',
    'action' => 'Perbaikan switch dan pengecekan kabel',
    'status_tiket' => 'Closed'
];

// Redirect ke form dengan data auto-fill
header("Location: dailyworkteknisi.php?".http_build_query($autoData));
exit;
