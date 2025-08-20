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

require_once 'config/vos_server.php';

function getRoutingData($server) {
    $cacheFile = __DIR__ . '/cache/routing_' . $server . '.json';
    $cacheTime = 300;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    global $vos_servers;
    if (!isset($vos_servers[$server])) {
        return [];
    }

    $cfg = $vos_servers[$server];
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], 'vos3000db');

    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
    }

    $sql = "SELECT r.id, r.name, r.prefix, s.rewriterulesincaller
            FROM e_gatewayrouting r
            JOIN e_gatewayroutingsetting s ON r.id = s.gatewayrouting_id
            ORDER BY r.name";

    $result = $conn->query($sql);

    if (!$result) {
        die("Query gagal: " . $conn->error);
    }

    $data = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();

    if (!file_exists(__DIR__ . '/cache')) {
        mkdir(__DIR__ . '/cache', 0755, true);
    }

    file_put_contents($cacheFile, json_encode($data));

    return $data;
}

// Path logo
$logoAqsaa = '../assets/img/dankomclean.png';


$server = isset($_GET['server']) && in_array($_GET['server'], ['vos1', 'vos2','vos3']) ? $_GET['server'] : 'vos1';
$routingData = getRoutingData($server);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Update Nomor Routing VOS3000</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f4f7f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header i {
            font-size: 48px;
            color: #4a90e2;
        }

        .header h1 {
            margin: 10px 0 5px;
            font-size: 26px;
        }

        .header p {
            font-size: 14px;
            color: #777;
        }

        form {
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        input[type="text"], select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            transition: 0.2s;
        }

        input[type="text"]:focus, select:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .btn {
            background: #4a90e2;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn:disabled {
            background: #ccc;
        }

        .btn:hover {
            background: #357ABD;
        }

        .search-box {
            position: relative;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 36px 12px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .loading {
            display: none;
            text-align: center;
            margin-bottom: 20px;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4a90e2;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 1s linear infinite;
            margin: auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .success, .error {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
	.back-btn {
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1000;
            background: linear-gradient(to right, #006d77, #83c5be);
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
	}

	.brand-logo {
            height: 90px;
            width: auto;
            max-width: 300px;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));

	}
        .success {
            background-color: #e0f5e9;
            color: #2e7d32;
        }

        .error {
            background-color: #fdecea;
            color: #c62828;
        }

        .result-icon {
            font-size: 20px;
            margin-right: 10px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<a href="/index.php" class="btn back-btn">
    <i class="bi bi-arrow-left-short"></i> Kembali ke Menu
</a>

<div class="container">
    <div class="header">
        <img src="<?php echo $logoAqsaa; ?>" onerror="this.src='https://via.placeholder.com/220x80?text=AQSAA'" alt="AQSAA Logo" class="brand-logo">
        <h1>Update Nomor Routing VOS3000</h1>
        <p>Pilih routing dan ubah nomor tujuan dengan cepat</p>
    </div>

    <form method="get" style="margin-bottom: 20px;">
        <div class="form-group">
            <label for="server">Pilih Server VOS:</label>
            <select name="server" id="server" onchange="this.form.submit()">
                <option value="vos1" <?= $server === 'vos1' ? 'selected' : '' ?>>VOS NINO</option>
                <option value="vos2" <?= $server === 'vos2' ? 'selected' : '' ?>>VOS 10</option>
		<option value="vos3" <?= $server === 'vos3' ? 'selected' : '' ?>>VOS 71</option>
            </select>
        </div>
    </form>

    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Cari routing...">
    </div>

    <form id="updateForm">
        <input type="hidden" name="server" value="<?= htmlspecialchars($server) ?>">

        <div class="form-group">
            <label for="routingSelect">Pilih Routing:</label>
            <select name="routing_id" id="routingSelect" required>
                <?php foreach ($routingData as $row): ?>
                    <option value="<?= htmlspecialchars($row['id']) ?>">
                        <?= htmlspecialchars($row['name']) ?> | Prefix: <?= htmlspecialchars($row['prefix']) ?> | Nomor: <?= htmlspecialchars($row['rewriterulesincaller']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="nomor">Nomor Baru:</label>
            <input type="text" name="nomor" id="nomor" placeholder="Contoh: 628123456789" required>
        </div>

        <div class="loading" id="loadingIndicator">
            <div class="loading-spinner"></div>
            <p>Memproses perubahan...</p>
        </div>

        <button type="submit" class="btn" id="submitBtn">
            <i class="fas fa-sync-alt"></i> Update Nomor
        </button>
    </form>

    <div id="result"></div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();
    const options = document.querySelectorAll('#routingSelect option');
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        option.style.display = text.includes(searchValue) ? '' : 'none';
    });
});

document.getElementById('updateForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const form = this;
    const submitBtn = document.getElementById('submitBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultDiv = document.getElementById('result');

    submitBtn.disabled = true;
    loadingIndicator.style.display = 'block';
    resultDiv.style.display = 'none';

    fetch('config/process_generate.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.className = 'success';
            resultDiv.innerHTML = `<i class="fas fa-check-circle result-icon"></i><strong>Berhasil!</strong> ${data.message}`;

            const selectedOption = document.querySelector('#routingSelect option:checked');
            selectedOption.textContent = selectedOption.textContent.replace(/Nomor: .+/, `Nomor: ${form.nomor.value}`);
            form.nomor.value = '';
        } else {
            resultDiv.className = 'error';
            resultDiv.innerHTML = `<i class="fas fa-exclamation-circle result-icon"></i><strong>Gagal!</strong> ${data.message}`;
        }
    })
    .catch(error => {
        resultDiv.className = 'error';
        resultDiv.innerHTML = `<i class="fas fa-times-circle result-icon"></i><strong>Error!</strong> ${error.message}`;
    })
    .finally(() => {
        submitBtn.disabled = false;
        loadingIndicator.style.display = 'none';
        resultDiv.style.display = 'block';
        resultDiv.scrollIntoView({ behavior: 'smooth' });
    });
});
</script>
</body>
</html>
