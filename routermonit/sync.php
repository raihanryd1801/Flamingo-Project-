<?php
// Aktifkan error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('/var/www/html/monitoring/config/db.php');
require_once('/var/www/html/monitoring/routeros-api/routeros_api.class.php');

$logFile = __DIR__ . '/../logs/sync.log';
$lastSync = file_exists($logFile) ? filemtime($logFile) : 0;
$now = time();

// Minimal 10 detik sekali
if ($now - $lastSync < 10) {
    echo "? Skip: Sync terlalu cepat.\n";
    exit;
}

// Daftar router
$routers = [
    [ 'id' => 1, 'ip' => '103.148.196.130', 'user' => 'noc@aqsaa.id', 'pass' => 'noc@aqsaa.id' ],
    [ 'id' => 2, 'ip' => '103.148.197.14',  'user' => 'noc@aqsaa.id', 'pass' => 'noc@aqsaa.id' ],
    [ 'id' => 3, 'ip' => '103.148.196.14',  'user' => 'noc@aqsaa.id', 'pass' => 'noc@aqsaa.id' ],
    [ 'id' => 4, 'ip' => '103.148.196.139', 'user' => 'noc@aqsaa.id', 'pass' => 'noc@aqsaa.id' ],
    [ 'id' => 5, 'ip' => '103.148.197.113', 'user' => 'Herdy', 'pass' => 'Roswaty909', 'port' => 7729 ],   
    [ 'id' => 6, 'ip' => '103.148.196.154', 'user' => 'noc@aqsaa.id', 'pass' => 'noc@aqsaa.id' ]
];

foreach ($routers as $router) {
    $API = new RouterosAPI();
    $API->debug = false;

    $portsToTry = isset($router['port']) ? [ $router['port'] ] : [ 8728, 8729 ];
    $connected = false;
    foreach ($portsToTry as $port) {
        if ($API->connect($router['ip'], $router['user'], $router['pass'], $port)) {
            echo "? Router {$router['ip']} terkoneksi di port {$port}\n";
            $connected = true;
            break;
        }
    }

    if (!$connected) {
        $error = "? Semua port gagal konek ke router {$router['ip']} pada " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($logFile, $error, FILE_APPEND);
        echo $error;
        continue;
    }

    // Ambil data PPP
    $ppp_active = $API->comm('/ppp/active/print');
    $ppp_secret = $API->comm('/ppp/secret/print');

    // Map user aktif
    $active_map = [];
    foreach ($ppp_active as $ppp) {
        $active_map[$ppp['name']] = [
            'ip'     => $ppp['address'],
            'uptime' => $ppp['uptime']
        ];
    }

    $current_users = [];

    foreach ($ppp_secret as $secret) {
        $username    = $secret['name'];
        $comment     = $secret['comment'] ?? '-';
        $password    = $secret['password'] ?? '-';
        $is_active   = isset($active_map[$username]);
        $status      = $is_active ? 'aktif' : 'tidak aktif';
        $ip          = $is_active ? $active_map[$username]['ip'] : null;
        $uptime      = $is_active ? $active_map[$username]['uptime'] : null;
        $last_update = date('Y-m-d H:i:s');
        $router_id   = $router['id'];

        $current_users[] = $username;

        $sql = "
            INSERT INTO pelanggan (username, comment, password, status, last_status, ip_address, uptime, last_update, router_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                comment = VALUES(comment),
                password = VALUES(password),
                last_status = status,
                status = VALUES(status),
                ip_address = VALUES(ip_address),
                uptime = VALUES(uptime),
                last_update = VALUES(last_update)
        ";

        $stmt = $koneksi->prepare($sql);
        if (!$stmt) {
            $errMsg = "? Prepare failed (Router {$router['ip']}): " . $koneksi->error . "\n";
            file_put_contents($logFile, $errMsg, FILE_APPEND);
            continue;
        }

        $stmt->bind_param(
            "ssssssssi",
            $username,
            $comment,
            $password,
            $status,
            $status,
            $ip,
            $uptime,
            $last_update,
            $router_id
        );

        if (!$stmt->execute()) {
            file_put_contents($logFile, "? Execute failed (Router {$router['ip']}): " . $stmt->error . "\n", FILE_APPEND);
        }

        $stmt->close();
    }

    // Hapus user yang tidak ada lagi di router
    if (!empty($current_users)) {
        $placeholders = implode(',', array_fill(0, count($current_users), '?'));
        $sql_delete = "DELETE FROM pelanggan WHERE router_id = ? AND username NOT IN ($placeholders)";

        $stmt_del = $koneksi->prepare($sql_delete);
        if ($stmt_del) {
            $types = 'i' . str_repeat('s', count($current_users));
            $params = array_merge([$router['id']], $current_users);
            $stmt_del->bind_param($types, ...$params);
            $stmt_del->execute();
            $stmt_del->close();
        }
    } else {
        // Kalau router kosong, hapus semua user
        $koneksi->query("DELETE FROM pelanggan WHERE router_id = {$router['id']}");
    }

    $API->disconnect();
    echo "? Sync router {$router['ip']} selesai\n";
}

// Tulis waktu terakhir sync
file_put_contents($logFile, "? Sync: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
echo "? Semua sync selesai pada " . date('Y-m-d H:i:s') . "\n";
?>
