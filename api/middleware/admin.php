<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_nim'])) {
    header('Content-Type: application/json');
    echo json_encode([
        "status"  => "error",
        "message" => "Akses ditolak! Silakan login terlebih dahulu."
    ]);
    exit;
}

if ($_SESSION['user_role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode([
        "status"  => "error",
        "message" => "Akses ditolak! Fitur ini hanya tersedia untuk Admin."
    ]);
    exit;
}
