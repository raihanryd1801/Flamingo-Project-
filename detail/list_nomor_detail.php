<?php
include '../config/db.php';
session_start();

$operator_id = intval($_GET['operator_id']);
$prefix = $koneksi->real_escape_string($_GET['prefix']);
$client_name = $koneksi->real_escape_string($_GET['client']);

$operator = $koneksi->query("SELECT name FROM operators WHERE id = $operator_id")->fetch_assoc()['name'];

$query = "
    SELECT phone_number FROM phone_numbers 
    WHERE operator_id = $operator_id 
      AND prefix = '$prefix' 
      AND client_name = '$client_name'
    ORDER BY phone_number ASC
";
$result = $koneksi->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Nomor</title>
</head>
<body>

<h2>Detail Nomor</h2>

<p>Operator: <b><?= htmlspecialchars($operator) ?></b></p>
<p>Prefix: <b><?= htmlspecialchars($prefix) ?></b></p>
<p>Client: <b><?= htmlspecialchars($client_name) ?></b></p>

<table border="1">
    <tr><th>No</th><th>Nomor</th></tr>
    <?php 
    $no = 1;
    while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['phone_number']) ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<p><a href="../management_nomor.php">Kembali</a></p>

</body>
</html>
