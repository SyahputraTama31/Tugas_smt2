<?php
session_start();
include "koneksi.php";

$username = $_POST['username'] ?? '';
$password = $_POST['Password'] ?? '';

// Validasi input kosong
if (empty($username) || empty($password)) {
    $_SESSION['error'] = 'Username dan password harus diisi';
    header('Location: lgn.php');
    exit;
}

try {
    // Cari user berdasarkan username
    $stmt = mysqli_prepare($conn, "SELECT id, username, nama, password, Role FROM users WHERE username = ?");
    
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan query");
    }
    
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    // Cek apakah user ditemukan
    if (!$user) {
        $_SESSION['error'] = 'Username tidak ditemukan';
        header('Location: lgn.php');
        exit;
    }

    // Cek apakah password benar
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'Password salah';
        header('Location: lgn.php');
        exit;
    }

    // Login berhasil — simpan data ke session
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['nama']     = $user['nama'];
    $_SESSION['role']     = $user['Role'];

    $role = strtolower(trim($user['Role']));

    if ($role === 'admin' || $role === 'superadmin') {
        header('Location: ../Admin/Home.php');
    } elseif ($role === 'user') {
        header('Location: ../Usr/home.php');
    }
    exit;

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['error'] = 'Debug: ' . $e->getMessage(); // SEMENTARA untuk debug
    header('Location: lgn.php');
    exit;
}

?>