<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Load database config
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/vos_server.php';

// Connect to local database
$local_db = new mysqli($host, $username, $password, $database);
if ($local_db->connect_error) {
    die("Koneksi database lokal gagal: " . $local_db->connect_error);
}

// Ambil semua jadwal pending yang sudah waktunya
$query = "SELECT * FROM routing_schedules WHERE status = 'pending' AND schedule_time <= NOW()";
$result = $local_db->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedule_id = $row['id'];
        $server = $row['server'];
        $routing_id = $row['routing_id'];
        $new_number = $row['new_number'];
        $new_locktype = $row['routing_status']; // Gunakan nilai yang disimpan

        try {
            // Mulai transaksi di database lokal
            $local_db->begin_transaction();
            
            // Eksekusi perubahan di VOS (nomor dan locktype sekaligus)
            $success = updateRoutingAndLocktypeInVOS($server, $routing_id, $new_number, $new_locktype);

            if ($success) {
                // Update status di database lokal
                $stmt = $local_db->prepare("UPDATE routing_schedules SET status = 'completed' WHERE id = ?");
                $stmt->bind_param("i", $schedule_id);
                $stmt->execute();
                
                $local_db->commit();
                echo "Jadwal ID {$schedule_id} berhasil dieksekusi. Locktype: {$new_locktype}\n";
            } else {
                throw new Exception("Gagal update VOS");
            }
        } catch (Exception $e) {
            $local_db->rollback();
            
            // Tandai gagal
            $stmt = $local_db->prepare("UPDATE routing_schedules SET status = 'failed', error_message = ? WHERE id = ?");
            $error_msg = $e->getMessage();
            $stmt->bind_param("si", $error_msg, $schedule_id);
            $stmt->execute();
            
            echo "Jadwal ID {$schedule_id} gagal: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "Tidak ada jadwal yang perlu dieksekusi.\n";
}

$local_db->close();

/**
 * Update nomor routing dan locktype sekaligus di VOS server
 */
function updateRoutingAndLocktypeInVOS($server, $routing_id, $new_number, $new_locktype) {
    global $vos_servers;

    if (!isset($vos_servers[$server])) {
        throw new Exception("Konfigurasi server VOS tidak ditemukan");
    }

    $cfg = $vos_servers[$server];
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);

    if ($conn->connect_error) {
        throw new Exception("Gagal terkoneksi ke server VOS: " . $conn->connect_error);
    }

    try {
        // Mulai transaksi
        $conn->autocommit(false);
        
        // 1. Update nomor routing
        $stmt1 = $conn->prepare("UPDATE e_gatewayroutingsetting SET rewriterulesincaller = ? WHERE gatewayrouting_id = ?");
        if (!$stmt1) {
            throw new Exception("Gagal mempersiapkan query update nomor: " . $conn->error);
        }
        $stmt1->bind_param("si", $new_number, $routing_id);
        
        // 2. Update locktype
        $stmt2 = $conn->prepare("UPDATE e_gatewayrouting SET locktype = ? WHERE id = ?");
        if (!$stmt2) {
            throw new Exception("Gagal mempersiapkan query update locktype: " . $conn->error);
        }
        $stmt2->bind_param("ii", $new_locktype, $routing_id);
        
        // Eksekusi kedua query
        $success = $stmt1->execute() && $stmt2->execute();
        
        if ($success) {
            $conn->commit();
            return true;
        } else {
            $conn->rollback();
            throw new Exception("Gagal eksekusi update di VOS");
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    } finally {
        $conn->close();
    }
}