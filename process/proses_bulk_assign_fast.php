<?php
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $operator_id = intval($_POST['operator_id']);
    $prefix = $koneksi->real_escape_string($_POST['prefix']);
    $client_name = $koneksi->real_escape_string($_POST['client_name']);
    $phone_numbers_raw = $_POST['phone_numbers'];

    // Proses inputan
    $lines = preg_split('/\r\n|\r|\n/', trim($phone_numbers_raw));
    $lines = array_filter(array_map('trim', $lines)); // hapus baris kosong

    $assigned = 0;
    $skipped = 0;

    foreach ($lines as $number) {
        $number = $koneksi->real_escape_string($number);

        // Validasi apakah nomor masih kosong
        $check = $koneksi->query("
            SELECT id FROM phone_numbers 
            WHERE operator_id = $operator_id 
            AND phone_number = '$number'
            AND (prefix = '' OR prefix IS NULL)
            AND (client_name = '' OR client_name IS NULL)
            LIMIT 1
        ");

        if ($check->num_rows > 0) {
            $row = $check->fetch_assoc();
            $id = intval($row['id']);

            $koneksi->query("
                UPDATE phone_numbers 
                SET prefix = '$prefix', client_name = '$client_name' 
                WHERE id = $id
            ");
            $assigned++;
        } else {
            $skipped++;
        }
    }

    // Redirect balik dengan summary
    header("Location: ../bulk_assign.php?operator_id=$operator_id&assigned=$assigned&skipped=$skipped");
    exit();
}
?>

