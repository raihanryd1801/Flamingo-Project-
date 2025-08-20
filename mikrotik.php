<?php
// Konfigurasi
$ip = '103.148.197.14';         // IP MikroTik
$community = 'zabbix';        // SNMP community
$oid = '1.3.6.1.2.1.1.3.0';   // OID uptime

// Cek apakah SNMP tersedia di server PHP
if (!function_exists('snmpget')) {
    die("? Fungsi SNMP tidak tersedia di PHP. Pastikan php-snmp sudah terinstall.");
}

// Aktifkan debugging error SNMP
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
snmp_set_quick_print(true);

// Jalankan SNMP GET
$uptime = @snmpget($ip, $community, $oid);

// Error handling
if ($uptime === false) {
    echo "? Gagal mengambil data dari MikroTik via SNMP.<br>";
    echo "?? IP: $ip<br>";
    echo "?? Community: $community<br>";
    echo "?? OID: $oid<br>";
    echo "?? Kemungkinan error:<br>";
    echo "- IP salah atau unreachable<br>";
    echo "- Port SNMP (161 UDP) diblokir firewall<br>";
    echo "- SNMP tidak diaktifkan di MikroTik<br>";
    echo "- Community string salah<br>";
} else {
    echo "? Berhasil ambil uptime MikroTik: <strong>$uptime</strong><br>";
}
?>

