<?php
include '../config/db.php';
session_start();

// Hak akses
if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['operator_id'])) {
        $operator_id = (int) $_POST['operator_id'];

        // Update semua nomor dengan operator_id yang dipilih
        $update = "
            UPDATE phone_numbers 
            SET terminate_status = 'done', terminate_date = NOW()
            WHERE operator_id = $operator_id AND terminate_status = 'proses' AND is_terminated = 1
        ";

        if ($koneksi->query($update)) {
            header('Location: ../numbermanagement/terminate_list.php?success=Semua nomor operator berhasil dipindahkan ke Done.');
        } else {
            header('Location: ../numbermanagement/terminate_list.php?error=Gagal memindahkan nomor operator.');
        }
        exit();
    } else {
        header('Location: ../numbermanagement/terminate_list.php?error=Operator tidak ditemukan.');
        exit();
    }
} else {
    header('Location: ../numbermanagement/terminate_list.php');
    exit();
}
?>

