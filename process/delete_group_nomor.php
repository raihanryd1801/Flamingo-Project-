<?php
include '../config/db.php';
session_start();

$operator_id = intval($_GET['operator_id']);
$prefix = $koneksi->real_escape_string($_GET['prefix']);
$client_name = $koneksi->real_escape_string($_GET['client']);

$query = "
    DELETE FROM phone_numbers 
    WHERE operator_id = $operator_id 
      AND prefix = '$prefix' 
      AND client_name = '$client_name'
";

if ($koneksi->query($query)) {
    header("Location: ../management_nomor.php?deleted=1");
} else {
    echo "Gagal menghapus data: " . $koneksi->error;
}
?>

