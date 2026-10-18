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

$id_klaim              = $data['id_klaim'] ?? null;
$kode_tiket            = $data['kode_tiket'] ?? null;
$item_id               = $data['item_id'] ?? null;
$claimer_id            = $data['claimer_id'] ?? null;
$bukti_kepemilikan     = $data['bukti_kepemilikan'] ?? null;
$bukti_serah_terima    = $data['bukti_serah_terima'] ?? null;
$status_klaim           = $data['status_klaim'] ?? null;
$admin_id              = $data['admin_id'] ?? null;

if (!$id_klaim) {
    echo json_encode([
        "status" => "error",
        "message" => "ID klaim tidak ditemukan!"
    ]);
    exit;
}

if (
    !$kode_tiket ||
    !$item_id ||
    !$claimer_id ||
    !$status_klaim
) {
    echo json_encode([
        "status" => "error",
        "message" => "Data klaim belum lengkap!"
    ]);
    exit;
}

try {

    $pdo->beginTransaction();

    /*
     * Update seluruh data pada tabel klaim
     */
    $query = "
        UPDATE klaim SET
            kode_tiket = ?,
            item_id = ?,
            claimer_id = ?,
            bukti_kepemilikan = ?,
            bukti_serah_terima = ?,
            status_klaim = ?,
            admin_id = ?
        WHERE id_klaim = ?
    ";

    $stmt = $pdo->prepare($query);

    $stmt->execute([
        $kode_tiket,
        $item_id,
        $claimer_id,
        $bukti_kepemilikan,
        $bukti_serah_terima,
        $status_klaim,
        $admin_id,
        $id_klaim
    ]);

    if (
        $status_klaim === 'Disetujui' ||
        $status_klaim === 'Selesai'
    ) {

        $stmtBarang = $pdo->prepare("
            UPDATE barang
            SET status = 'Diklaim'
            WHERE id_barang = ?
        ");

        $stmtBarang->execute([$item_id]);
    }

    $pdo->commit();

    echo json_encode([
        "status" => "sukses",
        "message" => "Data klaim berhasil diperbarui!"
    ]);
} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "[TITIPIN] klaim/update error: " . $e->getMessage()
    );

    echo json_encode([
        "status" => "error",
        "message" => "Gagal memperbarui data klaim."
    ]);
}
