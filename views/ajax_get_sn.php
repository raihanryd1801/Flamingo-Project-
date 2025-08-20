<?php
require '../config/db.php';

if (isset($_GET['item_id'])) {
    $item_id = intval($_GET['item_id']);
    $snList = [];

    // SN baru dari sn_stock yang belum keluar
    $query1 = "
        SELECT sn FROM sn_stock 
        WHERE item_id = $item_id 
        AND sn NOT IN (SELECT sn FROM barang_keluar)
    ";
    $result1 = $koneksi->query($query1);
    while ($row = $result1->fetch_assoc()) {
        $snList[] = $row['sn'];
    }

    // SN dari barang_retur yang digunakan_ulang = 1
    // dan hanya jika retur dilakukan SETELAH pemakaian terakhir
    $query2 = "
        SELECT br.sn 
        FROM barang_retur br
        LEFT JOIN (
            SELECT sn, MAX(tanggal) AS last_keluar 
            FROM barang_keluar 
            GROUP BY sn
        ) bk ON br.sn = bk.sn
        WHERE br.item_id = $item_id 
        AND br.digunakan_ulang = 1
        AND (bk.last_keluar IS NULL OR br.tanggal > bk.last_keluar)
    ";
    $result2 = $koneksi->query($query2);
    while ($row = $result2->fetch_assoc()) {
        if (!in_array($row['sn'], $snList)) {
            $snList[] = $row['sn'];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($snList);
}
?>
