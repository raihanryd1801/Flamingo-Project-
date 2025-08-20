<?php
session_start();
require_once 'db.php'; // File koneksi database

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'noc_voip') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_schedule':
            $server = $_POST['server'];
            $routingId = $_POST['routing_id'];
            $currentNumber = $_POST['current_number'] ?? '';
            $newNumber = $_POST['new_number'];
            $scheduleTime = $_POST['schedule_time'];
            $isRecurring = isset($_POST['is_recurring']) ? 1 : 0;
            $recurrencePattern = $_POST['recurrence_pattern'] ?? null;
            
            // Validasi nomor
            if (!preg_match('/^[0-9]+$/', $newNumber)) {
                throw new Exception("Nomor hanya boleh mengandung angka");
            }
            
            $stmt = $db->prepare("INSERT INTO routing_schedules 
                                 (routing_id, server, current_number, new_number, schedule_time, 
                                  is_recurring, recurrence_pattern, created_by)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $routingId, $server, $currentNumber, $newNumber, $scheduleTime,
                $isRecurring, $recurrencePattern, $_SESSION['user_id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Jadwal berhasil dibuat'
            ]);
            break;
            
        case 'cancel_schedule':
            $scheduleId = $_POST['id'];
            
            // Verifikasi pemilik jadwal
            $stmt = $db->prepare("SELECT created_by FROM routing_schedules WHERE id = ?");
            $stmt->execute([$scheduleId]);
            $schedule = $stmt->fetch();
            
            if (!$schedule || $schedule['created_by'] != $_SESSION['user_id']) {
                throw new Exception("Anda tidak memiliki izin untuk membatalkan jadwal ini");
            }
            
            $stmt = $db->prepare("UPDATE routing_schedules SET status = 'failed' WHERE id = ?");
            $stmt->execute([$scheduleId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Jadwal berhasil dibatalkan'
            ]);
            break;
            
        default:
            throw new Exception("Aksi tidak valid");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
