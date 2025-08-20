<?php
include '../config/db.php';
session_start();

// Hak akses
if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recover Per Nomor
    if (isset($_POST['phone_number'])) {
        $phone_number = $koneksi->real_escape_string($_POST['phone_number']);

        $update = "
            UPDATE phone_numbers 
            SET is_terminated = 0, terminate_status = NULL, terminate_date = NULL 
            WHERE phone_number = '$phone_number'
        ";

        if ($koneksi->query($update)) {
            header('Location: ../numbermanagement/terminate_list.php?success=Nomor berhasil dikembalikan.');
        } else {
            header('Location: ../numbermanagement/terminate_list.php?error=Gagal mengembalikan nomor.');
        }
        exit();
    }

    // Recover All Per Operator Berdasarkan Status
    if (isset($_POST['operator_id']) && isset($_POST['status'])) {
        $operator_id = (int) $_POST['operator_id'];
        $status = $koneksi->real_escape_string($_POST['status']);

        $update = "
            UPDATE phone_numbers 
            SET is_terminated = 0, terminate_status = NULL, terminate_date = NULL 
            WHERE operator_id = $operator_id AND terminate_status = '$status' AND is_terminated = 1
        ";

        if ($koneksi->query($update)) {
            header('Location: ../numbermanagement/terminate_list.php?success=Semua nomor operator berhasil dikembalikan.');
        } else {
            header('Location: ../numbermanagement/terminate_list.php?error=Gagal mengembalikan semua nomor.');
        }
        exit();
    }

    // Jika data tidak lengkap
    header('Location: ../numbermanagement/terminate_list.php?error=Data tidak lengkap.');
    exit();
} else {
    header('Location: ../numbermanagement/terminate_list.php');
    exit();
}
?>
