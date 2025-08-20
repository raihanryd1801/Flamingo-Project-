<?php
require '../vendor/autoload.php';
include '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$clientName = isset($_GET['client']) ? urldecode($_GET['client']) : 'AllClients';
$prefix = isset($_GET['prefix']) ? urldecode($_GET['prefix']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$clientName = $koneksi->real_escape_string($clientName);
$prefix = $koneksi->real_escape_string($prefix);
$search = $koneksi->real_escape_string($search);

// Build query
$query = "
    SELECT phone_number, client_name, prefix 
    FROM phone_numbers 
    WHERE client_name != '' AND prefix != ''
";

if (!empty($clientName)) {
    $query .= " AND client_name = '$clientName'";
}
if (!empty($prefix)) {
    $query .= " AND prefix = '$prefix'";
}
if (!empty($search)) {
    $words = preg_split('/\s+/', $search);
    foreach ($words as $word) {
        $word = $koneksi->real_escape_string($word);
        $query .= " AND (
            phone_number LIKE '%$word%' OR 
            client_name LIKE '%$word%' OR 
            prefix LIKE '%$word%'
        )";
    }
}

$query .= " ORDER BY phone_number ASC";

$result = $koneksi->query($query);

$numbers = [];
while ($row = $result->fetch_assoc()) {
    $numbers[] = $row['phone_number'];
}

// Spreadsheet setup
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Phone Report');

// === STYLES ===
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 12],
    'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF2F5597']],
    'alignment' => ['horizontal' => 'center']
];
$subHeaderStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FF333333'], 'size' => 11]
];
$dataStyle = [
    'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFF9F9F9']],
    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFD9D9D9']]],
    'font' => ['color' => ['argb' => 'FF333333']]
];

// === HEADER ===
$sheet->mergeCells('A1:C1');
$sheet->setCellValue('A1', 'PHONE NUMBER REPORT');
$sheet->getStyle('A1')->applyFromArray($headerStyle);

$sheet->mergeCells('A2:C2');
$sheet->setCellValue('A2', "CLIENT: {$clientName} | PREFIX: {$prefix} | FILTER: {$search}");
$sheet->getStyle('A2')->applyFromArray($subHeaderStyle);

$sheet->setCellValue('A4', 'Generated:');
$sheet->setCellValue('B4', date("F d, Y H:i:s"));

// === TABEL HEADER ===
$sheet->setCellValue('A6', 'No');
$sheet->setCellValue('B6', 'Phone Number');
$sheet->getStyle('A6:B6')->applyFromArray($subHeaderStyle);
$sheet->getStyle('A6:B6')->getAlignment()->setHorizontal('center');

// ==== PAKSA FORMAT TEXT ====
$highestRow = count($numbers) + 6; // Data mulai dari row 7
$sheet->getStyle("B7:B{$highestRow}")->getNumberFormat()->setFormatCode('@');

// === DATA ===
$row = 7;
$no = 1;

if (count($numbers) > 0) {
    foreach ($numbers as $number) {
        $sheet->setCellValue("A{$row}", $no);
        // Paksa isi cell sebagai string
        $sheet->setCellValueExplicit("B{$row}", $number, DataType::TYPE_STRING);
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($dataStyle);
        $row++;
        $no++;
    }

    // Total
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", "Total Numbers: " . count($numbers));
    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
        'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFE2EFDA']],
        'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['argb' => 'FFD9D9D9']]],
    ]);
} else {
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", "No Data Available");
    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($dataStyle);
}

// Auto width
foreach (range('A', 'B') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output file
// Gunakan nama client yang lebih bersih
$clientName = isset($_GET['client']) ? urldecode($_GET['client']) : 'AllClients';
$filename = "ClientReport_" . str_replace(' ', '_', $clientName ?: 'AllClients') . "_" . date("Ymd_His") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
