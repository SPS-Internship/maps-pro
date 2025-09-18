<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

/*  Get parameter lat & lng dari request */
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

if ($lat === null || $lng === null) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal Mendapatkan lokasi. Coba Lagi"
    ]);
    exit;
}

/* Lokasi cache & API ODP */
$cacheFile = __DIR__ . "/cache_odp.json";
$cacheTime = 300; // 5 menit
$apiUrl = "https://dashboard.kaptennaratel.com/api/v1/api_odp/";

/* Get data ODP  */
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    // Get from cache
    $odpData = file_get_contents($cacheFile);
} else {
    // Fetch from API
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ["Accept: application/json"]
    ]);
    $odpData = curl_exec($ch);

    if (curl_errno($ch)) {
        // Kalau gagal, fallback ke cache lama
        if (file_exists($cacheFile)) {
            $odpData = file_get_contents($cacheFile);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Gagal mengambil data. Coba Lagi",
                // "message" => "Gagal ambil data ODP: " . curl_error($ch)
            ]);
            curl_close($ch);
            exit;
        }
    }
    curl_close($ch);

    // Simpan cache baru
    if ($odpData) {
        file_put_contents($cacheFile, $odpData);
    }
}

$odpList = json_decode($odpData, true);

if (!is_array($odpList)) {
    echo json_encode([
        "success" => false,
        "message" => "Format tidak valid. Coba Lagi"
        // "message" => "Format data ODP tidak valid"
    ]);
    exit;
}

/* Haversine (hitung jarak antar titik)*/
function haversine($lat1, $lon1, $lat2, $lon2)
{
    $R = 6371000; // jari-jari bumi dalam meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c; // hasil meter
}

/*  Loop semua ODP & cek coverage (radius 200 meter) */
$radius = 200; // meter

foreach ($odpList as $odp) {
    $odpLat = isset($odp['latitude']) ? floatval($odp['latitude']) : 0;
    $odpLng = isset($odp['longitude']) ? floatval($odp['longitude']) : 0;

    if ($odpLat === 0 || $odpLng === 0) {
        continue; // skip kalau data ODP tidak valid
    }

    $distance = haversine($lat, $lng, $odpLat, $odpLng);

    if ($distance <= $radius) {
        echo json_encode(true);
        exit;
    }
}

/* if no ODP dalam radius */
echo json_encode(false);