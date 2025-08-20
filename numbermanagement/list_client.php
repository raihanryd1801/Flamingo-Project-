<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Mengatur hak akses  
if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$search = trim($_GET['search'] ?? '');
$prefixFilter = trim($_GET['prefix'] ?? '');
$searchCondition = '';

if ($search !== '') {
    $keywords = preg_split('/\s+/', $search);
    $conditions = [];
    foreach ($keywords as $word) {
        $safeWord = $koneksi->real_escape_string($word);
        $conditions[] = "(pn.client_name LIKE '%$safeWord%' OR o.name LIKE '%$safeWord%' OR pn.phone_number LIKE '%$safeWord%')";
    }
    $searchCondition .= ' AND ' . implode(' AND ', $conditions);
}

if ($prefixFilter !== '') {
    $safePrefix = $koneksi->real_escape_string($prefixFilter);
    $searchCondition .= " AND pn.prefix = '$safePrefix'";
}

$countSql = "
    SELECT COUNT(DISTINCT pn.client_name, pn.prefix) AS total
    FROM phone_numbers pn
    LEFT JOIN operators o ON pn.operator_id = o.id
    WHERE pn.client_name != '' $searchCondition
";
$totalRecords = $koneksi->query($countSql)->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

$sql = "
    SELECT 
        pn.client_name, pn.prefix, o.name AS operator_name,
        CASE 
            WHEN SUM(pn.is_terminated) > 0 THEN 'Terminated'
            WHEN pn.release_date IS NULL THEN 'Active'
            WHEN pn.release_date > CURDATE() THEN 'Pending Release'
            ELSE 'Released'
        END AS status,
        GROUP_CONCAT(pn.phone_number ORDER BY pn.phone_number ASC SEPARATOR ',') as numbers
    FROM phone_numbers pn
    LEFT JOIN operators o ON pn.operator_id = o.id
    WHERE pn.client_name != '' $searchCondition
    GROUP BY pn.client_name, pn.prefix, o.name, pn.client_name
    ORDER BY pn.client_name ASC, pn.prefix ASC
    LIMIT $offset, $perPage
";

$result = $koneksi->query($sql);

