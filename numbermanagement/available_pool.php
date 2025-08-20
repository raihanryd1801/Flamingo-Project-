<?php
include '../config/db.php';
session_start();

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


// Handle export CSV
if (isset($_GET['export']) && $_GET['export'] == 1) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=available_pool.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Operator', 'Nomor', 'Release Date']);

    $where = [];
    if (!empty($_GET['search'])) {
        $search = $koneksi->real_escape_string($_GET['search']);
        $where[] = "(pn.phone_number LIKE '%$search%')";
    }
    if (!empty($_GET['operator_id'])) {
        $where[] = "pn.operator_id = " . intval($_GET['operator_id']);
    }
    $where[] = "(pn.prefix='' OR pn.prefix IS NULL) AND (pn.client_name='' OR pn.client_name IS NULL)";
    $where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $query = "
        SELECT pn.id, o.name as operator_name, pn.phone_number, pn.release_date
        FROM phone_numbers pn
        LEFT JOIN operators o ON pn.operator_id = o.id
        $where_sql
        ORDER BY pn.phone_number ASC
    ";
    $result = $koneksi->query($query);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['id'], $row['operator_name'], $row['phone_number'], $row['release_date']]);
    }
    fclose($output);
    exit();
}

// Get total count for summary
$total_query = $koneksi->query("
    SELECT COUNT(*) as total 
    FROM phone_numbers pn 
    WHERE (pn.prefix='' OR pn.prefix IS NULL) AND (pn.client_name='' OR pn.client_name IS NULL)
");
$total_numbers = $total_query->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise 11.2 - Available Pool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            font-weight: 600;
            background-color: #6c757d;
            color: white;
        }
        .page-title {
            color: #343a40;
            border-bottom: 2px solid #6c757d;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .pagination .page-item.active .page-link {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .summary-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">
                <i class="bi bi-database"></i> Available Number Pool
            </h1>
            <div>
                <span class="badge bg-primary summary-badge">
                    <i class="bi bi"></i> Total: <?= number_format($total_numbers) ?>
                </span>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-funnel"></i> Filter Options
            </div>
            <div class="card-body">
                <form class="row g-3" method="GET">
                    <div class="col-md-4">
                        <label class="form-label">Operator</label>
                        <select name="operator_id" class="form-select">
                            <option value="">All Operators</option>
                            <?php
                            $result = $koneksi->query("SELECT * FROM operators ORDER BY name");
                            while ($row = $result->fetch_assoc()):
                                $selected = (isset($_GET['operator_id']) && $_GET['operator_id'] == $row['id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $row['id'] ?>" <?= $selected ?>><?= $row['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search phone numbers..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-grid gap-2 d-md-flex">
                            <button class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Apply Filter
                            </button>
                            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel"></i> Export CSV
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        // Pagination Logic
        $per_page = 20;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $per_page;

        $where = [];
        if (!empty($_GET['search'])) {
            $search = $koneksi->real_escape_string($_GET['search']);
            $where[] = "(pn.phone_number LIKE '%$search%')";
        }
        if (!empty($_GET['operator_id'])) {
            $where[] = "pn.operator_id = " . intval($_GET['operator_id']);
        }
        $where[] = "(pn.prefix='' OR pn.prefix IS NULL) AND (pn.client_name='' OR pn.client_name IS NULL)";
        $where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = $koneksi->query("
            SELECT COUNT(*) as total 
            FROM phone_numbers pn 
            LEFT JOIN operators o ON pn.operator_id = o.id 
            $where_sql
        ")->fetch_assoc()['total'];

        $query = "
            SELECT pn.id, o.name as operator_name, pn.phone_number, pn.release_date
            FROM phone_numbers pn
            LEFT JOIN operators o ON pn.operator_id = o.id
            $where_sql
            ORDER BY pn.phone_number ASC
            LIMIT $offset, $per_page
        ";
        $result = $koneksi->query($query);
        ?>

        <!-- Results Section -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ol"></i> Available Numbers
                <span class="float-end badge bg-primary">
                    Showing <?= min($per_page, $total - $offset) ?> of <?= number_format($total) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Operator</th>
                                <th>Phone Number</th>
                                <th>Release Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= $row['operator_name'] ?></td>
                                <td>
                                    <span class="font-monospace"><?= $row['phone_number'] ?></span>
                                </td>
                                <td>
                                    <?= $row['release_date'] ? date('d M Y', strtotime($row['release_date'])) : '-' ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total > $per_page): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php
                        $total_pages = ceil($total / $per_page);
                        $visible_pages = 5;
                        $start_page = max(1, $page - floor($visible_pages / 2));
                        $end_page = min($total_pages, $start_page + $visible_pages - 1);
                        
                        // Adjust if we're at the beginning or end
                        if ($end_page - $start_page + 1 < $visible_pages) {
                            $start_page = max(1, $end_page - $visible_pages + 1);
                        }
                        
                        if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif;
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor;
                        
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
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus search field on page load
        document.addEventListener('DOMContentLoaded', function() {
            const searchField = document.querySelector('input[name="search"]');
            if (searchField) {
                searchField.focus();
            }
        });
    </script>
</body>
</html>