<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['assigned_ids']) || empty($_SESSION['assigned_ids'])) {
    die("Tidak ada data assign ditemukan.");
}

$assigned_ids = $_SESSION['assigned_ids'];
$id_list = implode(",", array_map('intval', $assigned_ids));

$query = "
    SELECT phone_number 
    FROM phone_numbers 
    WHERE id IN ($id_list)
    ORDER BY phone_number ASC
";
$result = $koneksi->query($query);

// Store numbers in array for multiple use
$phone_numbers = [];
while($row = $result->fetch_assoc()) {
    $phone_numbers[] = $row['phone_number'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Assign Nomor | NOC 11.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .result-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-top: 2rem;
        }
        .number-textarea {
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.9rem;
            border-radius: 8px;
            background-color: #f8f9fa;
            padding: 1rem;
            min-height: 300px;
        }
        .success-badge {
            background-color: #d1fae5;
            color: #065f46;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }
        .action-buttons .btn {
            min-width: 120px;
        }
        .copy-btn {
            position: absolute;
            right: 15px;
            top: 15px;
            opacity: 0.7;
            transition: all 0.2s;
        }
        .copy-btn:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="result-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    Hasil Assign Nomor
                </h2>
                <span class="success-badge">
                    <i class="bi bi-check-lg me-1"></i>
                    <?= count($assigned_ids) ?> Nomor Berhasil Diassign
                </span>
            </div>
            
            <p class="text-muted mb-4">
                Berikut adalah daftar nomor yang telah berhasil diassign:
            </p>
            
            <div class="position-relative mb-4">
                <textarea id="numbersOutput" class="form-control number-textarea" rows="15" readonly><?= implode("\n", $phone_numbers) ?></textarea>
                <button id="copyButton" class="btn btn-sm btn-outline-secondary copy-btn" title="Salin ke clipboard">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
            
            <div class="d-flex justify-content-between align-items-center action-buttons">
                <a href="../numbermanagement/management_nomor.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Management Nomor
                </a>
                <div>
                    <button id="downloadBtn" class="btn btn-primary me-2">
                        <i class="bi bi-download me-1"></i> Download
                    </button>
                    <button id="printBtn" class="btn btn-secondary">
                        <i class="bi bi-printer me-1"></i> Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Copy to clipboard functionality
        document.getElementById('copyButton').addEventListener('click', function() {
            const textarea = document.getElementById('numbersOutput');
            textarea.select();
            document.execCommand('copy');
            
            // Change button appearance temporarily
            const originalIcon = this.innerHTML;
            this.innerHTML = '<i class="bi bi-check"></i> Tersalin!';
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-success');
            
            setTimeout(() => {
                this.innerHTML = originalIcon;
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-secondary');
            }, 2000);
        });
        
        // Download functionality
        document.getElementById('downloadBtn').addEventListener('click', function() {
            const content = document.getElementById('numbersOutput').value;
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'nomor-assign-' + new Date().toISOString().slice(0,10) + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
        
        // Print functionality
        document.getElementById('printBtn').addEventListener('click', function() {
            const content = document.getElementById('numbersOutput').value;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Cetak Nomor Assign</title>
                        <style>
                            body { font-family: Arial; padding: 20px; }
                            h2 { color: #333; }
                            pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
                        </style>
                    </head>
                    <body>
                        <h2>Nomor Assign</h2>
                        <p>Total: ${<?= count($assigned_ids) ?>} nomor</p>
                        <pre>${content}</pre>
                        <script>
                            window.onload = function() { window.print(); }
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
        });
    </script>
</body>
</html>

<?php
// Hapus session supaya hasil assign tidak muncul terus
unset($_SESSION['assigned_ids']);
?>