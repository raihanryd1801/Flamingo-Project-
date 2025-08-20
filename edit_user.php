<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'administrator') {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/config/db.php';

$error = '';
$success = '';
$user = [];

// Get user ID from URL
$user_id = (int)$_GET['id'];

// Get user data
$stmt = $koneksi->prepare("SELECT id, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $error = "User tidak ditemukan";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    if (empty($username) || empty($role)) {
        $error = "Username dan role wajib diisi";
    } else {
        try {
            // Update without password if empty
            if (empty($password)) {
                $stmt = $koneksi->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $role, $user_id);
            } else {
                // Update with password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $koneksi->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $role, $hashed, $user_id);
            }
            
            if ($stmt->execute()) {
                $success = "User berhasil diupdate";
                // Refresh user data
                $stmt = $koneksi->prepare("SELECT id, username, role FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
            } else {
                $error = "Gagal mengupdate user: " . $stmt->error;
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
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
    <title>Edit User</title>
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .simple-nav {
            background: #2c3e50;
            padding: 15px;
            color: white;
            margin-bottom: 20px;
        }
        .simple-nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        .simple-nav a:hover {
            text-decoration: underline;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Simple navigation -->
    <div class="simple-nav">
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a> | 
        <a href="admin_users.php"><i class="fas fa-users-cog"></i> User Management</a> | 
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a> | 
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-user-edit"></i> Edit User</h1>
            <p>Edit data user</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="admin-content">
            <div class="card">
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="administrator" <?= ($user['role'] ?? '') === 'administrator' ? 'selected' : '' ?>>Administrator</option>
                            <option value="noc_voip" <?= ($user['role'] ?? '') === 'noc_voip' ? 'selected' : '' ?>>NOC VoIP</option>
                            <option value="noc_internet" <?= ($user['role'] ?? '') === 'noc_internet' ? 'selected' : '' ?>>NOC Internet</option>
                            <option value="admin_it" <?= ($user['role'] ?? '') === 'admin_it' ? 'selected' : '' ?>>Admin IT</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Password (Kosongkan jika tidak ingin mengubah)</label>
                        <input type="password" name="password" placeholder="Masukkan password baru">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_user" class="btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="admin_users.php" class="back-button">
                            <i class="fas fa-arrow-left"></i> Kembali ke User Management
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>