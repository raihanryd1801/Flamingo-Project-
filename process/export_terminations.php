<?php
require '../vendor/autoload.php';
include '../config/db.php';
session_start();

if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$operatorId = $_GET['operator_id'] ?? null;
$status = $_GET['status'] ?? 'proses';

if (!$operatorId) {
    die('Operator ID tidak ditemukan.');
}

// Ambil data terminasi berdasarkan status
$query = "
    SELECT o.name AS operator_name, pn.phone_number
    FROM phone_numbers pn
    LEFT JOIN operators o ON pn.operator_id = o.id
    WHERE pn.is_terminated = 1 AND pn.terminate_status = ? AND o.id = ?
    ORDER BY pn.phone_number ASC
";
$stmt = $koneksi->prepare($query);
if (!$stmt) {
    die('Prepare statement error: ' . $koneksi->error);
}
$stmt->bind_param("si", $status, $operatorId);
$stmt->execute();
$result = $stmt->get_result();

$numbers = [];
$operatorName = '';
while ($row = $result->fetch_assoc()) {
    $operatorName = $row['operator_name'] ?? 'Tanpa Operator';
    $numbers[] = $row['phone_number'];
}
$stmt->close();

if (empty($numbers)) {
    die('Tidak ada nomor yang bisa di-export.');
}

// Buat file Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Termination Numbers');

// Header Excel
$sheet->mergeCells('A1:B1');
$sheet->setCellValue('A1', 'TERMINATION REPORT (' . strtoupper($status) . ')');
$sheet->mergeCells('A2:B2');
$sheet->setCellValue('A2', 'Operator: ' . $operatorName);
$sheet->mergeCells('A3:B3');
$sheet->setCellValue('A3', 'Tanggal Export: ' . date('Y-m-d H:i:s'));

// Sub Header
$sheet->setCellValue('A5', 'No');
$sheet->setCellValue('B5', 'Phone Number');

// Data
$row = 6;
$no = 1;
foreach ($numbers as $number) {
    $sheet->setCellValue('A' . $row, $no++);
    $sheet->setCellValue('B' . $row, $number);
    $row++;
}

// Auto Width
foreach (range('A', 'B') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output
$filename = "Termination_" . $status . "_" . str_replace(' ', '_', $operatorName) . "_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
