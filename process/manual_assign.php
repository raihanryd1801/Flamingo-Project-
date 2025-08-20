<?php
include '../config/db.php';

if (isset($_POST['selected_ids']) && count($_POST['selected_ids']) > 0) {
    $prefix = $koneksi->real_escape_string($_POST['prefix']);
    $client_name = $koneksi->real_escape_string($_POST['client_name']);
    $ids = array_map('intval', $_POST['selected_ids']);

    $id_list = implode(",", $ids);
    $sql = "UPDATE phone_numbers SET prefix='$prefix', client_name='$client_name' WHERE id IN ($id_list)";
    
    if ($koneksi->query($sql)) {
        header("Location: ../management_nomor.php?assign_success=1");
    } else {
        echo "Gagal assign: " . $koneksi->error;
    }
} else {
    echo "Tidak ada nomor yang dipilih.";
}
?>
