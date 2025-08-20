<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}
// Mengatur hak akses  
if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

$servers = [
    "Server NFS-13-Dankom" => "http://192.168.13.34/diskstatus.php",
    "Server Mega Kota" => "http://172.16.16.139/monitor/diskstatus.php",
    "Server Mega Semarang" => "http://172.16.16.27/monitor/diskstatus.php",
    "Server Mega Makassar" => "http://172.16.16.131/monitoring/diskstatus.php",
    "Server Mega Medan Recovery" => "http://172.16.16.19/monitor/diskstatus.php",
    "Server Mega Kuningan Collection" => "http://172.16.16.115/monitor/diskstatus.php",
    "Server Mega Kuningan RMU" => "http://172.16.16.119/monitor/diskstatus.php",
    "Server Mega Kuningan Recovery" => "http://172.16.16.117/monitor/diskstatus.php",
    // Tambahkan server Monitor lainnya di sini
];

// Multi cURL setup
$multiHandle = curl_multi_init();
$curlHandles = [];
$results = [];

foreach ($servers as $name => $url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);
    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[$name] = $ch;
}

$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

foreach ($curlHandles as $name => $ch) {
    $content = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $results[$name] = ($httpCode == 200 && $content) ? json_decode($content, true) : false;
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Storage Server</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9fafc; padding: 20px; }
        h1 { text-align: center; color: #2c3e50; }
        .search-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .search-box {
            width: 300px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        label {
            display: inline-block;
            margin-top: 10px;
            font-size: 15px;
        }
        .server {
            background: white;
            border-radius: 10px;
            margin-bottom: 25px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .server:hover {
            transform: scale(1.01);
        }
        .server-title {
            margin: 0 0 10px;
            font-size: 20px;
            color: #34495e;
        }
        .badge {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            font-size: 12px;
            padding: 3px 7px;
            border-radius: 12px;
            margin-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background: #3498db;
            color: white;
            padding: 10px;
        }
        td {
            padding: 10px;
            text-align: center;
        }
        tr:nth-child(even) { background: #f2f2f2; }
        tr.warning { background-color: #ffe6e6; }
        .error {
            color: red;
            font-weight: bold;
        }
        .notification {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 16px;
            display: none;
            z-index: 1000;
        }
    </style>
    <script>
        setTimeout(() => location.reload(), 20000);

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.classList.add('notification');
            notification.textContent = message;
            document.body.appendChild(notification);
            notification.style.display = 'block';

            setTimeout(() => {
                notification.style.display = 'none';
                document.body.removeChild(notification);
            }, 5000);
        }

        function filterServers() {
            const search = document.getElementById("search").value.toLowerCase();
            const filterHigh = document.getElementById("filterHigh").checked;
            const servers = document.querySelectorAll(".server");

            servers.forEach(server => {
                const name = server.querySelector(".server-title").textContent.toLowerCase();
                const highUsage = server.querySelectorAll("tr.warning").length > 0;
                let show = name.includes(search);
                if (filterHigh) {
                    show = show && highUsage;
                }
                server.style.display = show ? "block" : "none";
            });
        }
    </script>
</head>
<body>
    <div style="position: fixed; top: 16px; left: 16px; z-index: 1000;">
        <a href="/index.php" style="display: inline-flex; align-items: center; padding: 6px 10px; background: linear-gradient(to right, #0ea5e9, #60a5fa); color: white; font-size: 14px; border-radius: 6px; text-decoration: none; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
            <svg style="width: 16px; height: 16px; margin-right: 6px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
            </svg>
            Kembali ke menu
        </a>
    </div>

    <h1>Monitoring Storage Server</h1>

    <div class="search-container">
        <input type="text" id="search" class="search-box" onkeyup="filterServers()" placeholder="Cari server...">
        <br>
        <label><input type="checkbox" id="filterHigh" onchange="filterServers()"> Tampilkan hanya yang penggunaan disk &ge; 80%</label>
    </div>

    <?php
    foreach ($servers as $name => $url) {
        echo "<div class='server'>";
        echo "<h2 class='server-title'>$name";
        $result = $results[$name];
        if (!$result) {
            echo "</h2><p class='error'>DOWN - Tidak dapat mengakses $url</p>";
            echo "<script>showNotification('$name DOWN - Tidak dapat mengakses $url');</script>";
            echo "</div>";
            continue;
        }

        $hasWarning = false;
        foreach ($result['data'] as $row) {
            if (intval(str_replace('%', '', $row['use_perc'])) >= 80) {
                $hasWarning = true;
                break;
            }
        }

        if ($hasWarning) {
            echo "<span class='badge'>Disk > 80%</span>";
        }
        echo "</h2>";
        echo "<p> Last update: <strong>{$result['time']}</strong></p>";
        echo "<table><tr><th>Filesystem</th><th>Size</th><th>Used</th><th>Available</th><th>Use%</th><th>Mounted On</th></tr>";

        foreach ($result['data'] as $row) {
            $usage = intval(str_replace('%', '', $row['use_perc']));
            $class = $usage >= 80 ? "class='warning'" : "";
            echo "<tr $class>";
            echo "<td>{$row['filesystem']}</td><td>{$row['size']}</td><td>{$row['used']}</td><td>{$row['avail']}</td><td>{$row['use_perc']}</td><td>{$row['mounted_on']}</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";
    }
    ?>

</body>
</html>
