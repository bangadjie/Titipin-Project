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

$id_klaim = $data['id_klaim'] ?? null;

if (!$id_klaim) {
    echo json_encode([
        "status" => "error",
        "message" => "ID klaim tidak ditemukan!"
    ]);
    exit;
}

try {

    $pdo->beginTransaction();

    /*
     * Ambil data klaim terlebih dahulu
     * untuk mendapatkan item_id dan bukti file.
     */
    $stmtCek = $pdo->prepare("
        SELECT item_id, bukti_kepemilikan, bukti_serah_terima
        FROM klaim
        WHERE id_klaim = ?
    ");

    $stmtCek->execute([$id_klaim]);

    $klaim = $stmtCek->fetch(PDO::FETCH_ASSOC);

    if (!$klaim) {

        $pdo->rollBack();

        echo json_encode([
            "status" => "error",
            "message" => "Data klaim tidak ditemukan!"
        ]);

        exit;
    }

    /*
     * Hapus data klaim dari database
     */
    $stmtDelete = $pdo->prepare("
        DELETE FROM klaim
        WHERE id_klaim = ?
    ");

    $stmtDelete->execute([$id_klaim]);

    /*
     * Kalau ada file bukti kepemilikan,
     * hapus juga file tersebut dari folder uploads.
     */
    if (!empty($klaim['bukti_kepemilikan'])) {

        $fileBukti = '../../assets/uploads/' .
                     $klaim['bukti_kepemilikan'];

        if (file_exists($fileBukti)) {
            unlink($fileBukti);
        }
    }

    /*
     * Kalau ada file bukti serah terima,
     * hapus juga.
     */
    if (!empty($klaim['bukti_serah_terima'])) {

        $fileSerahTerima = '../../assets/uploads/' .
                           $klaim['bukti_serah_terima'];

        if (file_exists($fileSerahTerima)) {
            unlink($fileSerahTerima);
        }
    }

    $pdo->commit();

    echo json_encode([
        "status" => "sukses",
        "message" => "Data klaim berhasil dihapus!"
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "[TITIPIN] klaim/delete error: " . $e->getMessage()
    );

    echo json_encode([
        "status" => "error",
        "message" => "Gagal menghapus data klaim."
    ]);
}