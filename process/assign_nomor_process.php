<?php
include '../config/db.php';

$id = intval($_POST['id']);
$prefix = $koneksi->real_escape_string($_POST['prefix']);
$client_name = $koneksi->real_escape_string($_POST['client_name']);

$query = "
    UPDATE phone_numbers 
    SET prefix = '$prefix', client_name = '$client_name'
    WHERE id = $id
";

if ($koneksi->query($query)) {
    header("Location: ../management_nomor.php");
} else {
    echo "Gagal update: " . $koneksi->error;
}
?>

