<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('error_reporting', (string)E_ALL);
session_start();

require __DIR__ . '/config/db.php';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numbersInput = $_POST['numbers'] ?? '';
    $operator_id = (int)($_POST['operator_id'] ?? 0);

    $numbersArray = array_filter(array_map('trim', explode("\n", $numbersInput)));

    $stmt = $koneksi->prepare("INSERT INTO phone_numbers (operator_id, phone_number, prefix, client_name, is_available) VALUES (?, ?, '', '', 1)");

    $inserted = 0;
    foreach ($numbersArray as $number) {
        $stmt->bind_param("is", $operator_id, $number);
        if ($stmt->execute()) {
            $inserted++;
        }
    }

    $_SESSION['success'] = "$inserted nomor berhasil ditambahkan.";
    header("Location: bulk_insert.php");
    exit;
}

// Ambil data operator untuk dropdown
$operators = [];
$result = $koneksi->query("SELECT id, name FROM operators ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $operators[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bulk Insert Nomor</title>
</head>
<body>
    <h1>Input Massal Nomor Telepon</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div style="color: green;"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Operator:</label>
        <select name="operator_id" required>
            <option value="">-- Pilih Operator --</option>
            <?php foreach ($operators as $op): ?>
                <option value="<?= $op['id'] ?>"><?= htmlspecialchars($op['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label>Masukkan daftar nomor (1 nomor per baris):</label><br>
        <textarea name="numbers" rows="20" cols="50" placeholder="Masukkan nomor, satu nomor per baris"></textarea>
        <br><br>

        <button type="submit">Submit</button>
    </form>

</body>
</html>
<?php $koneksi->close(); ?>

