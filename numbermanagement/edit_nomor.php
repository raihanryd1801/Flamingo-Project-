<?php
include '../config/db.php';
session_start();

$id = intval($_GET['id']);
$data = $koneksi->query("SELECT * FROM phone_numbers WHERE id = $id")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Nomor</title>
</head>
<body>

<h2>Edit Nomor</h2>

<form action="../process/update_edit_nomor.php" method="POST">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">

    <label>Operator:</label>
    <select name="operator_id" required>
        <option value="">Pilih Operator</option>
        <?php
        $result = $koneksi->query("SELECT * FROM operators ORDER BY name");
        while ($row = $result->fetch_assoc()):
            $selected = ($data['operator_id'] == $row['id']) ? 'selected' : '';
        ?>
            <option value="<?= $row['id'] ?>" <?= $selected ?>><?= $row['name'] ?></option>
        <?php endwhile; ?>
    </select><br><br>

    <label>Nomor Telepon:</label><br>
    <input type="text" name="phone_number" value="<?= $data['phone_number'] ?>" required><br><br>

    <label>Prefix:</label><br>
    <input type="text" name="prefix" value="<?= $data['prefix'] ?>"><br><br>

    <label>Client:</label><br>
    <input type="text" name="client_name" value="<?= $data['client_name'] ?>"><br><br>

    <button type="submit">Simpan Perubahan</button>
</form>

</body>
</html>

