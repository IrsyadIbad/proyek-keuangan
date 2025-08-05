<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '_includes/db.php';

// 1. Hapus cookie "Ingat Saya" jika ada
if (isset($_COOKIE['remember_me'])) {
    
    // Hapus token dari database agar tidak bisa digunakan lagi
    list($selector, $validator) = explode(':', $_COOKIE['remember_me']);
    $sql = "DELETE FROM auth_tokens WHERE selector = ?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $stmt->close();
    }
    
    // Hapus cookie dari browser dengan mengatur waktu kedaluwarsa di masa lalu
    setcookie('remember_me', '', time() - 3600, '/');
}

// 2. Hapus semua variabel session
$_SESSION = [];

// 3. Hancurkan session
session_destroy();

if (isset($conn)) {
    $conn->close();
}

// 4. Arahkan kembali ke halaman login
header("Location: login.php");
exit();
?>