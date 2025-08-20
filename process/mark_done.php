<?php
include '../config/db.php';
session_start();

// Hak akses
if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['phone_number'])) {
        $phone_number = $koneksi->real_escape_string($_POST['phone_number']);

        // Update terminate_status jadi done dan isi terminate_date sekarang
        $update = "
            UPDATE phone_numbers 
            SET terminate_status = 'done', terminate_date = NOW()
            WHERE phone_number = '$phone_number' AND terminate_status = 'proses' AND is_terminated = 1
        ";

        if ($koneksi->query($update)) {
            header('Location: ../numbermanagement/terminate_list.php?success=Nomor berhasil dipindahkan ke Done.');
        } else {
            header('Location: ../numbermanagement/terminate_list.php?error=Gagal memindahkan nomor.');
        }
        exit();
    } else {
        header('Location: ../numbermanagement/terminate_list.php?error=Nomor tidak ditemukan.');
        exit();
    }
} else {
    header('Location: ../numbermanagement/terminate_list.php');
    exit();
}
?>
