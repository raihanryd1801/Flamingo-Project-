<?php
include '../config/db.php';
session_start();
include '../helper/history_helper.php'; // Tambahkan ini

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Validate POST input
if (empty($_POST['client_name']) || !isset($_POST['prefix'])) {
    header("Location: ../numbermanagement/list_client.php?error=invalid");
    exit();
}

$client_name = $koneksi->real_escape_string($_POST['client_name']);
$prefix = $koneksi->real_escape_string($_POST['prefix']);

// Mulai soft delete
$sql = "
    UPDATE phone_numbers
    SET client_name = '', prefix = ''
    WHERE client_name = '$client_name' AND prefix = '$prefix'
";

if ($koneksi->query($sql)) {
    // Tambahkan log history
    logHistory($koneksi, 'SOFT DELETE', "User menghapus client: $client_name dengan prefix: $prefix");

    header("Location: ../numbermanagement/list_client.php?success=deleted");
} else {
    header("Location: ../numbermanagement/list_client.php?error=failed");
}
?>
