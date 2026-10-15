<?php
$host = 'localhost';
$db   = 'titipin'; 
$user = 'postgres';
$pass = '1234';
$port = "5432";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Koneksi putus: " . $e->getMessage()]);
    exit;
}
?>