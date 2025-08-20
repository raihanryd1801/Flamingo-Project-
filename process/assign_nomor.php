<?php
include '../config/db.php';
session_start();
include '../helper/history_helper.php'; // Tambahkan ini

$operator_id = intval($_POST['operator_id']);
$prefix = trim($_POST['prefix']);
$client_name = trim($_POST['client_name']);
$jumlah = intval($_POST['jumlah']);
$manual_numbers = trim($_POST['manual_numbers']);

if ($prefix == '' || $client_name == '') {
    die("Prefix & Client wajib diisi.");
}

$assigned_ids = [];

if (!empty($manual_numbers)) {
    // Mode manual list nomor
    $manual_list = explode("\n", $manual_numbers);
    $manual_list = array_map('trim', $manual_list);
    $manual_list = array_filter($manual_list);

    foreach ($manual_list as $number) {
        // Cari ID berdasarkan nomor yg diinput manual
        $result = $koneksi->query("
            SELECT id FROM phone_numbers 
            WHERE phone_number = '{$koneksi->real_escape_string($number)}'
              AND operator_id = $operator_id 
              AND (prefix = '' OR prefix IS NULL)
              AND (client_name = '' OR client_name IS NULL)
            LIMIT 1
        ");
        if ($row = $result->fetch_assoc()) {
            $assigned_ids[] = $row['id'];
        }
    }
} elseif ($jumlah > 0) {
    // Mode jumlah otomatis
    $result = $koneksi->query("
        SELECT id FROM phone_numbers 
        WHERE operator_id = $operator_id 
          AND (prefix = '' OR prefix IS NULL)
          AND (client_name = '' OR client_name IS NULL)
        ORDER BY phone_number ASC
        LIMIT $jumlah
    ");
    while ($row = $result->fetch_assoc()) {
        $assigned_ids[] = $row['id'];
    }
} else {
    die("Silakan isi jumlah atau list nomor.");
}

if (empty($assigned_ids)) {
    die("Tidak ada nomor yang bisa di-assign.");
}

// Proses update assign
$id_list = implode(",", array_map('intval', $assigned_ids));
$update = $koneksi->query("
    UPDATE phone_numbers 
    SET prefix = '{$koneksi->real_escape_string($prefix)}', 
        client_name = '{$koneksi->real_escape_string($client_name)}'
    WHERE id IN ($id_list)
");

// Simpan hasil assign ke session
$_SESSION['assigned_ids'] = $assigned_ids;

// Tambahkan Log History
if ($update) {
    $jumlahAssign = count($assigned_ids);
    logHistory($koneksi, 'ASSIGN', "Assign $jumlahAssign nomor ke client $client_name dengan prefix $prefix");
}

header("Location: assign_result.php");
exit();
?>
