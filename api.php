<?php
header('Content-Type: application/json');

$host = "";  // Ganti dengan IP server VOS3000
$user = "noc";
$password = "";
$database = "vos300db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Koneksi gagal: " . $conn->connect_error]);
    exit();
}

// Query untuk mengambil saldo user (ganti sesuai kebutuhan)
$sql = "SELECT AccountID, Balance FROM account_info LIMIT 10";
$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["status" => "success", "data" => $data]);

$conn->close();
?>

