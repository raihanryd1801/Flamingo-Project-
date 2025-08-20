<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    $stmt = $koneksi->prepare("DELETE FROM dailywork WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Data berhasil dihapus";
    } else {
        $_SESSION['error'] = "Gagal menghapus data: " . $stmt->error;
    }
    
    $stmt->close();
    $koneksi->close();
    
    header("Location: laporan.php");
    exit;
}
?>
