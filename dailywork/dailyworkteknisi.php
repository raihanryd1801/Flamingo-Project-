<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('error_reporting', (string)E_ALL);
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: ../login.php');
    exit;
}

// Mengatur hak akses  
if ($_SESSION['role'] !== 'noc_internet' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}



require '../config/db.php';

$tanggal_input = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Daily Work Teknisi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin-top: 70px;
            margin-bottom: 30px;
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .back-btn {
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1000;
            background: linear-gradient(to right, #006d77, #83c5be);
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-primary {
            background-color: #006d77;
            border-color: #006d77;
        }
        .paste-section {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        #pasteData {
            font-family: monospace;
            white-space: pre;
            min-height: 150px;
        }
        #parseBtn {
            transition: all 0.3s;
        }
        #parseBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .time-input {
            position: relative;
        }
    </style>
</head>
<body>

<a href="/index.php" class="btn back-btn">
    <i class="bi bi-arrow-left-short"></i> Kembali
</a>

<div class="container">
    <div class="mb-4 text-end">
        <a href="laporan.php" class="btn btn-success">
            <i class="bi bi-file-earmark-text"></i> Lihat Laporan
        </a>
    </div>

    <h2 class="mb-4 text-center">Form Daily Work Teknisi</h2>
    
    <!-- Paste Data Section -->
    <div class="paste-section">
        <div class="mb-3">
            <label class="form-label fw-bold">Tempel Data Teknisi:</label>
            <textarea id="pasteData" class="form-control" rows="6" 
                      placeholder="Contoh format:
Tanggal : 15/05/2025
Jenis Pekerjaan : Maintenance 
Nama Teknisi : Zali Dan Faisal
ID Pelanggan : 11002133
Nama Client : Pandu mannalahi
Lokasi : Ciherang RT01/06
Waktu Mulai : 18:00
Waktu Selesai : 19:00
Indikasi : Los merah
Action : penyambungan kabel DW
Status ticket : Closed"></textarea>
            <button id="parseBtn" class="btn btn-info mt-2 w-100">
                <i class="bi bi-clipboard-check"></i> KONVERSI KE FORM
            </button>
        </div>
    </div>
    
    <form action="../process/dailywork_save.php" method="POST" id="workForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['token'] ?? '' ?>">
        
        <div class="mb-3">
            <label for="jenis_pekerjaan" class="form-label">Jenis Pekerjaan</label>
            <select class="form-select" name="jenis_pekerjaan" required>
		<option value="Aktivasi Baru">Aktivasi Bundling</option>	
                <option value="Aktivasi Baru">Aktivasi Baru</option>
                <option value="Maintenance">Maintenance</option>
		<option value="Aktivasi Baru">Maintenance Bundling</option>
                <option value="Dismantle">Dismantle</option>
                <option value="Relokasi">Relokasi</option>
                <option value="Reactivation">Reactivation</option>          
            </select>
        </div>

        <div class="mb-3">
            <label for="request_by" class="form-label">Request By</label>
            <select class="form-select" name="request_by" required>
                <option value="Client">Client</option>
                <option value="Marketing/Sales">Marketing/Sales</option>
                <option value="Customer Service">Customer Service</option>
                <option value="NOC">NOC</option>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="id_pelanggan" class="form-label">ID Pelanggan</label>
                <input type="text" class="form-control" name="id_pelanggan" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="nama_pelanggan" class="form-label">Nama Pelanggan</label>
                <input type="text" class="form-control" name="nama_pelanggan" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nama_teknisi" class="form-label">Nama Teknisi</label>
                <input type="text" class="form-control" name="nama_teknisi" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="site" class="form-label">Site</label>
                <input type="text" class="form-control" name="site">
            </div>
        </div>

        <div class="mb-3">
            <label for="lokasi" class="form-label">Lokasi</label>
            <textarea class="form-control" name="lokasi" rows="2" required></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="tanggal_mulai" class="form-label">Tanggal</label>
                <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai" value="<?= $tanggal_input ?>" required>
            </div>
            <div class="col-md-3 mb-3">
                <label for="waktu_mulai" class="form-label">Waktu Mulai</label>
                <input type="time" class="form-control" name="waktu_mulai" id="waktu_mulai" required>
            </div>
            <div class="col-md-3 mb-3">
                <label for="waktu_selesai" class="form-label">Waktu Selesai</label>
                <input type="time" class="form-control" name="waktu_selesai" id="waktu_selesai" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="total_waktu" class="form-label">Total Waktu Pengerjaan (hh:mm:ss)</label>
            <input type="text" class="form-control" name="total_waktu" id="total_waktu" readonly>
        </div>

        <div class="mb-3">
            <label for="indikasi_case" class="form-label">Indikasi Case</label>
            <textarea class="form-control" name="indikasi_case" rows="2"></textarea>
        </div>

        <div class="mb-3">
            <label for="action" class="form-label">Action</label>
            <textarea class="form-control" name="action" rows="2"></textarea>
        </div>

        <div class="mb-3">
            <label for="status_tiket" class="form-label">Status Tiket</label>
            <select class="form-select" name="status_tiket" required>
                <option value="Open">Open</option>
                <option value="Closed">Closed</option>
                <option value="Pending">Pending</option>
                <option value="Dalam Proses">Dalam Proses</option>
                <option value="Dalam Monitoring">Dalam Monitoring</option>
                <option value="Cancel">Cancel</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="catatan" class="form-label">Catatan</label>
            <textarea class="form-control" name="catatan" rows="2"></textarea>
        </div>

        <input type="hidden" name="tanggal_input" value="<?= $tanggal_input ?>">

        <div class="d-grid">
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <span id="submitText">Simpan Data</span>
                <span id="submitSpinner" class="spinner-border spinner-border-sm d-none"></span>
            </button>
        </div>
    </form>
