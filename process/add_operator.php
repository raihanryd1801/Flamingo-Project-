<?php
include '../config/db.php';

if (isset($_POST['name']) && isset($_POST['ip'])) {
    $name = trim($_POST['name']);
    $ip = trim($_POST['ip']);

    $stmt = $koneksi->prepare("INSERT INTO operators (name, ip) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $ip);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../numbermanagement/management_operator.php");
exit();
