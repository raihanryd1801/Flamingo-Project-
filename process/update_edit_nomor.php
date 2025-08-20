<?php
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $operator_id = intval($_POST['operator_id']);
    $phone_number = $koneksi->real_escape_string($_POST['phone_number']);
    $prefix = $koneksi->real_escape_string($_POST['prefix']);
    $client_name = $koneksi->real_escape_string($_POST['client_name']);

    $query = "
        UPDATE phone_numbers 
        SET operator_id = $operator_id, phone_number = '$phone_number', prefix = '$prefix', client_name = '$client_name'
        WHERE id = $id
    ";

    if ($koneksi->query($query)) {
        header("Location: ../numbermanagement/management_nomor.php");
        exit();
    } else {
        echo "Gagal update: " . $koneksi->error;
    }
}
?>

