<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e63946;
            --secondary: #f1faee;
            --dark: #1d3557;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            text-align: center;
            padding: 0;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            max-width: 500px;
            width: 90%;
            animation: fadeIn 0.8s ease-out;
        }
        
        .box {
            padding: 40px 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .box::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary);
        }
        
        .icon {
            font-size: 5rem;
            color: var(--primary);
            margin-bottom: 20px;
            animation: bounce 1s infinite alternate;
        }
        
        h1 {
            color: var(--primary);
            margin: 0 0 15px;
            font-size: 2rem;
        }
        
        p {
            color: #555;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(230, 57, 70, 0.3);
        }
        
        .btn:hover {
            background: #c1121f;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(230, 57, 70, 0.4);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-15px); }
        }
        
        @media (max-width: 576px) {
            .box {
                padding: 30px 20px;
            }
            
            .icon {
                font-size: 4rem;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box">
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
            <h1>403 - Akses Ditolak</h1>
            <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Silakan login dengan akun yang memiliki hak akses atau kembali ke halaman utama.</p>
            <a href="index.php" class="btn">
                <i class="fas fa-sign-in-alt"></i> Kembali ke Menu
            </a>
        </div>
    </div>
</body>
</html>