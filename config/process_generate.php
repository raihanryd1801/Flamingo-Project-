<?php
require 'vos_server.php';

// Set header JSON
header('Content-Type: application/json');

// Fungsi untuk menghapus cache
function clearCache($server) {
    $cacheFile = __DIR__.'/../cache/routing_'.$server.'.json';
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
}

try {
    // Ambil data dari POST
    $server = 'vos1';
    $routing_id = $_POST['routing_id'] ?? '';
    $nomorBaru = $_POST['nomor'] ?? '';
    
    // Validasi input
    if (empty($routing_id) || empty($nomorBaru)) {
        throw new Exception("Routing ID dan nomor baru harus diisi.");
    }
    
    // Validasi format nomor (opsional)
    //if (!preg_match('/^[0-9]+$/', $nomorBaru)) {
   //     throw new Exception("Nomor hanya boleh mengandung angka.");
  //  }
    
    // Koneksi database
    global $vos_servers;
    $cfg = $vos_servers[$server];
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], 'vos3000db');
    
    if ($conn->connect_error) {
        throw new Exception("Koneksi database gagal: " . $conn->connect_error);
    }
    
    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("UPDATE e_gatewayroutingsetting SET rewriterulesincaller = ? WHERE gatewayrouting_id = ?");
    $stmt->bind_param('si', $nomorBaru, $routing_id);
    
    if ($stmt->execute()) {
        // Hapus cache setelah update berhasil
        clearCache($server);
        
        echo json_encode([
            'success' => true,
            'message' => "Nomor routing berhasil diubah ke: $nomorBaru"
        ]);
    } else {
        throw new Exception("Gagal memperbarui database: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>