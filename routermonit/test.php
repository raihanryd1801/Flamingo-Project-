<?php
require __DIR__ . '/../vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Query;

$host = ''; // Add IP di sini
$user = 'Herdy';
$pass = ''; 
$timeout = 10; // Timeout koneksi dalam detik

// Fungsi untuk mendeteksi versi RouterOS
function getRouterOSVersion($host, $user, $pass, $timeout) {
    try {
        $client = new Client([
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'timeout' => $timeout,
        ]);

        $query = new Query('/system/identity/print');
        $response = $client->query($query)->read();

        return $response;
    } catch (\Exception $e) {
        return null;
    }
}

// Coba koneksi menggunakan SSL (port 8729)
try {
    $client = new Client([
        'host' => $host,
        'user' => $user,
        'pass' => $pass,
        'port' => 8729,
        'timeout' => $timeout,
        'ssl' => true,
        'ssl_options' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    // Jika berhasil, cek versi RouterOS
    $version = getRouterOSVersion($host, $user, $pass, $timeout);
    if ($version) {
        echo "Koneksi SSL berhasil!\n";
        print_r($version);
    } else {
        echo "Gagal mendapatkan versi RouterOS.\n";
    }
} catch (\Exception $e) {
    // Jika gagal, coba koneksi non-SSL (port 7729)
    try {
        $client = new Client([
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'port' => 7729,
            'timeout' => $timeout,
        ]);

        // Jika berhasil, cek versi RouterOS
        $version = getRouterOSVersion($host, $user, $pass, $timeout);
        if ($version) {
            echo "Koneksi non-SSL berhasil!\n";
            print_r($version);
        } else {
            echo "Gagal mendapatkan versi RouterOS.\n";
        }
    } catch (\Exception $e) {
        echo "Gagal koneksi ke RouterOS: " . $e->getMessage() . "\n";
    }
}

