<?php
include '../config/db.php';

$id = intval($_GET['id']);
$stmt = $koneksi->prepare("DELETE FROM phone_numbers WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: ../numbermanagement/management_nomor.php");
exit;
?>
