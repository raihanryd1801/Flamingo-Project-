<?php
require '../config/db.php';

// Ambil parameter filter dari URL
$min_date = isset($_GET['min']) ? $_GET['min'] : '';
$max_date = isset($_GET['max']) ? $_GET['max'] : '';
$site = isset($_GET['site']) ? $_GET['site'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$teknisi = isset($_GET['teknisi']) ? $_GET['teknisi'] : '';

// Bangun query berdasarkan filter
$sql = "SELECT * FROM dailywork WHERE 1=1";

if (!empty($min_date)) {
    $sql .= " AND DATE(waktu_mulai) >= '$min_date'";
}
if (!empty($max_date)) {
    $sql .= " AND DATE(waktu_mulai) <= '$max_date'";
}
if (!empty($site)) {
    $sql .= " AND site = '$site'";
}
if (!empty($status)) {
    $sql .= " AND status_tiket = '$status'";
}
if (!empty($teknisi)) {
    $sql .= " AND nama_teknisi LIKE '%$teknisi%'";
}

$sql .= " ORDER BY tanggal_input DESC, waktu_mulai DESC";

$result = $koneksi->query($sql);

// Path logo
$logoAqsaa = '../assets/img/Aqsaaa.png';
$logoRajawifi = '../assets/img/RajaWifi2.png';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LAPORAN AKTIVITAS TEKNISI AQSAA & RAJAWIFI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --gray: #95a5a6;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background: white;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .brand-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            margin-bottom: 15px;
        }
        
        .brand-logo {
            height: 80px;
            width: auto;
            max-width: 220px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .header h2 {
            color: var(--secondary);
            font-weight: 700;
            margin-bottom: 5px;
            font-size: 28px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .header p {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 0;
        }
        
        .report-title {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            box-shadow: var(--box-shadow);
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .report-title::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0.1) 100%);
            transform: translateX(-100%);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }
        
        .filter-info {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            font-size: 14px;
            border-left: 4px solid var(--primary);
            box-shadow: var(--box-shadow);
        }
        
        .filter-info h5 {
            color: var(--secondary);
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .filter-info h5::before {
            content: '\f0b0';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 10px;
            color: var(--primary);
        }
        
        .filter-info p {
            margin-bottom: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .filter-info strong {
            color: var(--secondary);
            min-width: 80px;
        }
        
        .filter-info span {
            font-weight: 500;
            color: var(--primary-dark);
            background-color: rgba(52, 152, 219, 0.1);
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
        }
        
        .filter-info span::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 10px;
            margin-right: 5px;
            color: var(--success);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .table th {
            background: linear-gradient(135deg, var(--secondary), var(--primary-dark));
            color: white;
            font-weight: 600;
            padding: 15px 12px;
            text-align: left;
            position: sticky;
            top: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table th:first-child {
            border-top-left-radius: var(--border-radius);
        }
        
        .table th:last-child {
            border-top-right-radius: var(--border-radius);
        }
        
        .table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: #f1f8fe;
        }
        
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .status i {
            margin-right: 5px;
            font-size: 10px;
        }
        
        .status-open {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .status-closed {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .status-pending {
            background-color: #fff8e1;
            color: #ff8f00;
            border: 1px solid #ffecb3;
        }
        
        .status-inprogress {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #bbdefb;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: var(--gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer .signature {
            text-align: center;
            margin-top: 30px;
        }
        
        .footer .signature-line {
            width: 200px;
            border-top: 1px solid var(--gray);
            margin: 5px auto;
        }
        
        .page-number:after {
            content: counter(page);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .no-data i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        .no-data p {
            font-size: 16px;
            margin-bottom: 0;
        }
        
        .no-print {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn {
            border-radius: var(--border-radius);
            font-weight: 500;
            padding: 8px 15px;
            font-size: 14px;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        
        @media print {
            body {
                padding: 10px !important;
                font-size: 12px;
            }
            
            .no-print {
                display: none;
            }
            
            .header {
                margin-top: 0;
                padding-top: 0;
            }
            
            .table th {
                position: static;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .footer {
                position: fixed;
                bottom: 0;
                width: 100%;
                background: white;
                padding: 10px 0;
            }
            
            .report-title, .status {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .report-title::after {
                display: none;
            }
            
            @page {
                size: auto;
                margin: 10mm;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print">
    <a href="laporan.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> Cetak Lagi
    </button>
</div>

<div class="header">
    <div class="brand-container">
        <img src="<?php echo $logoAqsaa; ?>" onerror="this.src='https://via.placeholder.com/220x80?text=AQSAA'" alt="AQSAA Logo" class="brand-logo">
        <img src="<?php echo $logoRajawifi; ?>" onerror="this.src='https://via.placeholder.com/220x80?text=RAJAWIFI'" alt="RAJAWIFI Logo" class="brand-logo">
    </div>
    <h2>LAPORAN AKTIVITAS TEKNISI</h2>
    <p>Dokumen ini dicetak secara otomatis oleh sistem</p>
</div>

<div class="report-title">
    <i class="fas fa-file-alt"></i> DETAIL LAPORAN KINERJA TEKNISI
</div>

<div class="filter-info">
    <h5>FILTER YANG DIGUNAKAN</h5>
    <p>
        <?php if (!empty($min_date) || !empty($max_date)): ?>
            <strong>Tanggal:</strong> 
            <span><?= !empty($min_date) ? date('d M Y', strtotime($min_date)) : 'Awal' ?></span> 
            sampai 
            <span><?= !empty($max_date) ? date('d M Y', strtotime($max_date)) : 'Akhir' ?></span>
        <?php endif; ?>
    </p>
    
    <?php if (!empty($site)): ?>
        <p><strong>Site:</strong> <span><?= htmlspecialchars($site) ?></span></p>
    <?php endif; ?>
    
    <?php if (!empty($status)): ?>
        <p><strong>Status:</strong> <span><?= htmlspecialchars($status) ?></span></p>
    <?php endif; ?>
    
    <?php if (!empty($teknisi)): ?>
        <p><strong>Teknisi:</strong> <span><?= htmlspecialchars($teknisi) ?></span></p>
    <?php endif; ?>
</div>

<table class="table">
    <thead>
        <tr>
            <th>No</th>
            <th>Jenis Pekerjaan</th>
            <th>Request By</th>
            <th>ID Pelanggan</th>
            <th>Nama Pelanggan</th>
            <th>Teknisi</th>
            <th>Lokasi</th>
            <th>Waktu Mulai</th>
            <th>Waktu Selesai</th>
            <th>Total Waktu</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            $no = 1;
            while($row = $result->fetch_assoc()): 
                $statusClass = '';
                $statusIcon = '';
                $statusText = strtolower($row['status_tiket']);
                
                if ($statusText === 'closed') {
                    $statusClass = 'status-closed';
                    $statusIcon = 'fa-check-circle';
                } elseif ($statusText === 'open') {
                    $statusClass = 'status-open';
                    $statusIcon = 'fa-door-open';
                } elseif ($statusText === 'pending') {
                    $statusClass = 'status-pending';
                    $statusIcon = 'fa-clock';
                } else {
                    $statusClass = 'status-inprogress';
                    $statusIcon = 'fa-spinner';
                }
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['jenis_pekerjaan']) ?></td>
                    <td><?= htmlspecialchars($row['request_by']) ?></td>
                    <td><?= htmlspecialchars($row['id_pelanggan']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                    <td><?= htmlspecialchars($row['nama_teknisi']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['lokasi'])) ?></td>
                    <td><?= date('d M Y H:i', strtotime($row['waktu_mulai'])) ?></td>
                    <td><?= date('d M Y H:i', strtotime($row['waktu_selesai'])) ?></td>
                    <td><?= htmlspecialchars($row['total_waktu']) ?></td>
                    <td>
                        <span class="status <?= $statusClass ?>">
                            <i class="fas <?= $statusIcon ?>"></i> <?= htmlspecialchars($row['status_tiket']) ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="11">
                    <div class="no-data">
                        <i class="fas fa-database"></i>
                        <p>Tidak ada data yang sesuai dengan filter yang dipilih</p>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div class="footer">
    <div>Dicetak pada <?= date('d F Y H:i:s') ?></div>
    <div>Halaman <span class="page-number"></span></div>
    <div class="signature no-print">
        <div class="signature-line"></div>
        <div>Tanda Tangan</div>
    </div>
</div>

<!-- Font Awesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>