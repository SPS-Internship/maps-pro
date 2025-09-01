<?php
$host = 'localhost';
$port = '5434'; // default kamu pakai 5434
$dbname = 'naratel_area';
$user = 'postgres';
$password = '12345';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "✅ Connected to PostgreSQL";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}
?>
