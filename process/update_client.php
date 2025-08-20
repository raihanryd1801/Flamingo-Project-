<?php
include '../config/db.php';
session_start();
include '../helper/history_helper.php'; // Fungsi logHistory

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$oldClientName = $_POST['old_client_name'];
$oldPrefix = $_POST['old_prefix'];
$newClientName = $_POST['new_client_name'];
$newPrefix = $_POST['new_prefix'];
$operatorId = $_POST['operator_id'];

// Update data client
$stmt = $koneksi->prepare("UPDATE phone_numbers SET client_name = ?, prefix = ?, operator_id = ? WHERE client_name = ? AND prefix = ?");
$stmt->bind_param("sssss", $newClientName, $newPrefix, $operatorId, $oldClientName, $oldPrefix);

if ($stmt->execute()) {
    // Catat history
    logHistory($koneksi, 'UPDATE', "Mengubah client dari $oldClientName ($oldPrefix) menjadi $newClientName ($newPrefix)");

    header("Location: ../numbermanagement/list_client.php?success=updated");
} else {
    header("Location: ../numbermanagement/list_client.php?error=1");
}
$stmt->close();
?>
