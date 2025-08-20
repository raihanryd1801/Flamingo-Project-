<?php
function logHistory($koneksi, $action, $description) {
    $stmt = $koneksi->prepare("INSERT INTO history_log (user_id, username, role, action, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'], $action, $description);
    $stmt->execute();
    $stmt->close();
}
?>

