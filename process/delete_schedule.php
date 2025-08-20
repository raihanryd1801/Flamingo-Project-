<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak ditemukan']);
    exit;
}

$id = intval($_GET['id']);
$db = new mysqli($host, $username, $password, $database);

if ($db->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi gagal']);
    exit;
}

$stmt = $db->prepare("DELETE FROM routing_schedules WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Jadwal berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus jadwal']);
}

$stmt->close();
$db->close();

