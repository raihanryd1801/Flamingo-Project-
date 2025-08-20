<?php
include '../config/db.php';

$query = "SELECT * FROM phone_numbers ORDER BY phone_number ASC";
$result = $koneksi->query($query);

header("Content-type: text/csv");
header("Content-Disposition: attachment
