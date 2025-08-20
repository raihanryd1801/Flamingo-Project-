<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session
session_start();

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Setup logging
$logFile = __DIR__ . '/logs/scheduler_' . date('Y-m-d') . '.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

function log_message($message) {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, "$timestamp $message\n", FILE_APPEND);
}

// Override default PHP session cache headers
header_remove("Pragma");
header_remove("Cache-Control");
header_remove("Expires");

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    log_message('Akses ditolak - tidak ada session user_id/token');
    die(json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']));
}

// Authorization check
$allowed_roles = ['noc_voip', 'administrator'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    log_message('Akses ditolak untuk role: ' . $_SESSION['role']);
    die(json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses']));
}

// Load database configurations
require_once __DIR__ . '/config/db.php'; // Local database config
require_once __DIR__ . '/config/vos_server.php'; // VOS servers config

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        log_message('CSRF token tidak valid');
        die(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
    }
    
    // Get input data
    $input = file_get_contents('php://input');
    $post_data = $_POST;
    
    if (empty($_POST) && strpos($input, '{') === 0) {
        $post_data = json_decode($input, true) ?? [];
    }

    $action = $post_data['action'] ?? '';
    
    if ($action === 'create_schedule') {
        // Add a unique identifier for this submission
        $submissionId = md5(serialize($post_data) . time());
        
        // Check if this is a duplicate submission
        if (isset($_SESSION['last_submission']) && $_SESSION['last_submission'] === $submissionId) {
            log_message('Duplicate submission detected');
            echo json_encode(['success' => false, 'message' => 'Permintaan sudah diproses sebelumnya']);
            exit;
        }
        
        $_SESSION['last_submission'] = $submissionId;

        $routing_id = intval($post_data['routing_id'] ?? 0);
        $new_number = trim($post_data['new_number'] ?? '');
        $schedule_time = trim($post_data['schedule_time'] ?? '');
        $is_recurring = isset($post_data['is_recurring']) ? 1 : 0;
        $recurrence_pattern = $is_recurring ? ($post_data['recurrence_pattern'] ?? 'daily') : '';
        $server = $post_data['server'] ?? 'vos1';
        $current_locktype = $post_data['current_locktype'] ?? 0;
        $nama_route = trim($post_data['nama_route'] ?? '');

        // Validation
        if ($routing_id <= 0 || empty($new_number) || empty($schedule_time)) {
            log_message('Validasi gagal - data tidak lengkap');
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            exit;
        }

        // Validate phone number format
        if (!preg_match('/^[0-9]+$/', $new_number)) {
            log_message('Validasi gagal - format nomor tidak valid: ' . $new_number);
            echo json_encode(['success' => false, 'message' => 'Nomor hanya boleh mengandung angka']);
            exit;
        }

        try {
            // Connect to LOCAL database
            $local_db = new mysqli('p:' . $host, $username, $password, $database);
            if ($local_db->connect_error) {
                throw new Exception("Koneksi database lokal gagal: " . $local_db->connect_error);
            }

            // Get current number from VOS server
            $current_number = getCurrentNumberFromVOS($server, $routing_id);
            
            // Prepare and execute insert query
            $stmt = $local_db->prepare("INSERT INTO routing_schedules 
                (routing_id, nama_route, server, current_number, new_number, 
                schedule_time, is_recurring, recurrence_pattern, 
                status, created_by, routing_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
            if (!$stmt) {
                throw new Exception("Gagal mempersiapkan query: " . $local_db->error);
            }

            $stmt->bind_param("isssssissi", 
                $routing_id, 
                $nama_route, 
                $server, 
                $current_number, 
                $new_number, 
                $schedule_time, 
                $is_recurring, 
                $recurrence_pattern, 
                $_SESSION['user_id'], 
                $current_locktype
            );

            if ($stmt->execute()) {
                log_message("Jadwal berhasil dibuat - Routing ID: $routing_id, Nomor Baru: $new_number");
                echo json_encode(['success' => true, 'message' => 'Jadwal berhasil dibuat']);
            } else {
                throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
            }
        } catch (Exception $e) {
            log_message('Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } finally {
            if (isset($local_db)) $local_db->close();
        }
        exit;
    } elseif ($action === 'cancel_schedule') {
        $schedule_id = intval($post_data['id'] ?? 0);

        try {
            // Connect to LOCAL database
            $local_db = new mysqli('p:' . $host, $username, $password, $database);
            if ($local_db->connect_error) {
                throw new Exception("Koneksi database lokal gagal: " . $local_db->connect_error);
            }

            $stmt = $local_db->prepare("UPDATE routing_schedules SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
            
            if (!$stmt) {
                throw new Exception("Gagal mempersiapkan query: " . $local_db->error);
            }
            
            $stmt->bind_param("i", $schedule_id);

            if ($stmt->execute()) {
                log_message("Jadwal berhasil dibatalkan - ID: $schedule_id");
                echo json_encode([
                    'success' => true,
                    'message' => 'Jadwal berhasil dibatalkan',
                    'affected_rows' => $stmt->affected_rows
                ]);
            } else {
                throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
            }
        } catch (Exception $e) {
            log_message('Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } finally {
            if (isset($local_db)) $local_db->close();
        }
        exit;
     } else {
        log_message('Aksi tidak valid: ' . $action);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
        exit;
    }
}

/**
 * Get current number from VOS server
 */
function getCurrentNumberFromVOS($server, $routing_id) {
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
        $stmt = $conn->prepare("SELECT s.rewriterulesincaller 
                       FROM e_gatewayrouting r
                       JOIN e_gatewayroutingsetting s ON r.id = s.gatewayrouting_id
                       WHERE r.id = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan query VOS: " . $conn->error);
        }
        
        $stmt->bind_param("i", $routing_id);
        if (!$stmt->execute()) {
            throw new Exception("Gagal mengeksekusi query VOS: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if (!$row) {
            throw new Exception("Data routing tidak ditemukan di server VOS");
        }
        
        return $row['rewriterulesincaller'] ?? '';
    } finally {
        $conn->close();
    }
}

/**
 * Get schedules from local database
 */
function getSchedules($userId) {
    global $host, $username, $password, $database;
    
    try {
        $local_db = new mysqli('p:' . $host, $username, $password, $database);
        if ($local_db->connect_error) {
            throw new Exception("Koneksi database lokal gagal: " . $local_db->connect_error);
        }

       $query = "SELECT s.*, IFNULL(r.name, 'Unknown') as routing_name, 
              IFNULL(r.locktype, 0) as routing_status 
              FROM routing_schedules s
              LEFT JOIN e_gatewayrouting r ON s.routing_id = r.id
              WHERE s.created_by = ? 
              ORDER BY s.schedule_time DESC";
        
        $stmt = $local_db->prepare($query);
        if (!$stmt) {
            throw new Exception("Gagal mempersiapkan query: " . $local_db->error);
        }
        
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getSchedules: " . $e->getMessage());
        return [];
    } finally {
        if (isset($local_db)) $local_db->close();
    }
}

/**
 * Get routing data from VOS server
 */
function getRoutingData($server) {
    $cacheFile = __DIR__ . '/cache/routing_' . $server . '.json';
    $cacheTime = 300; // 5 menit
    header("Cache-Control: max-age=$cacheTime, public");

    // Cek cache dulu
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    // Kalau cache tidak ada atau expired, ambil dari database
    global $vos_servers;

    if (!isset($vos_servers[$server])) {
        return [];
    }

    $cfg = $vos_servers[$server];
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);

    if ($conn->connect_error) {
        error_log("Gagal terkoneksi ke server VOS {$server}: " . $conn->connect_error);
        return [];
    }

    try {
        $sql = "SELECT r.id, r.name, r.prefix, r.locktype, s.rewriterulesincaller
                FROM e_gatewayrouting r
                JOIN e_gatewayroutingsetting s ON r.id = s.gatewayrouting_id
                ORDER BY r.name";

        $result = $conn->query($sql);
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // Simpan ke cache
        if (!file_exists(__DIR__ . '/cache')) {
            mkdir(__DIR__ . '/cache', 0755, true);
        }

        file_put_contents($cacheFile, json_encode($data));

        return $data;
    } catch (Exception $e) {
        error_log("Error in getRoutingData: " . $e->getMessage());
        return [];
    } finally {
        $conn->close();
    }
}

// Get data for display
$server = isset($_GET['server']) && in_array($_GET['server'], ['vos1', 'vos2', 'vos3','vos4']) ? $_GET['server'] : 'vos1';
$routingData = getRoutingData($server);
$schedules = getSchedules($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Automation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="css/scheduleroute.css">
    <style>
        body.loading {
            cursor: wait;
        }
        body.loading * {
            pointer-events: none;
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        body.loading .loading-overlay {
            display: flex;
        }
        .loading-spinner {
            color: white;
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i> Memproses...
        </div>
    </div>

    <div class="app-container">
        <header class="app-header">
            <a href="/index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Menu
            </a>
            <div class="header-content">
                <h1>Penjadwalan Pergantian Nomor Routing VOS3000</h1>
                <p>Jadwalkan pergantian nomor routing untuk waktu tertentu</p>
            </div>
        </header>

        <div class="server-selector">
            <form method="get">
                <div class="form-group">
                    <label for="server" id="server-label">Pilih Server VOS:</label>
                    <select name="server" id="server" aria-labelledby="server-label" onchange="this.form.submit()">
                        <option value="vos1" <?= isset($_GET['server']) && $_GET['server'] === 'vos1' ? 'selected' : '' ?>>VOS NINO</option>
                        <option value="vos2" <?= isset($_GET['server']) && $_GET['server'] === 'vos2' ? 'selected' : '' ?>>VOS CLIENT 10</option>
                        <option value="vos3" <?= isset($_GET['server']) && $_GET['server'] === 'vos3' ? 'selected' : '' ?>>VOS CLIENT 71</option>
                        <option value="vos4" <?= isset($_GET['server']) && $_GET['server'] === 'vos4' ? 'selected' : '' ?>>VOS ISAT MULTI 114</option>
                    </select>
                </div>
            </form>
        </div>

        <main class="app-main">
            <div class="app-tabs">
                <nav class="tabs-nav">
                    <button class="tab-btn active" data-tab="create">Buat Jadwal Baru</button>
                    <a href="daftar_jadwal.php" class="tab-btn">Daftar Jadwal</a>
                </nav>

                <div class="tab-panel active" id="create-tab">
                    <form id="scheduleForm" class="schedule-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="server" value="<?= htmlspecialchars($server) ?>">
                        <input type="hidden" name="action" value="create_schedule">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="routingSelect">Pilih Routing:</label>
                                <div class="routing-select-container">
                                    <div class="routing-select-wrapper">
                                        <div class="routing-select-header">
                                            <span>Daftar Routing</span>
                                            <span class="routing-select-count"><?= count($routingData) ?> routing tersedia</span>
                                        </div>
                                        <input type="text" id="routingSearch" class="routing-search" placeholder="Cari routing...">
                                        <select name="routing_id" id="routingSelect" required class="routing-select" size="8">
                                            <?php foreach ($routingData as $row):
                                            $lockStatus = $row['locktype'] == 3 ? 'DITUTUP' : 'DIBUKA'; 
                                            $statusClass = $row['locktype'] == 3 ? 'locked' : 'unlocked';
                                            ?>
                                            <option 
                                                value="<?= htmlspecialchars($row['id']) ?>" 
                                                data-current-number="<?= htmlspecialchars($row['rewriterulesincaller']) ?>"
                                                data-locktype="<?= $row['locktype'] ?>"
                                                class="routing-option <?= $statusClass ?>">
                                                <span class="routing-name"><?= htmlspecialchars($row['name']) ?></span>
                                                <span class="routing-details">
                                                    <span class="prefix">Prefix: <?= htmlspecialchars($row['prefix']) ?></span>
                                                    <span class="number">Nomor: <?= htmlspecialchars($row['rewriterulesincaller']) ?></span>
                                                    <span class="status <?= $statusClass ?>">Status: <?= $lockStatus ?></span>
                                                </span>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Status Routing Saat Penjadwalan:</label>
                                <select name="current_locktype" id="routingStatus" class="form-control">
                                    <option value="0">Buka Routing</option>
                                    <option value="3">Tutup Routing</option>
                                </select>
                            </div>
                            <div> 
                                <input type="hidden" name="nama_route" id="nama_route">
                            </div>

                            <div class="form-group">
                                <label for="currentNumber">Nomor Saat Ini:</label>
                                <input type="text" id="currentNumber" readonly class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="newNumber">Nomor Baru:</label>
                                <input type="text" name="new_number" id="newNumber" placeholder="Contoh: 628123456789" required class="form-control" pattern="[0-9]+" title="Hanya angka yang diperbolehkan">
                            </div>

                            <div class="form-group">
                                <label for="scheduleTime">Waktu Penjadwalan:</label>
                                <input type="text" name="schedule_time" id="scheduleTime" placeholder="Pilih tanggal dan waktu" required class="form-control">
                            </div>

                            <div class="form-group checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_recurring" id="isRecurring" class="checkbox-input">
                                    <span class="checkbox-custom"></span>
                                    Jadwal Berulang
                                </label>
                            </div>

                            <div class="form-group" id="recurrenceOptions" style="display: none;">
                                <label for="recurrencePattern">Pola Pengulangan:</label>
                                <select name="recurrence_pattern" id="recurrencePattern" class="form-control">
                                    <option value="daily">Harian</option>
                                    <option value="weekly">Mingguan (Setiap Senin)</option>
                                    <option value="monthly">Bulanan (Setiap Tanggal 1)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> Buat Jadwal
                            </button>
                        </div>
                    </form>

                    <div id="result" class="result-message"></div>
                </div>

                <div class="tab-panel" id="list-tab">
                    <div class="schedule-list">
                        <?php if (empty($schedules)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>Tidak ada jadwal yang ditemukan.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): ?>
                                <div class="schedule-card">
                                    <div class="schedule-header">
                                        <h4><?= htmlspecialchars($schedule['routing_name'] ?? 'Unknown') ?></h4>
                                        <div class="schedule-status">
                                            <span class="status-badge <?= $schedule['status'] ?>">
                                                <?= ucfirst($schedule['status']) ?>
                                            </span>
                                            <span class="lock-badge <?= $schedule['routing_status'] == 3 ? 'locked' : 'unlocked' ?>">
                                                <?= $schedule['routing_status'] == 3 ? 'DITUTUP' : 'DIBUKA' ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="schedule-body">
                                        <div class="schedule-info">
                                            <div class="info-item">
                                                <span class="info-label">Dari:</span>
                                                <span class="info-value"><?= htmlspecialchars($schedule['current_number']) ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Ke:</span>
                                                <span class="info-value"><?= htmlspecialchars($schedule['new_number']) ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Waktu:</span>
                                                <span class="info-value"><?= date('d M Y H:i', strtotime($schedule['schedule_time'])) ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($schedule['is_recurring']): ?>
                                            <div class="recurring-info">
                                                <i class="fas fa-redo"></i>
                                                <span>Berulang: <?= ucfirst($schedule['recurrence_pattern']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="schedule-actions">
                                        <button class="btn btn-cancel" data-id="<?= $schedule['id'] ?>" <?= $schedule['status'] !== 'pending' ? 'disabled' : '' ?>>
                                            <i class="fas fa-times"></i> Batalkan
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize datepicker
        flatpickr("#scheduleTime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true,
            locale: "id"
        });

        // Toggle recurring options
        document.getElementById('isRecurring').addEventListener('change', function() {
            document.getElementById('recurrenceOptions').style.display = this.checked ? 'block' : 'none';
        });

        // Update fields when routing changes
        const routingSelect = document.getElementById('routingSelect');
        const currentNumberField = document.getElementById('currentNumber');
        const routingStatusField = document.getElementById('routingStatus');

        function updateFields() {
            if (routingSelect.selectedIndex === -1) {
                currentNumberField.value = '';
                routingStatusField.value = '0';
                document.getElementById('nama_route').value = '';
                return;
            }

            const selectedOption = routingSelect.options[routingSelect.selectedIndex];
            currentNumberField.value = selectedOption.dataset.currentNumber || '';
            routingStatusField.value = selectedOption.dataset.locktype || '0';
            
            // Ambil teks dari span.routing-name di dalam option yang dipilih
            const optionContent = selectedOption.innerHTML;
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = optionContent;
            const routingNameSpan = tempDiv.querySelector('.routing-name');
            const routeName = routingNameSpan ? routingNameSpan.textContent : '';
            
            document.getElementById('nama_route').value = routeName.trim();
        }

        routingSelect.addEventListener('change', updateFields);
        updateFields(); // Initialize

        // Tab navigation
        document.querySelectorAll('.tab-btn').forEach(tab => {
            tab.addEventListener('click', function() {
                if (this.classList.contains('active')) return;
                
                document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab + '-tab').classList.add('active');
            });
        });

        // Form submission
        const form = document.getElementById('scheduleForm');
        let isSubmitting = false;
        
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (isSubmitting) return;
            
            // Client-side validation
            if (!form.elements['routing_id'].value) {
                showResult('Pilih routing terlebih dahulu', 'error');
                return;
            }
            
            if (!form.elements['new_number'].value) {
                showResult('Masukkan nomor baru', 'error');
                return;
            }
            
            if (!form.elements['schedule_time'].value) {
                showResult('Pilih waktu penjadwalan', 'error');
                return;
            }
            
            isSubmitting = true;
            document.body.classList.add('loading');

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

            try {
                const formData = new FormData(form);
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showResult('Jadwal berhasil dibuat!', 'success');
                    form.reset();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan');
                }
            } catch (error) {
                showResult(`Error: ${error.message}`, 'error');
                console.error("Error:", error);
            } finally {
                isSubmitting = false;
                document.body.classList.remove('loading');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });

        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.textContent = message;
            resultDiv.className = `result-message ${type}`;
            resultDiv.style.display = 'block';
            
            // Hide after 5 seconds
            setTimeout(() => {
                resultDiv.style.display = 'none';
            }, 5000);
        }

        // Cancel schedule
        document.querySelectorAll('.btn-cancel').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!confirm('Apakah Anda yakin ingin membatalkan jadwal ini?')) return;
                if (this.disabled) return;

                document.body.classList.add('loading');
                
                const scheduleId = this.dataset.id;
                const formData = new FormData();
                formData.append('action', 'cancel_schedule');
                formData.append('id', scheduleId);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Jadwal berhasil dibatalkan');
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Gagal membatalkan jadwal');
                    }
                } catch (error) {
                    alert(`Error: ${error.message}`);
                    console.error('Error:', error);
                } finally {
                    document.body.classList.remove('loading');
                }
            });
        });

        // Routing search
        document.getElementById('routingSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const options = document.getElementById('routingSelect').options;
            
            let firstVisible = null;
            
            for (let i = 0; i < options.length; i++) {
                const optionText = options[i].text.toLowerCase();
                const isVisible = optionText.includes(searchTerm);
                options[i].style.display = isVisible ? '' : 'none';
                
                if (isVisible && firstVisible === null) {
                    firstVisible = options[i];
                }
            }
            
            if (firstVisible) {
                firstVisible.selected = true;
                updateFields();
            }
        });
    });
    </script>
</body>
</html>