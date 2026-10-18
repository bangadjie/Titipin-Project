<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; // Hanya Admin
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "status" => "error",
        "message" => "Gunakan method post!"
    ]);
    exit;
}

// $json = file_get_contents('php://input');
// $data = json_decode($json, true);

$id_kerusakan       = $_POST['id_kerusakan'] ?? null;
$nama_fasilitas     = $_POST['nama_fasilitas'] ?? '';
$kategori_id        = $_POST['kategori_id'] ?? null;
$lokasi_id          = $_POST['lokasi_id'] ?? null;
$deskripsi_kerusakan = $_POST['deskripsi_kerusakan'] ?? '';
$status             = $_POST['status'] ?? '';
$catatan_teknisi    = $_POST['catatan_teknisi'] ?? '';

if (!$id_kerusakan) {
    echo json_encode([
        "status" => "error",
        "message" => "ID kerusakan tidak ditemukan!"
    ]);
    exit;
}
if (!$nama_fasilitas || !$deskripsi_kerusakan || !$status) {
    echo json_encode([
        "status" => "error",
        "message" => "Data laporan belum lengkap!"
    ]);
    exit;
}

try {

    $stmtLama = $pdo->prepare("
        SELECT foto
        FROM kerusakan_fasilitas
        WHERE id_kerusakan = ?
    ");

    $stmtLama->execute([$id_kerusakan]);

    $dataLama = $stmtLama->fetch(PDO::FETCH_ASSOC);

    if (!$dataLama) {
        echo json_encode([
            "status" => "error",
            "message" => "Data fasilitas tidak ditemukan!"
        ]);
        exit;
    }

    $nama_file_foto = $dataLama['foto'];

    if (
        isset($_FILES['foto']) &&
        $_FILES['foto']['error'] === UPLOAD_ERR_OK
    ) {

        $file = $_FILES['foto'];

        $ekstensi = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        $allowed_ext = [
            'jpg',
            'jpeg',
            'png'
        ];

        $allowed_mime = [
            'image/jpeg',
            'image/png'
        ];

        $max_size = 2 * 1024 * 1024;
        if ($file['size'] > $max_size) {

            echo json_encode([
                "status" => "error",
                "message" => "Ukuran foto maksimal 2MB!"
            ]);

            exit;
        }

        if (!in_array($ekstensi, $allowed_ext)) {

            echo json_encode([
                "status" => "error",
                "message" => "Format foto tidak valid! Gunakan JPG atau PNG."
            ]);

            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mime = finfo_file(
            $finfo,
            $file['tmp_name']
        );

        finfo_close($finfo);

        if (!in_array($mime, $allowed_mime)) {

            echo json_encode([
                "status" => "error",
                "message" => "Tipe file tidak diizinkan!"
            ]);

            exit;
        }

        $nama_file_foto =
            'fasilitas_' .
            time() . '_' .
            uniqid() .
            '.' .
            $ekstensi;

        $folder_simpan =
            '../../assets/uploads/' .
            $nama_file_foto;

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $folder_simpan
            )
        ) {

            echo json_encode([
                "status" => "error",
                "message" => "Gagal menyimpan foto baru!"
            ]);

            exit;
        }


        if (!empty($dataLama['foto'])) {

            $fotoLama =
                '../../assets/uploads/' .
                $dataLama['foto'];

            if (file_exists($fotoLama)) {
                unlink($fotoLama);
            }
        }
    }

    $query = "
        UPDATE kerusakan_fasilitas
        SET
            nama_fasilitas = ?,
            kategori_id = ?,
            lokasi_id = ?,
            deskripsi_kerusakan = ?,
            foto = ?,
            status = ?,
            catatan_teknisi = ?
        WHERE id_kerusakan = ?
    ";

    $stmt = $pdo->prepare($query);

    $stmt->execute([
        $nama_fasilitas,
        $kategori_id,
        $lokasi_id,
        $deskripsi_kerusakan,
        $nama_file_foto,
        $status,
        $catatan_teknisi,
        $id_kerusakan
    ]);

    echo json_encode([
        "status" => "sukses",
        "message" => "Data fasilitas berhasil diperbarui!"
    ]);
} catch (PDOException $e) {

    error_log(
        "[TITIPIN] fasilitas/update error: " .
            $e->getMessage()
    );

    echo json_encode([
        "status" => "error",
        "message" => "Gagal memperbarui data fasilitas."
    ]);
}
