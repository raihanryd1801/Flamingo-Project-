<?php
require '../config/db.php';

// Ambil parameter filter dari URL
$min = isset($_GET['min']) ? $_GET['min'] : '';
$max = isset($_GET['max']) ? $_GET['max'] : '';
$site = isset($_GET['site']) ? $_GET['site'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$teknisi = isset($_GET['teknisi']) ? $_GET['teknisi'] : '';

// Query dasar
$sql = "SELECT * FROM dailywork WHERE 1=1";

// Filter berdasarkan waktu_mulai
if (!empty($min)) {
    $min_date = date('Y-m-d', strtotime($min));
    $sql .= " AND DATE(waktu_mulai) >= '$min_date'";
}

if (!empty($max)) {
    $max_date = date('Y-m-d', strtotime($max));
    $sql .= " AND DATE(waktu_mulai) <= '$max_date'";
}

// Filter lainnya
if (!empty($site)) {
    $sql .= " AND site = '$site'";
}

if (!empty($status)) {
    $sql .= " AND status_tiket = '$status'";
}

if (!empty($teknisi)) {
    $sql .= " AND nama_teknisi LIKE '%$teknisi%'";
}

$sql .= " ORDER BY waktu_mulai DESC";

$result = $koneksi->query($sql);
$num_rows = $result ? $result->num_rows : 0;


// SOLUSI 2: Fungsi status dengan penanganan khusus untuk Excel
function getExcelStatus($status) {
    // Konversi status ke teks jika berupa angka
    if (is_numeric($status)) {
        $status_mapping = [
            0 => ['text' => 'Pending', 'color' => 'FFFF00'], // Kuning
            1 => ['text' => 'Open', 'color' => '00FF00'],    // Hijau
            2 => ['text' => 'Closed', 'color' => 'FF0000']   // Merah
        ];
        $status = $status_mapping[$status] ?? ['text' => 'Unknown', 'color' => 'CCCCCC'];
        return $status;
    }
    
    // Untuk status teks
    $status = strtolower($status);
    $status_mapping = [
        'pending' => ['text' => 'Pending', 'color' => 'FFFF00'],
        'open' => ['text' => 'Open', 'color' => '00FF00'],
        'closed' => ['text' => 'Closed', 'color' => 'FF0000']
    ];
    
    return $status_mapping[$status] ?? ['text' => $status, 'color' => 'CCCCCC'];
}

// Header untuk file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=DailyWork_Report_".date('Ymd_His').".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Laporan Harian Teknisi</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                            <x:HorizontalResolution>600</x:HorizontalResolution>
                            <x:VerticalResolution>600</x:VerticalResolution>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #006D77;
            padding-bottom: 15px;
        }
        .logo {
            height: 60px;
            max-width: 150px;
        }
        .report-title {
            text-align: center;
            flex-grow: 1;
        }
        .report-title h1 {
            color: #006D77;
            font-size: 22px;
            margin: 0;
        }
        .report-title p {
            color: #666;
            font-size: 14px;
            margin: 5px 0 0;
        }
        .report-info {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #006D77;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 12px;
        }
        th {
            background-color: #006D77;
            color: white;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .no-data {
            color: #e74c3c;
            font-style: italic;
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #777;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

<!-- Header dengan Logo -->
<div class="header-container">
       <div class="report-title">
        <h1>DAILY WORK ACTIVITY REPORT</h1>
        <p>TIM TEKNISI AQSAA & RAJAWIFI</p>
    </div>
    
    </div>

<!-- Info Laporan -->
<div class="report-info">
    <strong>Periode:</strong> <?= !empty($min) ? date('d M Y', strtotime($min)) : 'Semua Tanggal' ?> 
    s/d <?= !empty($max) ? date('d M Y', strtotime($max)) : 'Semua Tanggal' ?> | 
    <strong>Site:</strong> <?= !empty($site) ? $site : 'Semua Site' ?> | 
    <strong>Status:</strong> <?= !empty($status) ? $status : 'Semua Status' ?> | 
    <strong>Teknisi:</strong> <?= !empty($teknisi) ? $teknisi : 'Semua Teknisi' ?>
</div>

<!-- Tabel Data -->
<table>
    <thead>
        <tr>
            <th width="50">No</th>
            <th width="120">Jenis Pekerjaan</th>
            <th width="100">Request By</th>
            <th width="80">ID Pelanggan</th>
            <th width="150">Nama Pelanggan</th>
            <th width="100">Teknisi</th>
            <th width="80">Site</th>
            <th width="150">Lokasi</th>
            <th width="120">Waktu Mulai</th>
            <th width="120">Waktu Selesai</th>
            <th width="80">Total Waktu</th>
            <th width="80">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($num_rows > 0): ?>
            <?php 
            $no = 1;
            while($row = $result->fetch_assoc()): 
                $status_info = getExcelStatus($row['status_tiket']);
            ?>
                <tr>
                    <td align="center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['jenis_pekerjaan']) ?></td>
                    <td><?= htmlspecialchars($row['request_by']) ?></td>
                    <td><?= htmlspecialchars($row['id_pelanggan']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                    <td><?= htmlspecialchars($row['nama_teknisi']) ?></td>
                    <td><?= htmlspecialchars($row['site']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['lokasi'])) ?></td>
                    <td><?= date('d M Y H:i', strtotime($row['waktu_mulai'])) ?></td>
                    <td><?= date('d M Y H:i', strtotime($row['waktu_selesai'])) ?></td>
                    <td align="center"><?= htmlspecialchars($row['total_waktu']) ?></td>
                    <td align="center" style="background-color: #<?= $status_info['color'] ?>; color: dark; font-weight: bold;">
                        <?= $status_info['text'] ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="12" class="no-data">Tidak ada data yang ditemukan untuk kriteria filter yang dipilih</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Footer -->
<div class="footer">
    Dicetak pada: <?= date('d M Y H:i:s') ?> | &copy; <?= date('Y') ?> PT. Dankom Mitra Abadi - AQSAA & Rajawifi
</div>

</body>
</html>
