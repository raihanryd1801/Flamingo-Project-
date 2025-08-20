<?php
include '../config/db.php';

$where = [];
if (!empty($_GET['operator_id'])) $where[] = "operator_id = " . intval($_GET['operator_id']);
if (!empty($_GET['prefix'])) $where[] = "prefix LIKE '%" . $koneksi->real_escape_string($_GET['prefix']) . "%'";
if (!empty($_GET['client'])) $where[] = "client_name LIKE '%" . $koneksi->real_escape_string($_GET['client']) . "%'";
$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT phone_number FROM phone_numbers $where_clause ORDER BY phone_number ASC";
$result = $koneksi->query($query);

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=export_nomor.csv");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");
fputcsv($output, ['Phone Number']);
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [$row['phone_number']]);
}
fclose($output);
exit();
?>
