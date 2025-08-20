<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    die('Unauthorized access');
}
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/vos_server.php';
$server_aliases = [
    'VOS1' => 'VOS NINO',
    'VOS2' => 'VOS CLIENT 10',
    'VOS3' => 'VOS CLIENT 71',
    'VOS4' => 'VOS OP ISAT MULTI 021',
];

function getAllSchedulesWithDetails() {
    global $host, $username, $password, $database;
    try {
        $db = new mysqli($host, $username, $password, $database);
        if ($db->connect_error) {
            throw new Exception("Koneksi gagal: " . $db->connect_error);
        }
        
        $query = "
            SELECT 
                rs.*, 
                u.username
            FROM routing_schedules rs
            LEFT JOIN users u ON rs.created_by = u.id
            ORDER BY rs.schedule_time DESC
        ";
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception("Query error: " . $db->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return [];
    } finally {
        if (isset($db)) $db->close();
    }
}

$schedules = getAllSchedulesWithDetails();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Semua Jadwal Routing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/daftar_routing.css">
    <style>
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        .badge-pending {
            background-color: #ffc107;
            color: black;
        }
        .badge-completed {
            background-color: #17a2b8;
            color: white;
        }
        .badge-cancelled {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Daftar Semua Jadwal Routing</h1>
    <a href="scheduled_generate.php" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Cari jadwal...">
        <button id="searchBtn"><i class="fas fa-search"></i> Cari</button>
    </div>
    <table id="schedulesTable">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Routingan</th>
                <th>Server</th>
                <th>Nomor Lama</th>
                <th>Nomor Baru</th>
                <th>Waktu</th>
                <th>Status Jadwal</th>
                <th>Status Routing</th>
                <th>Dibuat Oleh</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($schedules)): ?>
            <tr><td colspan="10">Tidak ada jadwal ditemukan</td></tr>
        <?php else: ?>
            <?php foreach ($schedules as $index => $schedule): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($schedule['nama_route'] ?? 'N/A') ?></td>
                    <?php
                        $server_key = strtoupper($schedule['server']);
                        $server_label = $server_aliases[$server_key] ?? $server_key;
                    ?>
                    <td><?= htmlspecialchars($server_label) ?></td>
                    <td><?= htmlspecialchars($schedule['current_number']) ?></td>
                    <td title="<?= htmlspecialchars($schedule['new_number']) ?>">
    			<?= htmlspecialchars($schedule['new_number']) ?>
			</td>

                    <td><?= date('d M Y H:i', strtotime($schedule['schedule_time'])) ?></td>
                    <td>
                        <span class="badge badge-<?= htmlspecialchars($schedule['status']) ?>">
                            <?= ucfirst(htmlspecialchars($schedule['status'])) ?>
                        </span>
                        <?php if ($schedule['status'] === 'pending'): ?>
                            <button class="btn-cancel" onclick="cancelSchedule(<?= $schedule['id'] ?>)">
                                <i class="fas fa-times"></i> Batalkan
                            </button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= ($schedule['routing_status'] ?? 0) == 3 ? 'badge-danger' : 'badge-success' ?>">
                            <?= ($schedule['routing_status'] ?? 0) == 3 ? 'DITUTUP' : 'DIBUKA' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($schedule['username'] ?? 'System') ?></td>
                    <td>
                        <?php if ($schedule['status'] === 'pending'): ?>
                            <button class="btn-action btn-edit" onclick="editSchedule(<?= $schedule['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn-action btn-delete" onclick="deleteSchedule(<?= $schedule['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
// Fungsi pencarian
document.getElementById('searchBtn').addEventListener('click', function() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#schedulesTable tbody tr');
    
    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        row.style.display = rowText.includes(searchTerm) ? '' : 'none';
    });
});

function cancelSchedule(id) {
    if (confirm('Apakah Anda yakin ingin membatalkan jadwal ini?')) {
        fetch(`/process/cancel_schedule.php?id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update UI tanpa refresh
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) {
                        // Update status badge
                        const statusBadge = row.querySelector('.badge');
                        statusBadge.textContent = 'Cancelled';
                        statusBadge.classList.remove('badge-pending');
                        statusBadge.classList.add('badge-cancelled');
                        
                        // Hapus tombol batalkan
                        const cancelBtn = row.querySelector('.btn-cancel');
                        if (cancelBtn) cancelBtn.remove();
                        
                        // Beri feedback ke user
                        alert(data.message);
                    }
                } else {
                    alert(data.message || 'Gagal membatalkan jadwal');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat membatalkan jadwal');
            });
    }
}
// Fungsi edit jadwal
function editSchedule(id) {
    window.location.href = 'edit_schedule.php?id=' + id;
}

// Fungsi hapus jadwal
function deleteSchedule(id) {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal ini secara permanen?')) {
        fetch('process/delete_schedule.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.reload();
                }
            });
    }
}

// Pencarian saat mengetik
document.getElementById('searchInput').addEventListener('keyup', function() {
    document.getElementById('searchBtn').click();
});
</script>
</body>
</html>