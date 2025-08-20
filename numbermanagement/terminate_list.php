<?php
include '../config/db.php';
session_start();

if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$operatorQuery = "
    SELECT DISTINCT o.id AS operator_id, o.name AS operator_name
    FROM phone_numbers pn
    LEFT JOIN operators o ON pn.operator_id = o.id
    WHERE pn.is_terminated = 1 AND pn.terminate_status = 'proses'
    ORDER BY o.name
";
$operatorResult = $koneksi->query($operatorQuery);
$operators = [];
if ($operatorResult) {
    while ($operator = $operatorResult->fetch_assoc()) {
        $operators[] = $operator;
    }
}

$doneQuery = "
    SELECT o.id AS operator_id, o.name AS operator_name, pn.phone_number
    FROM phone_numbers pn
    LEFT JOIN operators o ON pn.operator_id = o.id
    WHERE pn.is_terminated = 1 AND pn.terminate_status = 'done'
    ORDER BY o.name, pn.phone_number
";
$doneResult = $koneksi->query($doneQuery);
$terminationsDone = [];
while ($row = $doneResult->fetch_assoc()) {
    $terminationsDone[$row['operator_id']]['operator_name'] = $row['operator_name'];
    $terminationsDone[$row['operator_id']]['numbers'][] = $row['phone_number'];
}

// Hitung total yang sedang di terminate (proses)
$totalProsesQuery = "SELECT COUNT(*) as total FROM phone_numbers WHERE is_terminated = 1 AND terminate_status = 'proses'";
$totalProsesResult = $koneksi->query($totalProsesQuery);
$totalProses = $totalProsesResult->fetch_assoc()['total'];

// Hitung total yang sudah done
$totalDoneQuery = "SELECT COUNT(*) as total FROM phone_numbers WHERE is_terminated = 1 AND terminate_status = 'done'";
$totalDoneResult = $koneksi->query($totalDoneQuery);
$totalDone = $totalDoneResult->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Nomor Terminasi | NOC Optimized v3.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .number-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }
        .loading {
            text-align: center;
            font-weight: bold;
            padding: 10px;
        }
        .number-container div {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-x-circle me-2"></i>Nomor Terminasi per Operator</h4>
	<div class="mb-3">
    	<span class="badge bg-warning text-dark me-2">Total Proses: <?= $totalProses ?></span>
    	<span class="badge bg-secondary me-2">Total Done: <?= $totalDone ?></span>
	</div>
        <a href="list_client.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> Terjadi kesalahan saat proses.
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-3" id="terminateTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="proses-tab" data-bs-toggle="tab" data-bs-target="#proses" type="button" role="tab">Proses</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="done-tab" data-bs-toggle="tab" data-bs-target="#done" type="button" role="tab">Done</button>
        </li>
    </ul>

    <div class="tab-content" id="terminateTabContent">

        <!-- Tab Proses -->
        <div class="tab-pane fade show active" id="proses" role="tabpanel">
            <?php if (empty($operators)): ?>
                <div class="alert alert-warning">Tidak ada nomor terminasi yang sedang diproses.</div>
            <?php else: ?>
                <?php foreach ($operators as $operator): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                            <strong><?= htmlspecialchars($operator['operator_name']) ?></strong>
                            <div class="d-flex align-items-center">
                                <a href="../process/export_terminations.php?operator_id=<?= $operator['operator_id'] ?>&status=proses" class="btn btn-sm btn-success me-2">
                                    <i class="bi bi-download"></i> Export
                                </a>
                                <form method="POST" action="../process/recover_client.php" class="d-inline me-2">
                                    <input type="hidden" name="operator_id" value="<?= $operator['operator_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Yakin kembalikan semua nomor operator <?= htmlspecialchars($operator['operator_name']) ?>?')">
                                        <i class="bi bi-arrow-counterclockwise"></i> Recover All
                                    </button>
                                </form>
                                <form method="POST" action="../process/mark_done_bulk.php" class="d-inline">
                                    <input type="hidden" name="operator_id" value="<?= $operator['operator_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-dark" onclick="return confirm('Yakin pindahkan semua nomor operator <?= htmlspecialchars($operator['operator_name']) ?> ke Done?')">
                                        <i class="bi bi-check2-circle"></i> Mark All as Done
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body number-container d-flex flex-wrap gap-2" id="operator-<?= $operator['operator_id'] ?>" data-operator-id="<?= $operator['operator_id'] ?>" data-offset="0" data-has-more="1">
                        </div>
                        <div class="loading" id="loading-<?= $operator['operator_id'] ?>">Loading...</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Tab Done -->
        <div class="tab-pane fade" id="done" role="tabpanel">
            <?php if (empty($terminationsDone)): ?>
                <div class="alert alert-warning">Tidak ada nomor terminasi yang sudah selesai.</div>
            <?php else: ?>
                <?php foreach ($terminationsDone as $operatorId => $operatorData): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <strong><?= htmlspecialchars($operatorData['operator_name']) ?></strong> (<?= count($operatorData['numbers']) ?> Nomor)
                            <a href="../process/export_terminations.php?operator_id=<?= $operatorId ?>&status=done" class="btn btn-sm btn-success"><i class="bi bi-download"></i> Export</a>
                        </div>
                        <div class="card-body d-flex flex-wrap">
                            <?php foreach ($operatorData['numbers'] as $phoneNumber): ?>
                                <div class="m-1 d-flex align-items-center">
                                    <span class="badge bg-secondary me-2"><?= htmlspecialchars($phoneNumber) ?></span>
                                    <form method="POST" action="../process/recover_client.php" class="d-inline">
                                        <input type="hidden" name="phone_number" value="<?= htmlspecialchars($phoneNumber) ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Yakin kembalikan nomor ini?')">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    $('.number-container').each(function () {
        let container = $(this);
        let operatorId = container.data('operator-id');
        loadMoreNumbers(container, operatorId);
    });

    function loadMoreNumbers(container, operatorId) {
        let offset = container.data('offset');
        let hasMore = container.data('has-more');

        if (hasMore !== 1) return;

        $.post('load_more.php', { operator_id: operatorId, offset: offset }, function (data) {
            if (data.numbers.length > 0) {
                data.numbers.forEach(number => {
                    let numberHtml = `
                        <div class="m-1 d-flex align-items-center">
                            <span class="badge bg-secondary me-2">${number}</span>
                            <form method="POST" action="../process/recover_client.php" class="d-inline">
                                <input type="hidden" name="phone_number" value="${number}">
                                <button type="submit" class="btn btn-sm btn-success me-2" onclick="return confirm('Yakin kembalikan nomor ini?')">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                            <form method="POST" action="../process/mark_done.php" class="d-inline">
                                <input type="hidden" name="phone_number" value="${number}">
                                <button type="submit" class="btn btn-sm btn-dark" onclick="return confirm('Pindahkan nomor ini ke Done?')">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                            </form>
                        </div>
                    `;
                    container.append(numberHtml);
                });

                container.data('offset', data.next_offset);
            }

            if (!data.has_more) {
                container.data('has-more', 0);
                $('#loading-' + operatorId).text('Semua nomor sudah ditampilkan.');
            } else {
                $('#loading-' + operatorId).hide();
            }
        }, 'json');
    }

    $('.number-container').on('scroll', function () {
        let container = $(this);
        if (container[0].scrollTop + container[0].clientHeight >= container[0].scrollHeight) {
            let operatorId = container.data('operator-id');
            $('#loading-' + operatorId).show();
            loadMoreNumbers(container, operatorId);
        }
    });
});
</script>

</body>
</html>