$clients = [];
while ($row = $result->fetch_assoc()) {
    $numbers = array_filter(explode(',', $row['numbers']));
    $clients[] = [
        'client_name' => $row['client_name'],
        'prefix' => $row['prefix'],
        'operator' => $row['operator_name'],
        'status' => $row['status'],
        'numbers' => $numbers
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client List | NOC Optimized v3.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .card-client { border-left: 4px solid #4361ee; transition: 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        .card-client:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
        .badge-number { background:#000000; font-family:monospace; margin:2px; }
        .pagination .active .page-link { background:#4361ee; border:#4361ee; }
        .btn-export { background: #1d6f42; color:white; }
        .btn-export:hover { background:#166534; }
        .small-badge { font-size:0.75rem; padding: 0.3rem 0.5rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-people-fill me-2"></i>Client List</h4>
    <div>
        <a href="terminate_list.php" class="btn btn-warning btn-sm me-2"><i class="bi bi-exclamation-triangle"></i> Nomor Terminasi</a>
        <a href="../process/export_clients.php?format=excel&client=<?= urlencode($clientFilter) ?>&search=<?= urlencode($search) ?>&prefix=<?= urlencode($prefixFilter) ?>" class="btn btn-export btn-sm"><i class="bi bi-download"></i> Export All</a>
	<a href="management_nomor.php" class="btn btn-outline-primary btn-sm me-2"><i class="bi bi-arrow-left"></i> Back to Menu</a>
    </div>
</div>

    <form class="mb-4 row g-2 align-items-center" method="get" style="max-width:600px;">
        <div class="col">
            <input type="text" name="search" class="form-control" placeholder="Search clients, operators or numbers..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col">
            <input type="text" name="prefix" class="form-control" placeholder="Prefix" value="<?= htmlspecialchars($prefixFilter) ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </div>
        <?php if ($search || $prefixFilter): ?>
        <div class="col-auto">
            <a href="list_client.php" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
        </div>
        <?php endif; ?>
    </form>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i> Client berhasil dihapus & nomor kembali ke stock available.
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan saat menghapus.
    </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Showing <?= count($clients) ?> of <?= $totalRecords ?> entries (page <?= $page ?> of <?= $totalPages ?>)
    </div>

    <div class="row g-4">
        <?php if (!$clients): ?>
            <div class="col-12"><div class="alert alert-warning">No clients found.</div></div>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card p-3 card-client h-100">
                    <div class="d-flex justify-content-between mb-2">
                        <h5 class="text-truncate" title="<?= htmlspecialchars($client['client_name']) ?>"><?= htmlspecialchars($client['client_name']) ?></h5>
                        <span class="badge 
    			<?= $client['status']=='Active' ? 'bg-success' : 
        		($client['status']=='Pending Release' ? 'bg-warning text-dark' : 
            		($client['status']=='Terminated' ? 'bg-danger' : 'bg-secondary')) ?> small-badge"><?= $client['status'] ?>
		    </span>

                    </div>
                    <div class="mb-2">
                        <small><i class="bi bi-hash me-1"></i>Prefix: <b><?= $client['prefix'] ?: '-' ?></b></small><br>
                        <?php if ($client['operator']): ?>
                        <small><i class="bi bi-building me-1"></i>Operator: <b><?= $client['operator'] ?></b></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <small><i class="bi bi-telephone me-1"></i>Numbers:</small><br>
                        <div class="d-flex flex-wrap">
                            <?php foreach (array_slice($client['numbers'], 0, 5) as $num): ?>
                                <span class="badge badge-number"><?= $num ?></span>
                            <?php endforeach; ?>
                            <?php if (count($client['numbers']) > 5): ?>
                                <span class="badge bg-light text-muted">+<?= count($client['numbers'])-5 ?> more</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small>
                            <i class="bi bi-list-ol me-1"></i> Total: <?= count($client['numbers']) ?> 
                            <?php if (count($client['numbers']) > 5): ?>
                                (showing 5 preview)
                            <?php endif; ?>
                        </small>
                        <div class="btn-group">
                            <a href="../process/export_client.php?client=<?= urlencode($client['client_name']) ?>&prefix=<?= $client['prefix'] ?>" class="btn btn-sm btn-export"><i class="bi bi-download"></i> Export</a>
			      <a href="edit_client.php?client=<?= urlencode($client['client_name']) ?>&prefix=<?= urlencode($client['prefix']) ?>" class="btn btn-sm btn-primary">
        		     <i class="bi bi-pencil-square"></i>
                            </a>
  			     <form method="POST" action="../process/terminate_client.php" onsubmit="return confirm('Yakin terminate nomor client ini?')">
        		     <input type="hidden" name="client_name" value="<?= htmlspecialchars($client['client_name']) ?>">
                             <input type="hidden" name="prefix" value="<?= htmlspecialchars($client['prefix']) ?>">
                             <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-exclamation-triangle"></i></button>
                            </form>
                            <form method="POST" action="../process/soft_delete.php" onsubmit="return confirm('Yakin hapus client ini?')">
                                <input type="hidden" name="client_name" value="<?= htmlspecialchars($client['client_name']) ?>">
                                <input type="hidden" name="prefix" value="<?= htmlspecialchars($client['prefix']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-folder-minus"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&prefix=<?= urlencode($prefixFilter) ?>">&laquo;</a></li>
            <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&prefix=<?= urlencode($prefixFilter) ?>">&lsaquo;</a></li>
            <?php endif; ?>

            <?php
            $start = max(1, $page-2);
            $end = min($totalPages, $page+2);
            for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&prefix=<?= urlencode($prefixFilter) ?>"><?= $i ?></a></li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&prefix=<?= urlencode($prefixFilter) ?>">&rsaquo;</a></li>
            <li class="page-item"><a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&prefix=<?= urlencode($prefixFilter) ?>">&raquo;</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
