<?php
// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mulai session
session_start();

// Validasi auth
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Validasi input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Invalid schedule ID']));
}

$schedule_id = (int)$_GET['id'];

// Koneksi database
require_once __DIR__ . '/../config/db.php';

try {
    $db = new mysqli($host, $username, $password, $database);
    if ($db->connect_error) {
        throw new Exception("Database connection failed: " . $db->connect_error);
    }

    // Update status jadwal
    $query = "UPDATE routing_schedules SET status = 'cancelled' WHERE id = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->error);
    }

    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();

    // Cek apakah ada row yang terpengaruh
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Jadwal berhasil dibatalkan',
            'schedule_id' => $schedule_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Jadwal tidak ditemukan atau sudah dibatalkan'
        ]);
    }
} catch (Exception $e) {
    error_log("Error cancelling schedule: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
} finally {
    if (isset($db)) $db->close();
}
?>
