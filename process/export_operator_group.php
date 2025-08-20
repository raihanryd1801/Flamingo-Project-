<?php
include '../config/db.php';

// Set header untuk file CSV
header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=export_operator_group.csv");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

// Header CSV
fputcsv($output, ['ID', 'Operator Name', 'Jumlah Nomor']);

// Query data group operator + hitung jumlah nomor
$query = "
    SELECT o.id, o.name, COUNT(pn.id) as jumlah_nomor
    FROM operators o
    LEFT JOIN phone_numbers pn ON o.id = pn.operator_id
    GROUP BY o.id, o.name
    ORDER BY o.id ASC
";

$result = $koneksi->query($query);

// Loop hasil query
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['id'], $row['name'], $row['jumlah_nomor']]);
}

fclose($output);
exit();
?>

