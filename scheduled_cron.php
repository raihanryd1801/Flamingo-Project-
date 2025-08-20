<?php
require_once 'config/database.php';
require_once 'config/vos_server.php';

// Ambil jadwal yang perlu dieksekusi
$now = date('Y-m-d H:i:00');
$stmt = $db->prepare("SELECT * FROM routing_schedules 
                     WHERE schedule_time <= ? AND status = 'pending'");
$stmt->execute([$now]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($schedules as $schedule) {
    try {
        // Update status ke processing
        $db->prepare("UPDATE routing_schedules SET status = 'processing' WHERE id = ?")
           ->execute([$schedule['id']]);
        
        // Proses update nomor di VOS
        $serverConfig = $vos_servers[$schedule['server']];
        $conn = new mysqli($serverConfig['host'], $serverConfig['user'], $serverConfig['pass'], 'vos3000db');
        
        if ($conn->connect_error) {
            throw new Exception("Koneksi database gagal: " . $conn->connect_error);
        }
        
        $sql = "UPDATE e_gatewayroutingsetting 
                SET rewriterulesincaller = ?
                WHERE gatewayrouting_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $schedule['new_number'], $schedule['routing_id']);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Tidak ada perubahan data");
        }
        
        // Update status ke completed
        $db->prepare("UPDATE routing_schedules 
                     SET status = 'completed', executed_at = NOW() 
                     WHERE id = ?")
           ->execute([$schedule['id']]);
        
        // Buat recurring schedule jika diperlukan
        if ($schedule['is_recurring']) {
            $nextSchedule = calculateNextSchedule($schedule['schedule_time'], $schedule['recurrence_pattern']);
            
            $db->prepare("INSERT INTO routing_schedules 
                         (routing_id, server, current_number, new_number, schedule_time, 
                          is_recurring, recurrence_pattern, created_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
               ->execute([
                   $schedule['routing_id'], $schedule['server'], 
                   $schedule['new_number'], $schedule['new_number'], $nextSchedule,
                   $schedule['is_recurring'], $schedule['recurrence_pattern'], 
                   $schedule['created_by']
               ]);
        }
        
        // Log success
        $db->prepare("INSERT INTO routing_schedule_logs 
                     (schedule_id, status, message)
                     VALUES (?, ?, ?)")
           ->execute([$schedule['id'], 'success', 'Nomor berhasil diupdate']);
        
    } catch (Exception $e) {
        // Update status ke failed
        $db->prepare("UPDATE routing_schedules 
                     SET status = 'failed', executed_at = NOW() 
                     WHERE id = ?")
           ->execute([$schedule['id']]);
        
        // Log error
        $db->prepare("INSERT INTO routing_schedule_logs 
                     (schedule_id, status, message)
                     VALUES (?, ?, ?)")
           ->execute([$schedule['id'], 'failed', $e->getMessage()]);
    }
}

function calculateNextSchedule($currentSchedule, $pattern) {
    $date = new DateTime($currentSchedule);
    
    switch ($pattern) {
        case 'daily':
            $date->modify('+1 day');
            break;
        case 'weekly':
            $date->modify('+1 week');
            break;
        case 'monthly':
            $date->modify('+1 month');
            break;
        default:
            throw new Exception("Pola pengulangan tidak valid");
    }
    
    return $date->format('Y-m-d H:i:00');
}
