<?php
include '../config/db.php';
session_start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Mengatur hak akses  
if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] == 1) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=number_export_'.date('Ymd').'.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Operator', 'Phone Number', 'Prefix', 'Client', 'Release Date', 'Status']);
    
    // Build query conditions
    $conditions = [];
    if (!empty($_GET['search'])) {
        $search = $koneksi->real_escape_string($_GET['search']);
        $conditions[] = "(pn.phone_number LIKE '%$search%' OR pn.prefix LIKE '%$search%' OR pn.client_name LIKE '%$search%')";
    }
    if (!empty($_GET['operator_id'])) {
        $conditions[] = "pn.operator_id = " . intval($_GET['operator_id']);
    }
    $conditions[] = "pn.prefix != '' AND pn.client_name != ''";
    $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Execute export query
    $export_query = $koneksi->query("
        SELECT pn.id, o.name as operator_name, pn.phone_number, pn.prefix, 
               pn.client_name, pn.release_date,
               CASE 
                   WHEN pn.release_date IS NULL THEN 'Active'
                   WHEN pn.release_date > CURDATE() THEN 'Pending Release'
                   ELSE 'Released'
               END as status
        FROM phone_numbers pn
        LEFT JOIN operators o ON pn.operator_id = o.id
        $where
        ORDER BY pn.phone_number ASC
    ");
    
    while ($row = $export_query->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Get summary statistics
$stats = $koneksi->query("
    SELECT 
        SUM(CASE WHEN (prefix='' OR prefix IS NULL) AND (client_name='' OR client_name IS NULL) THEN 1 ELSE 0 END) as available,
        SUM(CASE WHEN prefix IS NOT NULL AND client_name IS NOT NULL AND prefix<>'' AND client_name<>'' THEN 1 ELSE 0 END) as assigned,
        COUNT(*) as total
    FROM phone_numbers
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Number Management Dashboard | NOC 11.0</title>
    
    <!-- Bootstrap 5 with Dark Mode -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Google Fonts - Inter for better readability -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Loading overlay -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-loading-overlay/2.1.7/loadingoverlay.min.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --font-base: 15px;
            --font-scale: 1.2;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: var(--font-base);
            line-height: 1.6;
            color: #333;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            line-height: 1.3;
        }
        
        h1 { font-size: calc(var(--font-base) * var(--font-scale) * 1.5); }
        h2 { font-size: calc(var(--font-base) * var(--font-scale) * 1.3); }
        h3 { font-size: calc(var(--font-base) * var(--font-scale) * 1.15); }
        h4 { font-size: calc(var(--font-base) * var(--font-scale)); }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(67, 97, 238, 0.15);
            margin-bottom: 2rem;
        }
        
        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.2;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover i {
            opacity: 0.3;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        
        .btn {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.4em 0.7em;
        }
        
        /* Improved table styling */
        .table {
            font-size: 0.95rem;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }
        
        /* Better pagination */
        .page-link {
            padding: 0.5rem 0.75rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                font-size: calc(var(--font-base) * 0.9);
            }
            
            .btn {
                padding: 0.4rem 0.8rem;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <?php if (isset($_GET['success']) || isset($_GET['duplicate'])): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?= intval($_GET['success']) ?> numbers were successfully added.
                    <?php if (isset($_GET['duplicate']) && $_GET['duplicate'] > 0): ?>
                        <div class="text-white-50"><?= intval($_GET['duplicate']) ?> duplicates were skipped.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="container-fluid py-4">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1"><i class="bi bi-phone me-2"></i>NUMBER MANAGEMENT DANKOM</h1>
                    <p class="mb-0 opacity-85">Comprehensive number management system</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/index.php" class="btn btn-outline-light">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                    <a href="list_client.php" class="btn btn-outline-light">
                        <i class="bi bi-people-fill me-1"></i> Clients
                    </a>
                    <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#helpModal">
                        <i class="bi bi-question-circle me-1"></i> Help
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card bg-white h-100">
                    <div class="card-body">
                        <i class="bi bi-database text-primary float-end"></i>
                        <h6 class="text-uppercase text-muted mb-2">Available Pool</h6>
                        <h2 class="mb-0"><?= number_format($stats['available']) ?></h2>
                        <p class="text-muted mb-0">Unassigned numbers</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card bg-white h-100">
                    <div class="card-body">
                        <i class="bi bi-check-circle text-success float-end"></i>
                        <h6 class="text-uppercase text-muted mb-2">Assigned</h6>
                        <h2 class="mb-0"><?= number_format($stats['assigned']) ?></h2>
                        <p class="text-muted mb-0">Active assignments</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card bg-white h-100">
                    <div class="card-body">
                        <i class="bi bi-phone text-info float-end"></i>
                        <h6 class="text-uppercase text-muted mb-2">Total Numbers</h6>
                        <h2 class="mb-0"><?= number_format($stats['total']) ?></h2>
                        <p class="text-muted mb-0">In system</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Numbers Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-plus-circle me-2"></i> Add New Numbers
                </div>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#addNumbersCollapse">
                    <i class="bi bi-chevron-down"></i> Toggle
                </button>
            </div>
            <div class="card-body collapse show" id="addNumbersCollapse">
                <form action="../process/update_nomor.php" method="POST" id="addNumbersForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Operator <span class="text-danger">*</span></label>
                            <select name="operator_id" class="form-select" required>
                                <option value="">Select Operator</option>
                                <?php
                                $operators = $koneksi->query("SELECT * FROM operators ORDER BY name");
                                while ($operator = $operators->fetch_assoc()):
                                ?>
                                    <option value="<?= $operator['id'] ?>"><?= $operator['name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Release Date</label>
                            <input type="date" name="release_date" class="form-control">
                            <small class="text-muted">Leave empty for no release date</small>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Phone Numbers <span class="text-danger">*</span></label>
                            <textarea name="phone_numbers" class="form-control number-input" rows="5" 
                                      placeholder="Enter numbers, one per line (e.g., 628123456789)" required></textarea>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">Format: 628xxxxxxxxx (without spaces or special characters)</small>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="formatNumbersBtn">
                                    <i class="bi bi-magic me-1"></i> Auto-Format
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-12 mt-3">
                            <button type="submit" class="btn btn-primary px-4" id="submitNumbersBtn">
                                <i class="bi bi-save me-1"></i> Save Numbers
                            </button>
                            <button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#sampleModal">
                                <i class="bi bi-file-earmark-text me-1"></i> View Sample
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pool Summary Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-database me-2"></i> Available Number Pool Summary
                </div>
                <div>
                    <a href="available_pool.php" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-eye me-1"></i> View All
                    </a>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#poolCollapse">
                        <i class="bi bi-chevron-down"></i> Toggle
                    </button>
                </div>
            </div>
            <div class="card-body collapse show" id="poolCollapse">
                <div class="table-responsive">
                    <table class="table table-hover" id="poolTable">
                        <thead>
                            <tr>
                                <th>Operator</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pool_query = $koneksi->query("
                                SELECT o.id, o.name, COUNT(pn.id) AS total_pool
                                FROM operators o
                                LEFT JOIN phone_numbers pn 
                                    ON pn.operator_id = o.id AND (pn.prefix='' OR pn.prefix IS NULL) AND (pn.client_name='' OR pn.client_name IS NULL)
                                GROUP BY o.id
                                ORDER BY o.name
                            ");
                            
                            while ($pool = $pool_query->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $pool['name'] ?></td>
                                <td>
                                    <span class="badge bg-primary rounded-pill"><?= $pool['total_pool'] ?></span>
                                </td>
                                <td>
                                    <form action="bulk_assign.php" method="GET">
                                        <input type="hidden" name="operator_id" value="<?= $pool['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="bi bi-cloud-arrow-up me-1"></i> Bulk Assign
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Assigned Numbers Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-list-check me-2"></i> Assigned Numbers
                </div>
                <div>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>" class="btn btn-sm btn-success me-2">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export
                    </a>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#assignedCollapse">
                        <i class="bi bi-chevron-down"></i> Toggle
                    </button>
                </div>
            </div>
            
            <div class="card-body collapse show" id="assignedCollapse">
                <!-- Filter Form -->
                <form class="row g-3 mb-4" method="GET" id="filterForm">
                    <input type="hidden" name="page" value="1">
                    
                    <div class="col-md-4">
                        <label class="form-label">Operator</label>
                        <select name="operator_id" class="form-select">
                            <option value="">All Operators</option>
                            <?php
                            $operators = $koneksi->query("SELECT * FROM operators ORDER BY name");
                            while ($operator = $operators->fetch_assoc()):
                                $selected = (isset($_GET['operator_id']) && $_GET['operator_id'] == $operator['id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $operator['id'] ?>" <?= $selected ?>><?= $operator['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by number, prefix, or client" 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-secondary" id="clearSearchBtn">
                                <i class="bi bi-x"></i> Clear
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Apply Filters
                        </button>
                    </div>
                </form>
                
                <!-- Assigned Numbers Table -->
                <?php
                // Pagination Logic
                $per_page = 15;
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $offset = ($page - 1) * $per_page;

                // Build conditions
                $conditions = [];
                if (!empty($_GET['search'])) {
                    $search = $koneksi->real_escape_string($_GET['search']);
                    $conditions[] = "(pn.phone_number LIKE '%$search%' OR pn.prefix LIKE '%$search%' OR pn.client_name LIKE '%$search%')";
                }
                if (!empty($_GET['operator_id'])) {
                    $conditions[] = "pn.operator_id = " . intval($_GET['operator_id']);
                }
                $conditions[] = "pn.prefix != '' AND pn.client_name != ''";
                $where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

                // Get total count
                $total_query = $koneksi->query("
                    SELECT COUNT(*) as total 
                    FROM phone_numbers pn 
                    LEFT JOIN operators o ON pn.operator_id = o.id 
                    $where
                ");
                $total = $total_query->fetch_assoc()['total'];
                $total_pages = ceil($total / $per_page);

                // Get paginated results
                $query = "
                    SELECT pn.id, o.name as operator_name, pn.phone_number, pn.prefix, 
                           pn.client_name, pn.release_date, pn.is_terminated,
                           CASE 
                               WHEN pn.is_terminated = 1 THEN 'Terminated'
                               WHEN pn.release_date IS NULL THEN 'Active'
                               WHEN pn.release_date > CURDATE() THEN 'Pending Release'
                               ELSE 'In Use'
                           END as status
                    FROM phone_numbers pn
                    LEFT JOIN operators o ON pn.operator_id = o.id
                    $where
                    ORDER BY pn.phone_number ASC
                    LIMIT $offset, $per_page
                ";
                $result = $koneksi->query($query);
                ?>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="assignedNumbersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Operator</th>
                                <th>Number</th>
                                <th>Prefix</th>
                                <th>Client</th>
                                <th>Status</th>
                                <th>Release Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= $row['operator_name'] ?></td>
                                    <td class="number-input"><?= $row['phone_number'] ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $row['prefix'] ?></span>
                                    </td>
                                    <td><?= $row['client_name'] ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'Active'): ?>
                                            <span class="badge bg-secondary"><?= $row['status'] ?></span>
                                        <?php elseif ($row['status'] == 'Pending Release'): ?>
                                            <span class="badge bg-warning text-dark"><?= $row['status'] ?></span>
                                        <?php elseif ($row['status'] == 'Terminated'): ?>
                                            <span class="badge bg-danger"><?= $row['status'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?= $row['status'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $row['release_date'] ? date('d M Y', strtotime($row['release_date'])) : '-' ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="edit_nomor.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" 
                                               data-bs-toggle="tooltip" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="../process/delete_nomor.php?id=<?= $row['id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               data-bs-toggle="tooltip" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this number?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-center py-4">
                                        <i class="bi bi-exclamation-circle text-muted" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">No numbers found</h5>
                                        <p class="text-muted">Try adjusting your search filters</p>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <?php
                        $visible_pages = 5;
                        $start_page = max(1, $page - floor($visible_pages / 2));
                        $end_page = min($total_pages, $start_page + $visible_pages - 1);
                        
                        if ($end_page - $start_page + 1 < $visible_pages) {
                            $start_page = max(1, $end_page - $visible_pages + 1);
                        }
                        
                        // Previous button
                        if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif;
                        
                        // First page
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif;
                        endif;
                        
                        // Page numbers
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor;
                        
                        // Last page
                        if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif;
                        
                        // Next button
                        if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="helpModalLabel"><i class="bi bi-question-circle me-2"></i> Number Management Help</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="accordion" id="helpAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#helpAddNumbers">
                                    <i class="bi bi-plus-circle me-2"></i> Adding Numbers
                                </button>
                            </h2>
                            <div id="helpAddNumbers" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    <p>To add new numbers to the system:</p>
                                    <ol>
                                        <li>Select the operator from the dropdown</li>
                                        <li>Optionally set a release date (for temporary assignments)</li>
                                        <li>Enter phone numbers in the text area, one per line</li>
                                        <li>Click "Save Numbers" to submit</li>
                                    </ol>
                                    <p><strong>Format requirements:</strong></p>
                                    <ul>
                                        <li>Numbers should start with country code (e.g., 62 for Indonesia)</li>
                                        <li>No spaces, dashes, or special characters</li>
                                        <li>Example: 628123456789</li>
                                    </ul>
                                    <p>Use the "Auto-Format" button to clean up pasted numbers.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpManageNumbers">
                                    <i class="bi bi-list-check me-2"></i> Managing Numbers
                                </button>
                            </h2>
                            <div id="helpManageNumbers" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    <p>You can manage existing numbers through the Assigned Numbers table:</p>
                                    <ul>
                                        <li><strong>Edit:</strong> Click the pencil icon to modify number details</li>
                                        <li><strong>Delete:</strong> Click the trash icon to remove a number (requires confirmation)</li>
                                        <li><strong>Filter:</strong> Use the search and operator filters to find specific numbers</li>
                                        <li><strong>Export:</strong> Click the "Export" button to download data as CSV</li>
                                    </ul>
                                    <p>Status indicators:</p>
                                    <ul>
                                        <li><span class="badge bg-secondary">Active</span> - Currently assigned with no release date</li>
                                        <li><span class="badge bg-warning text-dark">Pending Release</span> - Scheduled for future release</li>
                                        <li><span class="badge bg-danger">Terminated</span> - Number has been terminated</li>
                                        <li><span class="badge bg-success">In Use</span> - Currently in use (past release date)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sample Numbers Modal -->
    <div class="modal fade" id="sampleModal" tabindex="-1" aria-labelledby="sampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="sampleModalLabel"><i class="bi bi-file-earmark-text me-2"></i> Sample Number Format</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Here's an example of correctly formatted numbers:</p>
                    <pre class="bg-light p-3 rounded">628123456789
628987654321
628112233445
628556677889
628998877665</pre>
                    <p class="mt-3">You can copy and paste this format directly into the input field.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="copySampleBtn">
                        <i class="bi bi-clipboard me-1"></i> Copy Sample
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-loading-overlay/2.1.7/loadingoverlay.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables with proper configuration
            $('#assignedNumbersTable').DataTable({
                responsive: true,
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                pageLength: 10,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                    emptyTable: "No numbers found"
                },
                paging: false,
                searching: false, // Disable DataTables search since we have our own
                info: false,
                columns: [
                    { data: 'id' },
                    { data: 'operator' },
                    { data: 'number' },
                    { data: 'prefix' },
                    { data: 'client' },
                    { data: 'status' },
                    { data: 'release_date' },
                    { data: 'actions', orderable: false }
                ]
            });

            $('#poolTable').DataTable({
                responsive: true,
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                pageLength: 10,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                },
                paging: false
            });
            
            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize toasts
            const toastElList = document.querySelectorAll('.toast');
            const toastList = [...toastElList].map(toastEl => {
                return new bootstrap.Toast(toastEl);
            });
            
            // Auto-focus search field
            $('input[name="search"]').focus();
            
            // Form validation
            $('#addNumbersForm').on('submit', function(e) {
                const phoneNumbers = $('textarea[name="phone_numbers"]').val().trim();
                if (!phoneNumbers) {
                    e.preventDefault();
                    showAlert('Please enter at least one phone number', 'danger');
                    return false;
                }
                
                // Show loading indicator
                $.LoadingOverlay("show", {
                    background: "rgba(0, 0, 0, 0.5)",
                    image: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                });
                
                return true;
            });
            
            // Clear search button
            $('#clearSearchBtn').on('click', function() {
                $('input[name="search"]').val('');
                $('select[name="operator_id"]').val('');
                $('#filterForm').submit();
            });
            
            // Auto-format numbers
            $('#formatNumbersBtn').on('click', function() {
                let numbers = $('textarea[name="phone_numbers"]').val();
                
                // Remove all non-digit characters
                numbers = numbers.replace(/[^\d\n]/g, '');
                
                // Remove empty lines
                numbers = numbers.split('\n')
                    .filter(line => line.trim() !== '')
                    .join('\n');
                
                $('textarea[name="phone_numbers"]').val(numbers);
                showAlert('Numbers formatted successfully', 'success');
            });
            
            // Copy sample numbers
            $('#copySampleBtn').on('click', function() {
                const sampleText = `628123456789
628987654321
628112233445
628556677889
628998877665`;
                
                navigator.clipboard.writeText(sampleText).then(function() {
                    showAlert('Sample copied to clipboard!', 'success');
                    $('#sampleModal').modal('hide');
                }, function() {
                    showAlert('Failed to copy sample', 'danger');
                });
            });
            
            // Show alert function
            function showAlert(message, type) {
                const alertHtml = `
                    <div class="toast show align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                $('.toast-container').append(alertHtml);
                
                // Auto-hide after 3 seconds
                setTimeout(function() {
                    $('.toast-container .toast').last().remove();
                }, 3000);
            }
            
            // Show loading spinner when exporting
            $('a[href*="export=1"]').on('click', function() {
                $.LoadingOverlay("show", {
                    background: "rgba(0, 0, 0, 0.5)",
                    image: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                });
            });
            
            // Collapse/expand sections based on localStorage
            $('.collapse').on('shown.bs.collapse', function() {
                localStorage.setItem(`collapse_${this.id}`, 'show');
            });
            
            $('.collapse').on('hidden.bs.collapse', function() {
                localStorage.setItem(`collapse_${this.id}`, 'hide');
            });
            
            // Initialize collapse state from localStorage
            $('.collapse').each(function() {
                const state = localStorage.getItem(`collapse_${this.id}`);
                if (state === 'hide') {
                    $(this).collapse('hide');
                }
            });
            
            // Reset to page 1 when filter form is submitted
            $('#filterForm').on('submit', function() {
                $(this).find('input[name="page"]').val(1);
            });
        });
    </script>
</body>
</html>