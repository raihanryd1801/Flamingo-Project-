<?php
include 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$client_name = $_GET['client'] ?? '';
$prefix = $_GET['prefix'] ?? '';

if (!$client_name || !$prefix) {
    header("Location: list_client.php");
    exit();
}

// Ambil data client yang mau diedit
$stmt = $koneksi->prepare("
    SELECT client_name, prefix, operator_id
    FROM phone_numbers 
    WHERE client_name = ? AND prefix = ? LIMIT 1
");
$stmt->bind_param("ss", $client_name, $prefix);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if (!$client) {
    echo "Client not found.";
    exit();
}

// Ambil data operator untuk dropdown
$operatorResult = $koneksi->query("SELECT id, name FROM operators ORDER BY name ASC");
$operators = [];
while ($row = $operatorResult->fetch_assoc()) {
    $operators[] = $row;
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_client_name = trim($_POST['client_name']);
    $new_prefix = trim($_POST['prefix']);
    $operator_id = (int)$_POST['operator_id'];

    // Update semua nomor yang punya client_name & prefix yang sama
    $updateStmt = $koneksi->prepare("
        UPDATE phone_numbers
        SET client_name = ?, prefix = ?, operator_id = ?
        WHERE client_name = ? AND prefix = ?
    ");
    $updateStmt->bind_param("ssiss", $new_client_name, $new_prefix, $operator_id, $client_name, $prefix);
    $updateStmt->execute();

    header("Location: list_client.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Client | NOC Optimized v3.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container">
    <h3 class="mb-4">Edit Client</h3>
    <div class="card p-4">
        <form method="post">
            <div class="mb-3">
                <label>Client Name</label>
                <input type="text" name="client_name" class="form-control" value="<?= htmlspecialchars($client['client_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label>Prefix</label>
                <input type="text" name="prefix" class="form-control" value="<?= htmlspecialchars($client['prefix']) ?>" required>
            </div>

            <div class="mb-3">
                <label>Operator</label>
                <select name="operator_id" class="form-select" required>
                    <option value="">-- Pilih Operator --</option>
                    <?php foreach ($operators as $op): ?>
                        <option value="<?= $op['id'] ?>" <?= $op['id'] == $client['operator_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($op['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex justify-content-between">
                <a href="list_client.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>

