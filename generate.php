<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: login.php');
    exit;
}

// Mengatur hak akses  
if ($_SESSION['role'] !== 'noc_voip' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="transition-all duration-300">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Generator Nomor - VOIP</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: {
              500: '#3B82F6',
              600: '#2563EB',
            },
            secondary: {
              500: '#10B981',
              600: '#059669',
            }
          }
        }
      }
    };
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    body {
      font-family: 'Poppins', sans-serif;
    }
    .card {
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .btn-indosat {
      background: linear-gradient(135deg, #FFC107 0%, #FF9800 100%);
    }
    .btn-tsel {
      background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
    }
    .btn-smartfren {
      background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
    }
    .btn-fix {
      background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
    }
    .btn-indosat:hover, .btn-tsel:hover, 
    .btn-smartfren:hover, .btn-fix:hover {
      opacity: 0.9;
    }
    .modal-content {
      animation: modalFadeIn 0.3s ease-out;
    }
    @keyframes modalFadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .dropdown-content {
      animation: dropdownFadeIn 0.2s ease-out;
    }
    @keyframes dropdownFadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">

<!-- Header with Branding -->
<div class="absolute top-4 left-4 flex items-center space-x-4">
  <a href="index.php" class="flex items-center space-x-2 group">
    <div class="bg-primary-500 group-hover:bg-primary-600 p-2 rounded-lg shadow transition-colors">
      <i data-lucide="arrow-left" class="w-5 h-5 text-white"></i>
    </div>
    <span class="font-medium text-gray-700 dark:text-gray-300">Kembali ke menu</span>
  </a>
  
  <div class="flex items-center space-x-2">
    <span class="text-gray-500 dark:text-gray-400">|</span>
    <img src="../assets/img/dankomclean.png" alt="RajaWifi Logo" class="h-8">
  </div>
</div>

<!-- Dark Mode Toggle -->
<div class="absolute top-4 right-4">
  <button onclick="toggleMode()" class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
    <i data-lucide="moon" id="icon-darkmode" class="w-5 h-5 dark:hidden"></i>
    <i data-lucide="sun" id="icon-lightmode" class="w-5 h-5 hidden dark:inline-block text-yellow-300"></i>
  </button>
</div>

<!-- Main Content -->
<div class="card bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-2xl p-8 max-w-2xl w-full mx-4">
  <div class="text-center mb-8">
    <h1 class="text-3xl font-bold mb-2">Auto Generate Route Number</h1>
    <p class="text-gray-600 dark:text-gray-400">Generator nomor telepon untuk kebutuhan routing</p>
  </div>

  <!-- Operator Buttons -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <button onclick="showModal('indosat')" class="btn-indosat text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center space-x-2">
      <i data-lucide="smartphone" class="w-5 h-5"></i>
      <span>Indosat</span>
    </button>
    <button onclick="showModal('tsel')" class="btn-tsel text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center space-x-2">
      <i data-lucide="smartphone" class="w-5 h-5"></i>
      <span>Telkomsel</span>
    </button>
    <button onclick="showModal('smartfren')" class="btn-smartfren text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center space-x-2">
      <i data-lucide="smartphone" class="w-5 h-5"></i>
      <span>Smartfren</span>
    </button>
    <div class="relative">
      <button onclick="toggleDropdown()" class="btn-fix text-white font-semibold py-3 px-4 rounded-xl w-full flex items-center justify-center space-x-2">
        <i data-lucide="settings" class="w-5 h-5"></i>
        <span>Fix Number</span>
      </button>
      <div id="dropdown" class="hidden absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden dropdown-content">
        <button onclick="showModal('fix_indosat')" class="block w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-2">
          <i data-lucide="smartphone" class="w-4 h-4"></i>
          <span>Indosat</span>
        </button>
        <button onclick="showModal('fix_tsel')" class="block w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-2">
          <i data-lucide="smartphone" class="w-4 h-4"></i>
          <span>Telkomsel</span>
        </button>
        <button onclick="showModal('fix_smartfren')" class="block w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-2">
          <i data-lucide="smartphone" class="w-4 h-4"></i>
          <span>Smartfren</span>
        </button>
      </div>
    </div>
  </div>

  <?php
    $output = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $operator = $_POST['operator'];
      $lines = explode("\n", trim($_POST['nomor']));
      $processed = [];

      foreach ($lines as $line) {
        $num = trim($line);
        if ($num == '') continue;

        switch ($operator) {
          case 'indosat':
            if (preg_match('/^0?21[0-9]{7,8}$/', $num)) {
              $num = preg_replace('/^0?/', '+62', $num);
            } else {
              $num = preg_replace('/^0/', '', $num);
              $num = '+' . $num;
            }
            $processed[] = $num;
            break;

          case 'tsel':
            $num = preg_replace('/^0/', '', $num);
            $processed[] = $num;
            break;

          case 'smartfren':
            if (!preg_match('/^0/', $num)) {
              $num = '0' . $num;
            }
            $processed[] = $num;
            break;

          case 'fix_indosat':
            $processed[] = "$num:+$num";
            break;

          case 'fix_tsel':
          case 'fix_smartfren':
            $processed[] = "$num:$num";
            break;
        }
      }

      $processed = array_unique($processed);

      if ($operator === 'fix_indosat') {
        $output = implode(",", $processed);
      } elseif ($operator === 'fix_tsel' || $operator === 'fix_smartfren') {
        $output = '*:' . implode(",", $processed);
      } else {
        $output = '*:' . implode(";", $processed);
      }
    }
  ?>
</div>

<!-- Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
  <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl w-full max-w-4xl relative p-6 shadow-2xl">
    <button onclick="closeModal()" class="absolute top-4 right-4 p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
      <i data-lucide="x" class="w-6 h-6 text-gray-600 dark:text-gray-300"></i>
    </button>
    
    <form method="post" class="flex flex-col md:flex-row gap-6">
      <input type="hidden" id="operator" name="operator">
      
      <div class="flex-1">
        <div class="mb-4">
          <label class="block text-sm font-semibold mb-2">Masukkan daftar nomor:</label>
          <div class="relative">
            <textarea name="nomor" rows="10" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" placeholder="Isi daftar nomor  berikut"><?= isset($_POST['nomor']) ? htmlspecialchars($_POST['nomor']) : '' ?></textarea>
            <div class="absolute bottom-4 right-4 text-xs text-gray-500 dark:text-gray-400">
              <i data-lucide="info" class="w-4 h-4 inline-block mr-1"></i>
              <span>Satu nomor per baris</span>
            </div>
          </div>
        </div>
        <button type="submit" class="w-full bg-primary-500 hover:bg-primary-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center space-x-2 transition-colors">
          <i data-lucide="zap" class="w-5 h-5"></i>
          <span>Generate</span>
        </button>
      </div>
      
      <div class="flex-1">
        <div class="mb-4">
          <label class="block text-sm font-semibold mb-2">Hasil Generate:</label>
          <div class="relative">
            <textarea id="output" rows="10" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" readonly><?= isset($output) ? htmlspecialchars($output) : '' ?></textarea>
            <div class="absolute bottom-4 right-4 text-xs text-gray-500 dark:text-gray-400">
              <i data-lucide="clipboard" class="w-4 h-4 inline-block mr-1"></i>
              <span>Klik tombol salin</span>
            </div>
          </div>
        </div>
        <button type="button" onclick="copyText()" class="w-full bg-secondary-500 hover:bg-secondary-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center space-x-2 transition-colors">
          <i data-lucide="copy" class="w-5 h-5"></i>
          <span>Salin ke Clipboard</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Modal functions
  function showModal(op) {
    document.getElementById("operator").value = op;
    document.getElementById("modal").classList.remove("hidden");
    document.body.style.overflow = 'hidden';
    document.getElementById("dropdown").classList.add("hidden");
  }

  function closeModal() {
    document.getElementById("modal").classList.add("hidden");
    document.body.style.overflow = 'auto';
  }

  // Copy to clipboard
  function copyText() {
    const output = document.getElementById("output");
    output.select();
    output.setSelectionRange(0, 99999);
    document.execCommand("copy");
    
    // Show notification
    const notification = document.createElement("div");
    notification.className = "fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 animate-bounce";
    notification.innerHTML = `
      <i data-lucide="check-circle" class="w-5 h-5"></i>
      <span>Nomor berhasil disalin!</span>
    `;
    document.body.appendChild(notification);
    
    // Refresh Lucide icons
    lucide.createIcons();
    
    // Remove notification after 3 seconds
    setTimeout(() => {
      notification.remove();
    }, 3000);
  }

  // Dark mode toggle
  function toggleMode() {
    document.documentElement.classList.toggle('dark');
    localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
  }

  // Dropdown toggle
  function toggleDropdown() {
    const dropdown = document.getElementById("dropdown");
    dropdown.classList.toggle("hidden");
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById("dropdown");
    const fixBtn = document.querySelector('.relative button');
    
    if (!dropdown.contains(event.target) && event.target !== fixBtn && !fixBtn.contains(event.target)) {
      dropdown.classList.add("hidden");
    }
  });

  // Initialize dark mode
  if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }

  // Initialize Lucide icons
  lucide.createIcons();

  // Show modal if form was submitted
  <?php if ($_SERVER["REQUEST_METHOD"] == "POST") : ?>
    document.getElementById("modal").classList.remove("hidden");
    document.body.style.overflow = 'hidden';
  <?php endif; ?>
</script>

</body>
</html>