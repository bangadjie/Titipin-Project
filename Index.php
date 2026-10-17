<?php
// Set header agar selalu mengembalikan format JSON
header('Content-Type: application/json');

// Tampilkan pesan sambutan dasar
echo json_encode([
    "status" => "sukses",
    "message" => "Selamat datang di API TITIPIN (Project Based Learning)",
    "version" => "1.0",
    "endpoints_tersedia" => [
        "auth" => "/api/auth/",
        "barang" => "/api/barang/",
        "klaim" => "/api/klaim/",
        "fasilitas" => "/api/fasilitas/"
    ]
]);
