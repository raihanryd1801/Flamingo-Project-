<?php
session_start();
require '../config/db.php';

// Ambil data berdasarkan ID
$id = $_GET['id'] ?? 0;
$stmt = $koneksi->prepare("SELECT * FROM dailywork WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: laporan.php");
    exit;
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'jenis_pekerjaan', 'request_by', 'id_pelanggan', 'nama_pelanggan',
        'nama_teknisi', 'lokasi', 'site', 'waktu_mulai', 'waktu_selesai', 
        'total_waktu', 'indikasi_case', 'action', 'status_tiket', 'catatan'
    ];
    
    $params = [];
    $types = str_repeat('s', count($fields)) . 'i'; // semua string + id integer
    
    foreach ($fields as $field) {
        $params[] = $_POST[$field];
    }
    $params[] = $id;
    
    $sql = "UPDATE dailywork SET " . 
           implode('=?, ', $fields) . "=? WHERE id = ?";
    
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Data berhasil diperbarui";
        header("Location: laporan.php");
        exit;
    } else {
        $error = "Gagal memperbarui data: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Daily Work</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --aquadark: #006d77;
            --aqualight: #83c5be;
        }
        body {
            background-color: #f0f5f5;
        }
        .edit-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: 600;
        }
        .btn-submit {
            background-color: var(--aquadark);
            border-color: var(--aquadark);
        }
        .btn-submit:hover {
            background-color: #005f69;
            border-color: #005f69;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <h2 class="mb-4 text-center"><i class="bi bi-pencil-square"></i> Edit Data Daily Work</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Jenis Pekerjaan</label>
                    <input type="text" class="form-control" name="jenis_pekerjaan" value="<?= htmlspecialchars($data['jenis_pekerjaan']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Request By</label>
                    <input type="text" class="form-control" name="request_by" value="<?= htmlspecialchars($data['request_by']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">ID Pelanggan</label>
                    <input type="text" class="form-control" name="id_pelanggan" value="<?= htmlspecialchars($data['id_pelanggan']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Pelanggan</label>
                    <input type="text" class="form-control" name="nama_pelanggan" value="<?= htmlspecialchars($data['nama_pelanggan']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Nama Teknisi</label>
                    <input type="text" class="form-control" name="nama_teknisi" value="<?= htmlspecialchars($data['nama_teknisi']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Site</label>
                    <input type="text" class="form-control" name="site" value="<?= htmlspecialchars($data['site']) ?>">
                </div>
                
                <div class="col-12">
                    <label class="form-label">Lokasi</label>
                    <textarea class="form-control" name="lokasi" rows="2" required><?= htmlspecialchars($data['lokasi']) ?></textarea>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Waktu Mulai</label>
                    <input type="datetime-local" class="form-control" name="waktu_mulai" value="<?= str_replace(' ', 'T', $data['waktu_mulai']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Waktu Selesai</label>
                    <input type="datetime-local" class="form-control" name="waktu_selesai" value="<?= str_replace(' ', 'T', $data['waktu_selesai']) ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Total Waktu</label>
                    <input type="text" class="form-control" name="total_waktu" value="<?= htmlspecialchars($data['total_waktu']) ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status Tiket</label>
                    <select class="form-select" name="status_tiket" required>
                        <option value="Open" <?= $data['status_tiket'] === 'Open' ? 'selected' : '' ?>>Open</option>
                        <option value="Closed" <?= $data['status_tiket'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
                        <option value="Pending" <?= $data['status_tiket'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Dalam Proses" <?= $data['status_tiket'] === 'Dalam Proses' ? 'selected' : '' ?>>Dalam Proses</option>
                        <option value="Dalam Monitoring" <?= $data['status_tiket'] === 'Dalam Monitoring' ? 'selected' : '' ?>>Dalam Monitoring</option>
                        <option value="Cancel" <?= $data['status_tiket'] === 'Cancel' ? 'selected' : '' ?>>Cancel</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Indikasi Case</label>
                    <textarea class="form-control" name="indikasi_case" rows="3"><?= htmlspecialchars($data['indikasi_case']) ?></textarea>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Action</label>
                    <textarea class="form-control" name="action" rows="3"><?= htmlspecialchars($data['action']) ?></textarea>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-control" name="catatan" rows="3"><?= htmlspecialchars($data['catatan']) ?></textarea>
                </div>
                
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-submit text-white me-2">
                        <i class="bi bi-save"></i> Simpan Perubahan
                    </button>
                    <a href="laporan.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Hitung total waktu saat waktu mulai/selesai berubah
        document.addEventListener('DOMContentLoaded', function() {
            const waktuMulai = document.querySelector('input[name="waktu_mulai"]');
            const waktuSelesai = document.querySelector('input[name="waktu_selesai"]');
            const totalWaktu = document.querySelector('input[name="total_waktu"]');
            
            function hitungDurasi() {
                if (waktuMulai.value && waktuSelesai.value) {
                    const mulai = new Date(waktuMulai.value);
                    const selesai = new Date(waktuSelesai.value);
                    
                    let selisih = (selesai - mulai) / 1000; // dalam detik
                    
                    if (selisih < 0) {
                        totalWaktu.value = 'Invalid';
                        return;
                    }
                    
                    const jam = String(Math.floor(selisih / 3600)).padStart(2, '0');
                    const menit = String(Math.floor((selisih % 3600) / 60)).padStart(2, '0');
                    const detik = String(Math.floor(selisih % 60)).padStart(2, '0');
                    
                    totalWaktu.value = `${jam}:${menit}:${detik}`;
                }
            }
            
            waktuMulai.addEventListener('change', hitungDurasi);
            waktuSelesai.addEventListener('change', hitungDurasi);
        });
    </script>
</body>
</html>