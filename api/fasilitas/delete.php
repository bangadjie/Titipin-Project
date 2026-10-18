<?php
header('Content-Type: application/json');
require '../middleware/admin.php';
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Gunakan method POST!"
    ]);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$id_kerusakan = $data['id_kerusakan'] ?? null;

if (!$id_kerusakan) {
    echo json_encode([
        "status" => "error",
        "message" => "ID kerusakan tidak ditemukan!"
    ]);
    exit;
}

try {
    $stmtCek = $pdo->prepare("
        SELECT foto
        FROM kerusakan_fasilitas
        WHERE id_kerusakan = ?
    ");

    $stmtCek->execute([
        $id_kerusakan
    ]);
    $dataFasilitas =
        $stmtCek->fetch(PDO::FETCH_ASSOC);

    if (!$dataFasilitas) {
        echo json_encode([
            "status" => "error",
            "message" => "Data fasilitas tidak ditemukan!"
        ]);
        exit;
    }

    $stmtDelete = $pdo->prepare("
        DELETE FROM kerusakan_fasilitas
        WHERE id_kerusakan = ?
    ");

    $stmtDelete->execute([
        $id_kerusakan
    ]);
    if (!empty($dataFasilitas['foto'])) {
        $fileFoto =
            '../../assets/uploads/' .
            $dataFasilitas['foto'];
        if (file_exists($fileFoto)) {
            unlink($fileFoto);
        }
    }

    echo json_encode([
        "status" => "sukses",
        "message" => "Data fasilitas berhasil dihapus!"
    ]);
} catch (PDOException $e) {
    error_log(
        "[TITIPIN] fasilitas/delete error: " .
            $e->getMessage()
    );
    echo json_encode([
        "status" => "error",
        "message" => "Gagal menghapus data fasilitas."
    ]);
}
