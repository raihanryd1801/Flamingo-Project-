<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require(__DIR__ . '/../config/db.php');

// Mengatur Role
if (!isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'noc_internet' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator')) {
    header('Location: ../unauthorized.php');
    exit;
}

// Ambil filter lokasi dan keyword
$filter_id = isset($_GET['router_id']) ? intval($_GET['router_id']) : 0;
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';

// Peta lokasi berdasarkan router_id
$lokasi_map = [
    0 => 'Semua Lokasi',
    1 => 'Kebalen',
    2 => 'Karawang',
    3 => 'Karawang Timur',
    4 => 'Podomoro',
    5 => 'Lapalma',
    6 => 'RajaWifi Cyber Site 25 & 24'
];

$lokasi = $lokasi_map[$filter_id] ?? 'Tidak Dikenal';

// Buat query dinamis
$where = [];
$params = [];
$types = '';

if ($filter_id !== 0) {
    $where[] = 'router_id = ?';
    $params[] = $filter_id;
    $types .= 'i';
}

if (!empty($search)) {
    $where[] = "(username LIKE ? OR comment LIKE ? OR ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$sql = "SELECT * FROM pelanggan";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY comment ASC";

$stmt = $koneksi->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$aktif = $tidak_aktif = 0;
$baru_disconnect = [];

// New improved formatUptime function that handles different formats
function formatUptime($uptime) {
    // If already in formatted string like "1w2d3h4m5s", return as is
    if (is_string($uptime) && preg_match('/^(\d+w)?(\d+d)?(\d+h)?(\d+m)?(\d+s)?$/', $uptime)) {
        return $uptime;
    }
    
    // If empty or not numeric, return dash
    if (empty($uptime) || (!is_numeric($uptime) && !is_string($uptime))) {
        return '-';
    }
    
    // Try to parse MikroTik-style uptime strings if present
    if (is_string($uptime)) {
        // Format like "5w6d12h34m56s"
        if (preg_match('/^(\d+w)?(\d+d)?(\d+h)?(\d+m)?(\d+s)?$/', $uptime)) {
            return $uptime;
        }
        // Format like "1w2d3h4m5s"
        elseif (preg_match('/^(\d+)w(\d+)d(\d+)h(\d+)m(\d+)s$/', $uptime)) {
            return $uptime;
        }
    }
    
    // Convert to integer if it's a numeric string
    $seconds = is_numeric($uptime) ? (int)$uptime : 0;
    
    // Handle negative values
    if ($seconds < 0) {
        return '-';
    }
    
    // Convert seconds to weeks/days/hours/minutes/seconds
    $weeks = floor($seconds / 604800);
    $seconds %= 604800;
    $days = floor($seconds / 86400);
    $seconds %= 86400;
    $hours = floor($seconds / 3600);
    $seconds %= 3600;
    $minutes = floor($seconds / 60);
    $seconds %= 60;
    
    $result = [];
    if ($weeks > 0) $result[] = $weeks . 'w';
    if ($days > 0) $result[] = $days . 'd';
    if ($hours > 0) $result[] = $hours . 'h';
    if ($minutes > 0) $result[] = $minutes . 'm';
    if ($seconds > 0 || empty($result)) $result[] = $seconds . 's';
    
    return implode('', $result);
}

// Modify the data processing loop
while ($row = $result->fetch_assoc()) {
    // Handle uptime value properly
    $row['formatted_uptime'] = formatUptime($row['uptime']);
    $row['formatted_updated_at'] = $row['updated_at'] ? date('Y-m-d H:i:s', strtotime($row['updated_at'])) : '-';
    
    $data[] = $row;
    if ($row['status'] === 'aktif') {
        $aktif++;
    } else {
        $tidak_aktif++;
        if ($row['last_status'] === 'aktif') {
            $baru_disconnect[] = $row;
        }
    }
}
$total = count($data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring PPPoE</title>
    <meta http-equiv="refresh" content="15">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="../css/routermonit.css" rel="stylesheet">
    <style>
        /* Additional inline styles for table perfection */
        .table-fixed {
            table-layout: fixed;
            width: 100%;
        }
        .table-column-uptime {
            width: 120px;
            font-family: 'Roboto Mono', monospace;
        }
        .table-column-lastupdate {
            width: 160px;
            font-family: 'Roboto Mono', monospace;
        }
        .table-align-center {
            text-align: center;
        }
        .mono-font {
            font-family: 'Roboto Mono', monospace;
        }
        .table-responsive-container {
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .table-zebra tbody tr:nth-child(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        .table-cell-padding {
            padding: 12px 8px !important;
        }
        .text-ellipsis {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-router"></i> Dashboard Monitoring Pelanggan</h2>
                <p>Lokasi: <?= $lokasi ?></p>
            </div>
            <a href="/index.php" class="back-button">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <form class="row mb-4" method="get">
        <div class="col-md-3">
            <select name="router_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($lokasi_map as $id => $name): ?>
                    <option value="<?= $id ?>" <?= $filter_id == $id ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <input type="text" name="search" class="form-control" placeholder="Cari username / comment / IP..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Cari</button>
        </div>
    </form>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h5 class="card-title">Total Pelanggan</h5>
                    <p class="card-text"><?= $total ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h5 class="card-title">Aktif</h5>
                    <p class="card-text"><?= $aktif ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h5 class="card-title">Tidak Aktif</h5>
                    <p class="card-text"><?= $tidak_aktif ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($baru_disconnect) > 0): ?>
    <div class="alert alert-danger">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><strong><?= $lokasi ?>:</strong> <?= count($baru_disconnect) ?> pelanggan baru saja disconnect</div>
        </div>
        <ul class="mb-0 ps-4">
            <?php foreach ($baru_disconnect as $user): ?>
                <li>
                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                    (<?= htmlspecialchars($user['comment']) ?>)
                    <span class="text-muted">[<?= $lokasi_map[$user['router_id']] ?? 'Tidak Diketahui' ?>]</span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="table-responsive-container">
        <table class="table table-fixed table-zebra">
            <thead>
                <tr>
                    <th class="table-align-center">No</th>
                    <th>ID Pelanggan</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th class="table-align-center">Status</th>
                    <th class="table-align-center">IP</th>
                    <th class="table-align-center table-column-uptime">UPTIME</th>
                    <th class="table-align-center table-column-lastupdate">LAST UPDATE</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($data as $row): ?>
                    <?php
                        $badge = $row['status'] === 'aktif'
                            ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> AKTIF</span>'
                            : '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> TIDAK AKTIF</span>';
                    ?>
                    <tr <?= ($row['status'] === 'tidak aktif' && $row['last_status'] === 'aktif') ? 'class="bg-danger bg-opacity-10"' : '' ?>>
                        <td class="table-align-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['comment']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['password']) ?></td>
                        <td class="table-align-center"><?= $badge ?></td>
                        <td class="table-align-center mono-font"><?= $row['ip_address'] ?? '-' ?></td>
                        <td class="table-align-center mono-font"><?= $row['formatted_uptime'] ?></td>
                        <td class="table-align-center mono-font"><?= $row['formatted_updated_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="refresh-info"><i class="bi bi-clock-history"></i> Auto-refresh setiap 15 detik - Terakhir update: <?= date('H:i:s') ?></p>
</div>
</body>
</html>