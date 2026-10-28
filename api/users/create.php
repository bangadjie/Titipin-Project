<?php
header('Content-Type: application/json');
require '../middleware/admin.php'; // Hanya Admin
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Gunakan method POST!"]);
    exit;
}

$data = $_POST;
if (empty($data)) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }
}

$nim          = trim((string) ($data['nim'] ?? ''));
$username     = trim((string) ($data['username'] ?? ''));
$nama_lengkap = trim((string) ($data['nama_lengkap'] ?? ''));
$email        = trim((string) ($data['email'] ?? ''));
$password     = (string) ($data['password'] ?? '');
$role         = trim((string) ($data['role'] ?? 'Mahasiswa'));
$jurusan      = trim((string) ($data['jurusan'] ?? ''));
$kelas        = trim((string) ($data['kelas'] ?? ''));
$no_telepon   = trim((string) ($data['no_telepon'] ?? ''));

if ($role === '') {
    $role = 'Mahasiswa';
}

if ($nim === '' || $username === '' || $nama_lengkap === '' || $email === '' || $password === '') {
    echo json_encode(["status" => "error", "message" => "NIM, username, nama lengkap, email, dan password wajib diisi!"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Format email tidak valid!"]);
    exit;
}

if (!in_array($role, ['Admin', 'Mahasiswa'], true)) {
    echo json_encode(["status" => "error", "message" => "Role harus Admin atau Mahasiswa!"]);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (nim, username, nama_lengkap, email, password, role, jurusan, kelas, no_telepon)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $nim,
        $username,
        $nama_lengkap,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
        $jurusan === '' ? null : $jurusan,
        $kelas === '' ? null : $kelas,
        $no_telepon === '' ? null : $no_telepon
    ]);

    echo json_encode(["status" => "sukses", "message" => "User berhasil dibuat!"]);
} catch (PDOException $e) {
    error_log("[TITIPIN] users/create error: " . $e->getMessage());

    if ($e->getCode() === '23505') {
        echo json_encode(["status" => "error", "message" => "NIM atau email sudah digunakan!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan data user."]);
    }
}
?>