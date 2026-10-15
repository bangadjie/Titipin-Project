<?php
session_start();
header('Content-Type: application/json');

// Menghapus semua variabel sesi
session_unset();

// Menghancurkan sesi sepenuhnya
session_destroy();

echo json_encode([
    "status" => "sukses",
    "message" => "Berhasil keluar! Sampai jumpa lagi."
]);
