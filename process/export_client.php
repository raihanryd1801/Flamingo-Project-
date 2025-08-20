<?php
require '../vendor/autoload.php';
include '../config/db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// Ambil parameter client dan prefix
$clientName = isset($_GET['client']) ? urldecode($_GET['client']) : null;
$prefix = isset($_GET['prefix']) ? urldecode($_GET['prefix']) : null;

if (!$clientName || !$prefix) {
    die("Missing required parameters.");
}

// Query data berdasarkan client + prefix
$stmt = $koneksi->prepare("SELECT phone_number FROM phone_numbers WHERE client_name = ? AND prefix = ?");
$stmt->bind_param("ss", $clientName, $prefix);
$stmt->execute();
$result = $stmt->get_result();

$numbers = [];
while ($row = $result->fetch_assoc()) {
    $numbers[] = $row['phone_number'];
}
$stmt->close();

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Phone Report');

// === HEADER STYLE ===
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FFFFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF2F5597']
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
    ]
];

// === SUB HEADER STYLE ===
$subHeaderStyle = [
    'font' => [
        'bold' => true,
        'color' => ['argb' => 'FF333333'],
        'size' => 11
    ]
];

// === DATA STYLE ===
$dataStyle = [
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FFF9F9F9']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['argb' => 'FFD9D9D9'],
        ],
    ],
    'font' => [
        'color' => ['argb' => 'FF333333']
    ]
];

// SET TITLE
$sheet->mergeCells('A1:C1');
$sheet->setCellValue('A1', 'PHONE NUMBER REPORT');
$sheet->getStyle('A1')->applyFromArray($headerStyle);

$sheet->mergeCells('A2:C2');
$sheet->setCellValue('A2', "CLIENT: {$clientName} | PREFIX: {$prefix}");
$sheet->getStyle('A2')->applyFromArray($subHeaderStyle);

// Generated Info
$sheet->setCellValue('A4', 'Generated:');
$sheet->setCellValue('B4', date("F d, Y H:i:s"));

// Table Header
$sheet->setCellValue('A6', 'No');
$sheet->setCellValue('B6', 'Phone Number');
$sheet->getStyle('A6:B6')->applyFromArray($subHeaderStyle);
$sheet->getStyle('A6:B6')->getAlignment()->setHorizontal('center');

// === PAKSA FORMAT TEXT ===
if (count($numbers) > 0) {
    $highestRow = count($numbers) + 6;
    $sheet->getStyle("B7:B{$highestRow}")->getNumberFormat()->setFormatCode('@');
}

// Populate Data
$row = 7;
$no = 1;

if (count($numbers) > 0) {
    foreach ($numbers as $number) {
        $sheet->setCellValue("A{$row}", $no);
        // Paksa isi cell sebagai string
        $sheet->setCellValueExplicit("B{$row}", $number, DataType::TYPE_STRING);

        // Rata tengah nomor
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Rata tengah No
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Data Style
        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($dataStyle);

        $row++;
        $no++;
    }

    // Total Numbers
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", "Total Numbers: " . count($numbers));
    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
        'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFE2EFDA']  // Hijau soft
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FFD9D9D9'],
            ],
        ],
    ]);
} else {
    $sheet->mergeCells("A{$row}:B{$row}");
    $sheet->setCellValue("A{$row}", "No Data Available");
    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($dataStyle);
}

// Auto width columns
foreach (range('A', 'B') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output Excel
$filename = "ClientReport_" . str_replace(' ', '_', $clientName) . "_" . date("Ymd_His") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"{$filename}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
