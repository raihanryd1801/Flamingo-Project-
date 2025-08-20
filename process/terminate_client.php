<?php
include '../config/db.php';
session_start();
include '../helper/history_helper.php'; // ? Tambahkan ini untuk log

if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = $koneksi->real_escape_string($_POST['client_name'] ?? '');
    $prefix = $koneksi->real_escape_string($_POST['prefix'] ?? '');

    if ($clientName && $prefix) {
        $terminateStatus = 'proses';

        $updateSql = "UPDATE phone_numbers SET is_terminated = 1, terminate_status = ? WHERE client_name = ? AND prefix = ?";
        $stmt = $koneksi->prepare($updateSql);
        $stmt->bind_param('sss', $terminateStatus, $clientName, $prefix);

        if ($stmt->execute()) {
            // ? Catat ke history log
            logHistory($koneksi, 'TERMINATE', "Terminasi client $clientName dengan prefix $prefix");

            header('Location: ../numbermanagement/list_client.php?success=terminated');
        } else {
            header('Location: ../numbermanagement/list_client.php?error=terminate');
        }
        $stmt->close();
    } else {
        header('Location: ../numbermanagement/list_client.php?error=invalid');
    }
} else {
    header('Location: ../numbermanagement/list_client.php');
    exit;
}
?>
