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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = (int)$_POST['user_id'];
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success = "User berhasil dihapus";
        } else {
            $error = "Gagal menghapus user: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['add_user'])) {
        // Add new user
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        if (empty($username) || empty($password) || empty($role)) {
            $error = "Semua field wajib diisi";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $koneksi->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed, $role);
            
            if ($stmt->execute()) {
                $success = "User berhasil ditambahkan";
            } else {
                $error = "Gagal menambahkan user: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get all users
$users = [];
$result = $koneksi->query("SELECT id, username, role FROM users ORDER BY id");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

$koneksi->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
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
    </style>
</head>
<body>
    <!-- Simple navigation without navbar.php -->
    <div class="simple-nav">
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a> | 
        <a href="admin_users.php"><i class="fas fa-users-cog"></i> User Management</a> | 
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a> | 
    </div>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p>Kelola semua user sistem</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="admin-content">
            <div class="card">
                <h2><i class="fas fa-user-plus"></i> Tambah User Baru</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="administrator">Administrator</option>
                            <option value="noc_voip">NOC VoIP</option>
                            <option value="noc_internet">NOC Internet</option>
                            <option value="admin_it">Admin IT</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </form>
            </div>

            <div class="card">
                <h2><i class="fas fa-users"></i> Daftar User</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td class="actions">
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn-danger">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>