<?php
$apiUrl = "http://IP-VOS3000:8080"; // Ganti ke IP server VOS3000 kamu
$username = "noc";
$password = "fid1234";
$routeName = "TSEL_RANDOM";

// Login
$loginData = json_encode(array(
    "username" => $username,
    "password" => $password
));

$loginCurl = curl_init($apiUrl . "/api/login");
curl_setopt_array($loginCurl, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => array("Content-Type: application/json"),
    CURLOPT_POSTFIELDS => $loginData
));

$loginResponse = curl_exec($loginCurl);
if (!$loginResponse) {
    die("Curl Error: " . curl_error($loginCurl));
}
echo "Response login mentah:\n" . $loginResponse . "\n";

$loginJson = json_decode($loginResponse, true);
if (!$loginJson || !isset($loginJson['sessionToken'])) {
    die("Login gagal: " . (isset($loginJson['message']) ? $loginJson['message'] : 'Unknown error'));
}

$sessionToken = $loginJson['sessionToken'];
echo "Login berhasil. Token: " . $sessionToken . "\n";

// Apply route
$applyData = json_encode(array(
    "routeName" => $routeName
));

$applyCurl = curl_init($apiUrl . "/api/applyRoute");
curl_setopt_array($applyCurl, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $sessionToken
    ),
    CURLOPT_POSTFIELDS => $applyData
));

$applyResponse = curl_exec($applyCurl);
curl_close($applyCurl);

$applyJson = json_decode($applyResponse, true);
if (isset($applyJson['success']) && $applyJson['success'] == true) {
    echo "Apply berhasil untuk route: " . $routeName . "\n";
} else {
    echo "Apply gagal: " . (isset($applyJson['message']) ? $applyJson['message'] : $applyResponse) . "\n";
}

