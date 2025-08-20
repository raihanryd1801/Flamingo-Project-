<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}

$clientName = $_GET['client'] ?? '';
$prefix = $_GET['prefix'] ?? '';

if (!$clientName || !$prefix) {
    header('Location: list_client.php?error=invalid');
    exit;
}

$sql = "SELECT * FROM phone_numbers WHERE client_name = ? AND prefix = ? LIMIT 1";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("ss", $clientName, $prefix);
$stmt->execute();
$result = $stmt->get_result();
$clientData = $result->fetch_assoc();

if (!$clientData) {
    header('Location: list_client.php?error=notfound');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Client</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h4>Edit Client: <?= htmlspecialchars($clientName) ?> (Prefix: <?= htmlspecialchars($prefix) ?>)</h4>
    <form action="../process/update_client.php" method="POST" class="mt-4">
        <input type="hidden" name="old_client_name" value="<?= htmlspecialchars($clientName) ?>">
        <input type="hidden" name="old_prefix" value="<?= htmlspecialchars($prefix) ?>">

        <div class="mb-3">
            <label class="form-label">Client Name</label>
            <input type="text" name="new_client_name" class="form-control" value="<?= htmlspecialchars($clientData['client_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Prefix</label>
            <input type="text" name="new_prefix" class="form-control" value="<?= htmlspecialchars($clientData['prefix']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Operator ID</label>
            <input type="text" name="operator_id" class="form-control" value="<?= htmlspecialchars($clientData['operator_id']) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="list_client.php" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>
