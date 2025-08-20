<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Optional: Batasi role yang boleh akses halaman ini
if ($_SESSION['role'] !== 'administrator' && $_SESSION['role'] !== 'admin_it') {
    header("Location: ../unauthorized.php");
    exit();
}

// Ambil data log
$logs = [];
$result = $koneksi->query("SELECT * FROM history_log ORDER BY created_at DESC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>History Log | NOC Optimized v3.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body style="background:#f8f9fa; padding:20px;">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-clock-history me-2"></i>History Log</h4>
        <a href="../index.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left"></i> Back to Menu</a>
    </div>

    <?php if (empty($logs)): ?>
        <div class="alert alert-warning">Belum ada aktivitas yang tercatat.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $index => $log): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($log['username']) ?></td>
                            <td><?= htmlspecialchars($log['role']) ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= htmlspecialchars($log['description']) ?></td>
                            <td><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
