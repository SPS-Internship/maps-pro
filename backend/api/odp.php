<?php
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// URL API eksternal
$url = 'https://dashboard.kaptennaratel.com/api/v1/api_odp/';

// File cache
$cacheFile = __DIR__ . '/cache_odp.json';
$cacheTime = 300; // cache 5 menit (300 detik)

// 1. Kalau cache ada dan masih valid → langsung kirim cache
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    echo file_get_contents($cacheFile);
    exit;
}

// 2. Kalau cache sudah expired → ambil dari API
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_ENCODING       => "",
    CURLOPT_HTTPHEADER     => [
        "Accept: application/json",
        "User-Agent: PHP-cURL-Proxy"
    ],
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    // Kalau gagal fetch API, fallback pakai cache lama (kalau ada)
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil data ODP',
            'error'   => curl_error($ch)
        ]);
    }
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    // Sama: fallback cache kalau API error
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'API mengembalikan status ' . $httpCode
        ]);
    }
    exit;
}

// 3. Simpan ke cache baru
file_put_contents($cacheFile, $response);

// 4. Kirim hasil ke frontend
echo $response;

// header('Access-Control-Allow-Origin: http://localhost:5173');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');

// header('Content-Type: application/json');

// // URL API eksternal
// $url = 'https://dashboard.kaptennaratel.com/api/v1/api_odp/';

// $ch = curl_init($url);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_TIMEOUT, 60);


// $response = curl_exec($ch);

// if (curl_errno($ch)) {
//     echo json_encode([
//         'success' => false,
//         'message' => 'Gagal mengambil data ODP',
//         'error' => curl_error($ch)
//     ]);
//     curl_close($ch);
//     exit;
// }

// curl_close($ch);

// // Kirim response ke frontend
// echo $response;
