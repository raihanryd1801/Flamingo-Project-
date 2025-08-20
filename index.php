<?php
session_start();
include 'config/check_license.php';

if (!isset($_SESSION['license_valid']) || !$_SESSION['license_valid']) {
    die("License belum valid.");
}

// Lanjut aplikasi
//echo "License valid! Selamat datang di aplikasi!";

// Panggil session handler yang lebih aman
require __DIR__ . '/config/session.php';
//require __DIR__ . '/token.php';


// Validasi session (panggil fungsi dari session.php)
validate_session();

// Enhanced session validation with error handling
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['token']) || !isset($_SESSION['role'])) {
    $_SESSION['login_error'] = "Silakan login terlebih dahulu";
    header("Location: login.php");
    exit;
}


// Secure session data handling
$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
$avatar_icon = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'fa-user-circle';
$login_ip   = $_SESSION['ip_address'] ?? 'Tidak diketahui';
$current_ip = function_exists('get_client_ip') ? get_client_ip() : 'N/A';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Flamingo | <?= ucfirst($role) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
</head>
<body>
 <!-- Top Navigation Bar -->
<header class="topbar">
            <button class="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
          <div class="topbar-right">
        <div class="theme-toggle">
            <input type="checkbox" id="themeToggle" class="toggle-checkbox">
            <label for="themeToggle" class="toggle-label">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
                <span class="toggle-ball"></span>
            </label>
        </div>
                <div class="user-dropdown">
                    <button class="user-btn">
                        <div class="user-avatar">
                            <i class="fas <?= $avatar_icon ?>"></i>
                        </div>
                        <div class="user-info">
                            <span class="username"><?= $username ?></span>
                            <span class="user-role"><?= $role ?></span>
                        </div>
                        <i class="dropdown-icon fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user-cog"></i> Profil Saya
                        </a>
                        <?php if ($role === 'administrator'): ?>
                        <a href="register.php" class="dropdown-item">
                            <i class="fas fa-user-plus"></i> Buat Akun Baru
                        </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <form action="logout.php" method="POST" class="logout-form">
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
        <!-- Sidebar Navigation -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="brand-logo">
                <img src="assets/img/dankom1.png" alt="Flamingo Logo" class="logo-img">
            </a>
            <button class="sidebar-toggle" aria-label="Close sidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-menu">
            <h3 class="menu-title"><i class="fas fa-th-large"></i> MENU UTAMA</h3>
            
            <?php if ($role === 'administrator'): ?>
                <div class="menu-group">
                    <h4 class="menu-group-title">Administrator</h4>
                    <a href="admin_users.php" class="menu-item">
                        <i class="fas fa-users-cog"></i>
                        <span>Management Users</span>
                    </a>
                    <a href="firewalld.php" class="menu-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Management Firewalld</span>
                    </a>
                </div>
                <div class="menu-group">
                    <h4 class="menu-group-title">Nomor Telepon</h4>
                    <a href="generate.php" class="menu-item">
                        <i class="fas fa-bolt"></i>
                        <span>Generate Number</span>
                    </a>
                    <a href="generate2.php" class="menu-item">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Pergantian Nomor</span>
                    </a>
                    <a href="scheduled_generate.php" class="menu-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Schedule Generate</span>
                    </a>
                    <a href="numbermanagement/management_nomor.php" class="menu-item">
                        <i class="fas fa-tasks"></i>
                        <span>Management Nomor</span>
                    </a>
                </div>

                <div class="menu-group">
                    <h4 class="menu-group-title">Monitoring</h4>
                    <a href="servermonitoring/monitoring.php" class="menu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Monitoring Server</span>
                    </a>
                    <a href="../helper/history_log.php" class="menu-item">
                        <i class="fas fa-history"></i>
                        <span>History Log</span>
                    </a>		    
                    <a href="routermonit/monitor.php" class="menu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Monitoring Router</span>
                    </a>
                </div>

                <div class="menu-group">
                    <h4 class="menu-group-title">Operasional</h4>
                    <a href="dailywork/dailyworkteknisi.php" class="menu-item">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Daily Work Teknisi</span>
                    </a>
                </div>

            <?php elseif ($role === 'noc_voip'): ?>
                <div class="menu-group">
                    <h4 class="menu-group-title">VOIP Operations</h4>
                    <a href="generate.php" class="menu-item">
                        <i class="fas fa-bolt"></i>
                        <span>Generate Number</span>
                    </a>
                    <a href="servermonitoring/monitoring.php" class="menu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Monitoring Server</span>
                    </a>
                    <a href="generate2.php" class="menu-item">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Pergantian Nomor</span>
                    </a>
                    <a href="scheduled_generate.php" class="menu-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Schedule Generate</span>
                    </a>
                    <a href="numbermanagement/management_nomor.php" class="menu-item">
                        <i class="fas fa-tasks"></i>
                        <span>Management Nomor</span>
                    </a>
                </div>

            <?php elseif ($role === 'noc_internet'): ?>
                <div class="menu-group">
                    <h4 class="menu-group-title">Internet Operations</h4>
                    <a href="dailywork/dailyworkteknisi.php" class="menu-item">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Daily Work Teknisi</span>
                    </a>
                    <a href="routermonit/monitor.php" class="menu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Monitoring Router</span>
                    </a>
                </div>

            <?php elseif ($role === 'admin_it'): ?>
                <div class="menu-group">
                    <h4 class="menu-group-title">Inventory</h4>
                    <a href="gudang/sistemgudang.php" class="menu-item">
                        <i class="fas fa-warehouse"></i>
                        <span>Sistem Gudang</span>
                    </a>
                </div>
                <div class="menu-group">
                    <h4 class="menu-group-title">Reports</h4>
                    <a href="dailywork/laporan.php" class="menu-item">
                        <i class="fas fa-file-alt"></i>
                        <span>Laporan Teknisi</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="menu-group">
                    <div class="menu-item disabled">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Role tidak dikenali</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <div class="user-status">
                <div class="status-indicator online"></div>
                <span>Sistem Aktif</span>
            </div>
            <div class="app-version">
                <span>v2.5.0</span>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="content">
        <div class="welcome-card">
            <div class="welcome-header">
                <h1>Selamat Datang, <?= $username ?>!</h1>
                <p class="welcome-subtitle">Anda login sebagai <span class="role-badge"><?= $role ?></span></p>
		<p <strong>IP Login:</strong> <?= htmlspecialchars($login_ip, ENT_QUOTES, 'UTF-8') ?></p>
    		<p <strong>IP Sekarang:</strong> <?= htmlspecialchars($current_ip, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            
            <div class="welcome-content">
                <div class="welcome-illustration">
                    <i class="fas fa-rocket" id="rocketIcon"></i>
                </div>
                <div class="welcome-message">
                    <h2>Sistem Manajemen Flamingo</h2>
                    <p>Gunakan menu navigasi di sebelah kiri untuk mengakses fitur yang tersedia sesuai dengan hak akses Anda.</p>
                    <button class="quick-action-btn" id="mulaiBtn">
                        <i class="fas fa-bolt"></i> Mulai Bekerja
                    </button>
                    <div id="animationContainer" style="width: 100%; height: 100vh; display: none;"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        // Toggle sidebar functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            // Toggle sidebar on mobile menu button click
            if (mobileMenuToggle && sidebar) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar on close button click
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                });
            }
            
            // Dark mode toggle functionality
            const toggleCheckbox = document.querySelector('.toggle-checkbox');
            if (toggleCheckbox) {
                toggleCheckbox.addEventListener('change', function() {
                    document.body.classList.toggle('dark-mode');
                    // Save preference to localStorage
                    if (this.checked) {
                        localStorage.setItem('darkMode', 'enabled');
                    } else {
                        localStorage.removeItem('darkMode');
                    }
                });
                
                // Check for dark mode preference on load
                if (localStorage.getItem('darkMode') === 'enabled') {
                    document.body.classList.add('dark-mode');
                    toggleCheckbox.checked = true;
                }
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 992) {
                    if (!sidebar.contains(e.target) && 
                        e.target !== mobileMenuToggle && 
                        !mobileMenuToggle.contains(e.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });
            
            // Rocket animation
            document.getElementById('mulaiBtn').addEventListener('click', function() {
                gsap.to('#rocketIcon', {
                    y: -500,
                    rotation: 360,
                    opacity: 0,
                    duration: 2,
                    ease: "power2.out",
                    onComplete: function() {
                        // Reset rocket position after animation
                        setTimeout(function() {
                            gsap.set('#rocketIcon', { y: 0, rotation: 0, opacity: 1 });
                        }, 1000);
                    }
                });
            });
        });
    </script>

</body>
</html>
