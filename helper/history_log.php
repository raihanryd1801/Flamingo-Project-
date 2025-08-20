<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Log | NOC Optimized v3.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --dark-color: #2b2d42;
            --light-color: #f8f9fa;
        }
        
        body {
            background-color: #f5f7ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background: var(--dark-color);
            color: white;
        }
        
        .table th {
            font-weight: 500;
            padding: 1rem;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(72, 149, 239, 0.1);
        }
        
        .action-badge {
            padding: 0.35em 0.65em;
            border-radius: 50px;
            font-size: 0.75em;
            font-weight: 600;
        }
        
        .badge-login {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-update {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .badge-delete {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .badge-create {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        
        .time-col {
            white-space: nowrap;
        }
        
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary-custom:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card-container">
            <div class="header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0"><i class="bi bi-clock-history me-2"></i>Activity Log</h2>
                        <p class="mb-0 opacity-75">Track all system activities and user actions</p>
                    </div>
                    <a href="../index.php" class="btn btn-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            
            <div class="p-4">
                <?php if (empty($logs)): ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x"></i>
                        <h4>No activity recorded</h4>
                        <p>System activities will appear here once available</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th class="time-col">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $index => $log): 
                                    $badgeClass = '';
                                    $action = strtolower($log['action']);
                                    
                                    if (strpos($action, 'login') !== false) {
                                        $badgeClass = 'badge-login';
                                    } elseif (strpos($action, 'update') !== false) {
                                        $badgeClass = 'badge-update';
                                    } elseif (strpos($action, 'delete') !== false) {
                                        $badgeClass = 'badge-delete';
                                    } elseif (strpos($action, 'create') !== false || strpos($action, 'add') !== false) {
                                        $badgeClass = 'badge-create';
                                    }
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($log['username']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['role']) ?></span></td>
                                    <td><span class="action-badge <?= $badgeClass ?>"><?= htmlspecialchars($log['action']) ?></span></td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td class="time-col">
                                        <small class="text-muted"><?= date('d M Y', strtotime($log['created_at'])) ?></small><br>
                                        <small><?= date('H:i:s', strtotime($log['created_at'])) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add subtle animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(10px)';
                row.style.transition = `all 0.3s ease ${index * 0.05}s`;
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 50);
            });
        });
    </script>
</body>

</html>