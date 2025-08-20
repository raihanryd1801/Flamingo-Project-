<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $operator_id = intval($_POST['operator_id']);
    $prefix = $koneksi->real_escape_string(trim($_POST['prefix']));
    $client_name = $koneksi->real_escape_string(trim($_POST['client_name']));
    $jumlah = intval($_POST['jumlah']);

    // Ambil N nomor kosong untuk operator tersebut
    $result = $koneksi->query("
        SELECT id FROM phone_numbers 
        WHERE operator_id = $operator_id 
        AND (prefix = '' OR prefix IS NULL) 
        AND (client_name = '' OR client_name IS NULL)
        ORDER BY phone_number ASC 
        LIMIT $jumlah
    ");

    if (!$result) {
        die("Error ambil data: " . $koneksi->error);
    }

    $updated = 0;
    while ($row = $result->fetch_assoc()) {
        $id = intval($row['id']);
        $update = $koneksi->query("
            UPDATE phone_numbers 
            SET prefix = '$prefix', client_name = '$client_name' 
            WHERE id = $id
        ");

        if ($update) {
            $updated++;
        }
    }

    header("Location: ../management_nomor.php?assign_success=$updated");
    exit();
} else {
    die("Metode tidak diizinkan.");
}
?>

