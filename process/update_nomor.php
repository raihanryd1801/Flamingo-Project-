<?php
include '../config/db.php';

$operator_id = intval($_POST['operator_id']);
$phone_numbers = explode("\n", trim($_POST['phone_numbers']));
$release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;

$success = 0;
$duplicate = 0;

foreach ($phone_numbers as $number) {
    $number = trim($number);
    if ($number == '') continue;

    $check = $koneksi->query("SELECT id FROM phone_numbers WHERE phone_number = '$number'");
    if ($check->num_rows == 0) {
        $stmt = $koneksi->prepare("INSERT INTO phone_numbers (operator_id, phone_number, release_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $operator_id, $number, $release_date);
        $stmt->execute();
        $stmt->close();
        $success++;
    } else {
        $duplicate++;
    }
}

header("Location: ../numbermanagement/management_nomor.php?success=$success&duplicate=$duplicate");
exit();
?>
