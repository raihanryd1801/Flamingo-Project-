<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require __DIR__ . '/config/db.php';
require __DIR__ . '/helper/history_helper.php';
require __DIR__ . '/config/session.php'; // biar bisa pakai get_client_ip()
require_once __DIR__ . '/config/token.php';
$csrf_token = generate_token();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_token($token)) {
        $error = 'Invalid CSRF token!';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if ($username && $password && $role) {
            $stmt = $koneksi->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $allowed_roles = ['noc_voip', 'noc_internet', 'admin_it', 'administrator']; // Role yang diizinkan

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] !== $role) {
                    $error = 'Role tidak sesuai dengan akun.';
                    logHistory($koneksi, 'FAILED LOGIN', 'User ' . $username . ' gagal login karena role tidak sesuai.');
                } elseif (!in_array($user['role'], $allowed_roles)) {
                    logHistory($koneksi, 'FAILED LOGIN', 'User ' . $username . ' mencoba login dengan role yang tidak diizinkan.');
                    header("Location: unauthorized.php");
                    exit;
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['token'] = bin2hex(random_bytes(32));
                    $_SESSION['ip_address'] = get_client_ip(); // penting untuk validate_session()
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? ''; // penting juga

                    logHistory($koneksi, 'LOGIN', 'User ' . $user['username'] . ' berhasil login dengan role ' . $user['role']);

                    header("Location: index.php");
                    exit;
                }
            } else {
                $error = 'Username atau password salah';
                logHistory($koneksi, 'FAILED LOGIN', 'Gagal login untuk username ' . $username);
            }
        } else {
            $error = 'Harap isi semua field';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem Flamingo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
    --primary: #4361ee;
    --primary-dark: #3a56d4;
    --primary-light: #eef2ff;
    --secondary: #3f37c9;
    --secondary-dark: #2f2ba8;
    --error: #ef233c;
    --error-light: #fde8ea;
    --success: #4bb543;
    --light: #f8f9fa;
    --light-gray: #f1f3f5;
    --dark: #212529;
    --dark-gray: #343a40;
    --gray: #6c757d;
    --border-radius: 8px;
    --border-radius-lg: 12px;
    --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    --box-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.12);
    --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    color: var(--dark);
    line-height: 1.6;
}

.login-container {
    background-color: white;
    padding: 2.5rem;
    box-shadow: var(--box-shadow);
    border-radius: var(--border-radius-lg);
    width: 100%;
    max-width: 420px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.03);
}

.login-container:hover {
    transform: translateY(-3px);
    box-shadow: var(--box-shadow-hover);
}

.login-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-header h2 {
    color: var(--dark-gray);
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    letter-spacing: -0.5px;
}

.login-header p {
    color: var(--gray);
    font-size: 0.95rem;
    max-width: 80%;
    margin: 0 auto;
}

.login-icon {
    font-size: 2.5rem;
    color: var(--primary);
    margin-bottom: 1.25rem;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--dark-gray);
    font-size: 0.95rem;
}

.input-with-icon {
    position: relative;
}

.input-with-icon i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    font-size: 1rem;
}

.form-control {
    width: 100%;
    padding: 0.85rem 1rem 0.85rem 40px;
    border: 1px solid #e9ecef;
    border-radius: var(--border-radius);
    font-size: 0.95rem;
    transition: var(--transition);
    background-color: var(--light-gray);
    color: var(--dark);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    background-color: white;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: var(--gray);
    font-size: 1.1rem;
    transition: var(--transition);
}

.password-toggle:hover {
    color: var(--dark);
}

.btn {
    width: 100%;
    padding: 0.85rem;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition);
    margin-top: 0.5rem;
    letter-spacing: 0.5px;
}

.btn:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--secondary-dark));
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
}

.btn:active {
    transform: translateY(0);
}

.error {
    color: var(--error);
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    text-align: center;
    padding: 0.75rem;
    background-color: var(--error-light);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--error);
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.additional-links {
    margin-top: 1.5rem;
    text-align: center;
    font-size: 0.9rem;
}

.additional-links a {
    color: var(--gray);
    text-decoration: none;
    transition: var(--transition);
}

.additional-links a:hover {
    color: var(--primary);
    text-decoration: underline;
}

@media (max-width: 480px) {
    .login-container {
        padding: 2rem 1.5rem;
        margin: 0 1rem;
    }
    
    .login-header h2 {
        font-size: 1.5rem;
    }
    
    .login-header p {
        max-width: 100%;
    }
}    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <div class="login-icon"><i class="fas fa-user-shield"></i></div>
        <h2>Selamat Datang</h2>
        <p>Silakan masuk ke akun Anda</p>
    </div>

    <?php if ($error): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        
        <div class="form-group">
            <label for="username">Username</label>
            <div class="input-with-icon">
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>
        </div>

        <div class="form-group">
            <label for="role">Pilih Role</label>
            <div class="input-with-icon">
                <i class="fas fa-users-cog"></i>
                <select name="role" id="role" class="form-control" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="administrator">Administrator</option>
                    <option value="noc_voip">NOC VOIP</option>
                    <option value="noc_internet">NOC INTERNET</option>
                    <option value="admin_it">Admin Dankom</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-with-icon">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                <span class="password-toggle" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
            </div>
        </div>

        <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.password-toggle i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
</body>
</html>
