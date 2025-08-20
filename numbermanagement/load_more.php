<?php
include '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operatorId = $_POST['operator_id'] ?? null;
    $offset = $_POST['offset'] ?? 0;
    $limit = 1000; // Load per 50 nomor

    if (!$operatorId) {
        echo json_encode(['error' => 'Operator ID tidak valid.']);
        exit;
    }

    $query = "
        SELECT phone_number 
        FROM phone_numbers 
        WHERE operator_id = ? AND is_terminated = 1 AND terminate_status = 'proses' 
        ORDER BY phone_number 
        LIMIT ? OFFSET ?
    ";

    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("iii", $operatorId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $numbers = [];
    while ($row = $result->fetch_assoc()) {
        $numbers[] = $row['phone_number'];
    }

    $response = [
        'numbers' => $numbers,
        'next_offset' => $offset + $limit,
        'has_more' => count($numbers) === $limit // ? Simple dan akurat
    ];

    echo json_encode($response);
}
?>
