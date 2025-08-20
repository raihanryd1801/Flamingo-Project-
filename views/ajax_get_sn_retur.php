<?php
require '../config/db.php';

if (isset($_GET['item_id'])) {
    $item_id = intval($_GET['item_id']);

    $query = "
        SELECT sn 
        FROM barang_keluar 
        WHERE item_id = $item_id 
        AND sn NOT IN (SELECT sn FROM barang_retur)
        ORDER BY sn ASC
    ";

    $result = $koneksi->query($query);
    $snList = [];

    while ($row = $result->fetch_assoc()) {
        $snList[] = $row['sn'];
    }

    header('Content-Type: application/json');
    echo json_encode($snList);
}
