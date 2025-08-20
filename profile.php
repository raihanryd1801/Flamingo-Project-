<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config/db.php';

$error = '';
$success = '';
$userData = [];

// Get current user data
try {
    $stmt = $koneksi->prepare("SELECT username, role FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $koneksi->error);
    }
    
    if (!$stmt->bind_param("s", $_SESSION['username'])) {
        throw new Exception("Error binding parameters: " . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    if (!$userData) {
        throw new Exception("User data not found");
    }
    
    $stmt->close();
} catch (Exception $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Semua field wajib diisi.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        try {
            // Verify current password
            $stmt = $koneksi->prepare("SELECT password FROM users WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Error preparing query: " . $koneksi->error);
            }
            
            if (!$stmt->bind_param("s", $_SESSION['username'])) {
                throw new Exception("Error binding parameters: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            $stmt->bind_result($hashed_password);
            if (!$stmt->fetch()) {
                throw new Exception("User not found");
            }
            $stmt->close();

            if (!password_verify($current_password, $hashed_password)) {
                throw new Exception("Password saat ini salah.");
            }

            // Update password
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare("UPDATE users SET password = ? WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Error preparing update: " . $koneksi->error);
            }
            
            if (!$stmt->bind_param("ss", $new_hashed, $_SESSION['username'])) {
                throw new Exception("Error binding parameters: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating password: " . $stmt->error);
            }
            
            $success = "Password berhasil diubah!";
            $stmt->close();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$koneksi->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna</title>
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
    <div class="profile-box">
        <div class="profile-header">
            <h2>Profil Pengguna</h2>
            <p>Kelola informasi akun Anda</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($userData): ?>
        <div class="profile-info">
            <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($userData['username']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Role:</span>
                <span class="info-value"><?php echo htmlspecialchars($userData['role']); ?></span>
            </div>
        </div>

        <div class="password-form">
            <h3>Ubah Password</h3>
            <form method="POST" action="profile.php">
                <div class="form-group">
                    <input type="password" name="current_password" placeholder="Password Saat Ini" required>
                </div>
                <div class="form-group">
                    <input type="password" name="new_password" placeholder="Password Baru" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
                </div>
                <button type="submit" name="change_password" class="update-button">Update Password</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="profile-actions">
            <a href="index.php" class="back-button">Kembali ke Dashboard</a>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </div>
</div>
</body>
</html>