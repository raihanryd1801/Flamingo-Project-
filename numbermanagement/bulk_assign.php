<?php
include '../config/db.php';
session_start();

include '../helper/history_helper.php'; // Tambahkan ini setelah session_start

logHistory($koneksi, 'ACCESS', 'Mengakses halaman Bulk Assign untuk operator ' . $operator['name']);


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mengatur hak akses  
if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}


if (!isset($_GET['operator_id'])) {
    die("Operator ID tidak ditemukan.");
}

$operator_id = intval($_GET['operator_id']);
$operator = $koneksi->query("SELECT * FROM operators WHERE id = $operator_id")->fetch_assoc();

if (!$operator) {
    die("Operator tidak valid.");
}

// Count available numbers
$pool_result = $koneksi->query("
    SELECT COUNT(*) as total FROM phone_numbers
    WHERE operator_id = $operator_id 
      AND (prefix = '' OR prefix IS NULL)
      AND (client_name = '' OR client_name IS NULL)
");
$total_pool = $pool_result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Assign - <?= htmlspecialchars($operator['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
        .numbers-preview {
            height: 300px;
            font-family: monospace;
        }
        .badge-available {
            background-color: #28a745;
        }
        .operator-header {
            background-color: #6c757d;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="operator-header">
            <h2><i class="bi bi-phone"></i> Bulk Assign - <?= htmlspecialchars($operator['name']) ?></h2>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-cloud-arrow-up"></i> Assign Numbers</h4>
                    </div>
                    <div class="card-body">
                        <form action="../process/assign_nomor.php" method="POST" id="assignForm">
                            <input type="hidden" name="operator_id" value="<?= $operator_id ?>">

                            <div class="mb-3">
                                <label for="prefix" class="form-label">Prefix</label>
                                <input type="text" class="form-control" id="prefix" name="prefix" required>
                                <div class="form-text">Contoh: 0812, 62813, dll</div>
                            </div>

                            <div class="mb-3">
                                <label for="client_name" class="form-label">Client Name</label>
                                <input type="text" class="form-control" id="client_name" name="client_name" required>
                            </div>

                            <ul class="nav nav-tabs mb-3" id="assignModeTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="quantity-tab" data-bs-toggle="tab" data-bs-target="#quantity" type="button" role="tab">By Quantity</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">Manual List</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="assignModeContent">
                                <div class="tab-pane fade show active" id="quantity" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="jumlah" class="form-label">Number of Numbers to Assign</label>
                                        <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" max="<?= $total_pool ?>" value="1">
                                        <div class="form-text">Available: <span class="badge badge-available"><?= $total_pool ?> numbers</span></div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="manual" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="manual_numbers" class="form-label">Manual Number List</label>
                                        <textarea class="form-control numbers-preview" id="manual_numbers" name="manual_numbers" placeholder="Enter numbers one per line..."></textarea>
                                        <div class="form-text">Enter one number per line. Numbers not in the pool will be skipped.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-save"></i> Assign Numbers
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="bi bi-list-ol"></i> Available Number Pool</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($total_pool > 0): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> There are <strong><?= $total_pool ?></strong> available numbers in the pool.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Preview of available numbers:</label>
                                <textarea class="form-control numbers-preview" readonly>
<?php
$preview_result = $koneksi->query("
    SELECT phone_number FROM phone_numbers
    WHERE operator_id = $operator_id 
      AND (prefix = '' OR prefix IS NULL)
      AND (client_name = '' OR client_name IS NULL)
    ORDER BY phone_number ASC
    LIMIT 500
");

while ($row = $preview_result->fetch_assoc()) {
    echo $row['phone_number'] . "\n";
}
?>
                                </textarea>
                                <div class="form-text">Showing first 500 numbers. Total: <?= $total_pool ?> numbers.</div>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="export_pool.php?operator_id=<?= $operator_id ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-download"></i> Export Full List
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> No available numbers in the pool.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <a href="management_nomor.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Number Management
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('assignForm').addEventListener('submit', function(e) {
            const quantityMode = document.getElementById('quantity-tab').classList.contains('active');
            const manualMode = document.getElementById('manual-tab').classList.contains('active');
            
            if (quantityMode) {
                const jumlah = document.getElementById('jumlah').value;
                if (jumlah < 1) {
                    alert('Please enter a valid quantity (at least 1)');
                    e.preventDefault();
                }
            } else if (manualMode) {
                const manualNumbers = document.getElementById('manual_numbers').value.trim();
                if (manualNumbers === '') {
                    alert('Please enter at least one number in the manual list');
                    e.preventDefault();
                }
            }
        });

        // Tab switching logic
        document.getElementById('quantity-tab').addEventListener('click', function() {
            document.getElementById('manual_numbers').required = false;
            document.getElementById('jumlah').required = true;
        });

        document.getElementById('manual-tab').addEventListener('click', function() {
            document.getElementById('jumlah').required = false;
            document.getElementById('manual_numbers').required = true;
        });
    </script>
</body>
</html>