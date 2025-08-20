<?php
// db.php config include
include '../config/db.php';

echo "=====================================\n";
echo "      DATA ANALYZER & CLEANER v2     \n";
echo "=====================================\n\n";

// Total record
$totalQuery = "SELECT COUNT(*) as total FROM phone_numbers";
$totalResult = $koneksi->query($totalQuery);
$total = $totalResult->fetch_assoc()['total'];
echo "Total Records: $total\n";

// Assigned client (prefix + client_name tidak kosong)
$assignedQuery = "SELECT COUNT(*) as total FROM phone_numbers WHERE prefix != '' AND client_name != ''";
$assignedResult = $koneksi->query($assignedQuery);
$assigned = $assignedResult->fetch_assoc()['total'];
echo "Assigned to Client: $assigned\n";

// Available stock (prefix kosong, client_name kosong)
$availableQuery = "SELECT COUNT(*) as total FROM phone_numbers WHERE (prefix IS NULL OR prefix = '') AND (client_name IS NULL OR client_name = '')";
$availableResult = $koneksi->query($availableQuery);
$available = $availableResult->fetch_assoc()['total'];
echo "Available Stock: $available\n";

// Anomali: nomor kosong
$anomalyQuery = "SELECT COUNT(*) as total FROM phone_numbers WHERE phone_number IS NULL OR phone_number = ''";
$anomalyResult = $koneksi->query($anomalyQuery);
$anomaly = $anomalyResult->fetch_assoc()['total'];
echo "Anomaly (phone_number kosong): $anomaly\n";

// Optional: Tampilkan persentase
echo "\n";
echo "Summary:\n";
echo "Assigned: ".round(($assigned/$total)*100, 2)."%\n";
echo "Available: ".round(($available/$total)*100, 2)."%\n";
echo "Anomaly: ".round(($anomaly/$total)*100, 2)."%\n";

echo "\n";

// Optional cleaner (hati-hati)
if ($anomaly > 0) {
    echo "Do you want to clean anomaly data? (y/n): ";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    if(trim($line) == 'y'){
        $cleanQuery = "DELETE FROM phone_numbers WHERE phone_number IS NULL OR phone_number = ''";
        if ($koneksi->query($cleanQuery)) {
            echo "Anomaly data cleaned successfully.\n";
        } else {
            echo "Failed to clean anomaly data: ".$koneksi->error."\n";
        }
    } else {
        echo "Cleaner skipped.\n";
    }
    fclose($handle);
} else {
    echo "No anomaly data found. Cleaner skipped.\n";
}

echo "\nProcess completed.\n";

