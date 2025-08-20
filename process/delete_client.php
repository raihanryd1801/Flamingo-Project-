<?php
include '../config/db.php';
session_start();

// Debugging Mode
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/delete_errors.log');

// Validasi Session
if (!isset($_SESSION['user_id'])) {
    error_log("Unauthorized access attempt");
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

// Validasi Input
if (!isset($_GET['client']) || empty(trim($_GET['client']))) {
    error_log("Invalid client parameter");
    die(json_encode(['status' => 'error', 'message' => 'Invalid client']));
}

$clientName = $koneksi->real_escape_string(trim($_GET['client']));
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $koneksi->real_escape_string(trim($_GET['search'])) : '';

// Mulai Transaction
$koneksi->begin_transaction();

try {
    // DEBUG: Log sebelum eksekusi
    error_log("Attempting to delete client: $clientName");
    
    // OPTION 1: SOFT DELETE (Recommended)
    $query = "UPDATE phone_numbers 
              SET status = 'released',
                  client_name = NULL,
                  release_date = NOW()
              WHERE client_name = ?";
    
    // OPTION 2: HARD DELETE (Jika diperlukan)
    // $query = "DELETE FROM phone_numbers WHERE client_name = ?";
    
    $stmt = $koneksi->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $koneksi->error);
    }
    
    $stmt->bind_param("s", $clientName);
    $stmt->execute();
    
    // DEBUG: Cek affected rows
    $affected = $stmt->affected_rows;
    error_log("Affected rows: $affected");
    
    if ($affected === 0) {
        throw new Exception("No records matched client: $clientName");
    }
    
    $koneksi->commit();
    
    // DEBUG: Log sukses
    error_log("Successfully deleted client: $clientName");
    
    $_SESSION['success'] = "Client '$clientName' berhasil dihapus";
    
} catch (Exception $e) {
    $koneksi->rollback();
    error_log("Delete failed: " . $e->getMessage());
    $_SESSION['error'] = "Gagal menghapus: " . $e->getMessage();
}

// Redirect dengan parameter asli
$params = [];
if ($page > 1) $params[] = "page=$page";
if (!empty($search)) $params[] = "search=" . urlencode($search);

$redirectUrl = "../list_client.php";
if (!empty($params)) {
    $redirectUrl .= "?" . implode("&", $params);
}

// DEBUG: Log redirect URL
error_log("Redirecting to: $redirectUrl");

header("Location: $redirectUrl");
exit();
?>
