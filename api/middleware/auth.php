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
