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

$nim = trim((string) ($data['nim'] ?? ''));

if ($nim === '') {
    echo json_encode(["status" => "error", "message" => "NIM user tidak ditemukan!"]);
    exit;
}

try {
    $stmtCek = $pdo->prepare("SELECT nim FROM users WHERE nim = ?");
    $stmtCek->execute([$nim]);

    if (!$stmtCek->fetch()) {
        echo json_encode(["status" => "error", "message" => "User tidak ditemukan!"]);
        exit;
    }

    $set    = [];
    $params = [];
    $roleBaru = null;

    if (array_key_exists('username', $data)) {
        $username = trim((string) $data['username']);
        if ($username === '') {
            echo json_encode(["status" => "error", "message" => "Username tidak boleh kosong!"]);
            exit;
        }
        $set[]    = 'username = ?';
        $params[] = $username;
    }

    if (array_key_exists('nama_lengkap', $data)) {
        $nama_lengkap = trim((string) $data['nama_lengkap']);
        if ($nama_lengkap === '') {
            echo json_encode(["status" => "error", "message" => "Nama lengkap tidak boleh kosong!"]);
            exit;
        }
        $set[]    = 'nama_lengkap = ?';
        $params[] = $nama_lengkap;
    }

    if (array_key_exists('email', $data)) {
        $email = trim((string) $data['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Format email tidak valid!"]);
            exit;
        }
        $set[]    = 'email = ?';
        $params[] = $email;
    }

    if (array_key_exists('role', $data)) {
        $roleBaru = trim((string) $data['role']);
        if (!in_array($roleBaru, ['Admin', 'Mahasiswa'], true)) {
            echo json_encode(["status" => "error", "message" => "Role harus Admin atau Mahasiswa!"]);
            exit;
        }
        $set[]    = 'role = ?';
        $params[] = $roleBaru;
    }

    foreach (['jurusan', 'kelas', 'no_telepon'] as $kolom) {
        if (array_key_exists($kolom, $data)) {
            $nilai    = trim((string) $data[$kolom]);
            $set[]    = $kolom . ' = ?';
            $params[] = $nilai === '' ? null : $nilai;
        }
    }

    if (array_key_exists('password', $data)) {
        $password = (string) $data['password'];
        if ($password !== '') {
            $set[]    = 'password = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    if (empty($set)) {
        echo json_encode(["status" => "error", "message" => "Tidak ada data yang diubah!"]);
        exit;
    }

    $params[] = $nim;
    $stmt     = $pdo->prepare("UPDATE users SET " . implode(', ', $set) . " WHERE nim = ?");
    $stmt->execute($params);

    if ($roleBaru !== null && $nim === ($_SESSION['user_nim'] ?? null)) {
        $_SESSION['user_role'] = $roleBaru;
    }

    echo json_encode(["status" => "sukses", "message" => "User berhasil diperbarui!"]);
} catch (PDOException $e) {
    error_log("[TITIPIN] users/update error: " . $e->getMessage());

    if ($e->getCode() === '23505') {
        echo json_encode(["status" => "error", "message" => "Email sudah digunakan!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memperbarui data user."]);
    }
}
?>