</div>

<script>
// Fungsi untuk parsing teks teknisi
function parseTechnicianText(text) {
    const result = {};
    const lines = text.split('\n');
    
    const patterns = [
        { regex: /Tanggal\s*:\s*(.+)/i, key: 'tanggal_mulai' },
        { regex: /Jenis\s*Pekerjaan\s*:\s*(.+)/i, key: 'jenis_pekerjaan' },
        { regex: /Nama\s*Teknisi\s*:\s*(.+)/i, key: 'nama_teknisi' },
        { regex: /ID\s*Pelanggan\s*:\s*(\d+)/i, key: 'id_pelanggan' },
        { regex: /(Nama\s*Client|Nama\s*Pelanggan)\s*:\s*(.+)/i, key: 'nama_pelanggan', index: 2 },
        { regex: /Lokasi\s*:\s*(.+)/i, key: 'lokasi' },
        { regex: /Waktu\s*Mulai\s*:\s*(\d{1,2}:\d{2})/i, key: 'waktu_mulai' },
        { regex: /Waktu\s*Selesai\s*:\s*(\d{1,2}:\d{2})/i, key: 'waktu_selesai' },
        { regex: /Indikasi\s*(?:Case)?\s*:\s*(.+)/i, key: 'indikasi_case' },
        { regex: /Action\s*:\s*(.+)/i, key: 'action' },
        { regex: /Status\s*ticket\s*:\s*(.+)/i, key: 'status_tiket' }
    ];

    lines.forEach(line => {
        if (line.trim()) {
            for (const pattern of patterns) {
                const match = line.match(pattern.regex);
                if (match) {
                    const valueIndex = pattern.index || 1;
                    result[pattern.key] = match[valueIndex].trim();
                    break;
                }
            }
        }
    });

    return result;
}

// Format tanggal dari berbagai format ke YYYY-MM-DD
function formatDate(inputDate) {
    const dateFormats = [
        { regex: /(\d{2})[\/-](\d{2})[\/-](\d{4})/, parts: [3, 2, 1] }, // DD/MM/YYYY atau DD-MM-YYYY
        { regex: /(\d{2})\s+(\w+)\s+(\d{4})/, parts: [3, 2, 1], months: {
            'Januari': '01', 'Februari': '02', 'Maret': '03', 'April': '04',
            'Mei': '05', 'Juni': '06', 'Juli': '07', 'Agustus': '08',
            'September': '09', 'Oktober': '10', 'November': '11', 'Desember': '12'
        }},
        { regex: /(\d{4})[\/-](\d{2})[\/-](\d{2})/, parts: [1, 2, 3] } // YYYY/MM/DD atau YYYY-MM-DD
    ];

    for (const format of dateFormats) {
        const match = inputDate.match(format.regex);
        if (match) {
            let year, month, day;
            
            if (format.months) {
                year = match[format.parts[0]];
                month = format.months[match[format.parts[1]]];
                day = match[format.parts[2]].padStart(2, '0');
            } else {
                year = match[format.parts[0]];
                month = match[format.parts[1]].padStart(2, '0');
                day = match[format.parts[2]].padStart(2, '0');
            }
            
            return `${year}-${month}-${day}`;
        }
    }
    
    return inputDate; // Return as-is jika tidak match
}

// Hitung durasi otomatis
function hitungDurasi() {
    const start = document.getElementById('waktu_mulai').value;
    const end = document.getElementById('waktu_selesai').value;
    
    if (start && end) {
        const [startH, startM] = start.split(':').map(Number);
        const [endH, endM] = end.split(':').map(Number);
        
        let totalMins = (endH * 60 + endM) - (startH * 60 + startM);
        if (totalMins < 0) totalMins += 1440; // Tambah 24 jam jika negatif
        
        const hours = Math.floor(totalMins / 60);
        const mins = totalMins % 60;
        document.getElementById('total_waktu').value = 
            `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:00`;
    }
}

// Event listener untuk tombol konversi
document.getElementById('parseBtn').addEventListener('click', function() {
    const text = document.getElementById('pasteData').value;
    if (!text.trim()) {
        alert('Silakan tempel data teknisi terlebih dahulu');
        return;
    }

    const data = parseTechnicianText(text);
    
    // Isi form dengan data yang telah diparse
    Object.keys(data).forEach(key => {
        const element = document.querySelector(`[name="${key}"]`);
        if (element) {
            // Format khusus untuk tanggal
            if (key === 'tanggal_mulai') {
                element.value = formatDate(data[key]);
            } 
            // Handle select dropdown
            else if (element.tagName === 'SELECT') {
                const option = Array.from(element.options).find(opt => 
                    opt.text.toLowerCase().includes(data[key].toLowerCase()));
                if (option) option.selected = true;
            }
            else {
                element.value = data[key];
            }
        }
    });
    
    // Auto hitung durasi
    hitungDurasi();
    
    // Beri feedback
    alert('Data berhasil diisi otomatis! Silakan cek dan simpan.');
});

// Event listeners untuk perhitungan durasi
['waktu_mulai', 'waktu_selesai'].forEach(id => {
    document.getElementById(id).addEventListener('change', hitungDurasi);
});

// Form submission handler
document.getElementById('workForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    document.getElementById('submitText').textContent = 'Menyimpan...';
    document.getElementById('submitSpinner').classList.remove('d-none');
});
</script>
</body>
</html>
