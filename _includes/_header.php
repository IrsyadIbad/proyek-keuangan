<?php
// Selalu mulai session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logika "Ingat Saya"
if (!isset($_SESSION['loggedin']) && isset($_COOKIE['remember_me'])) {
    require_once '_includes/db.php'; 
    list($selector, $validator) = explode(':', $_COOKIE['remember_me']);
    $sql = "SELECT * FROM auth_tokens WHERE selector = ? AND expires >= NOW()";
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $token = $result->fetch_assoc();
            if (password_verify($validator, $token['hashed_validator'])) {
                $sql_user = "SELECT id, username FROM users WHERE id = ?";
                if($stmt_user = $conn->prepare($sql_user)){
                    $stmt_user->bind_param("i", $token['user_id']);
                    $stmt_user->execute();
                    $user = $stmt_user->get_result()->fetch_assoc();
                    session_regenerate_id();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $stmt_user->close();
                }
            }
        }
        $stmt->close();
    }
    $conn->close();
}

// Mendapatkan nama file halaman saat ini
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="id">
<head>

<style>
    /* ... style untuk .card-animate tetap di sini ... */
    
    /* Wrapper untuk menampung semua toast */
    #toast-container {
        position: fixed;
        top: 1.5rem; /* 24px */
        right: 1.5rem; /* 24px */
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.75rem; /* 12px */
    }

    /* Style untuk satu buah toast */
    .toast {
        /* Menggunakan @apply dari Tailwind untuk styling dasar */
        padding: 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
        
        /* Properti untuk animasi */
        opacity: 0;
        transform: translateX(100%);
        transition: transform 0.4s ease-out, opacity 0.4s ease-out;
    }

    /* State ketika toast ditampilkan */
    .toast.show {
        opacity: 1;
        transform: translateX(0);
    }
</style>
</head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Keuangan Pribadi</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('modal', { isOpen: false, deleteUrl: '' });
        });
    </script>

    <style>
        .card-animate { opacity: 0; transform: translateY(20px); transition: opacity 0.5s ease-out, transform 0.5s ease-out; }
        .card-animate.visible { opacity: 1; transform: translateY(0); }
    </style>
</head>
<body class="bg-gray-50">

<nav x-data="{ open: false }" class="bg-white border-b border-gray-200">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <a href="index.php" class="text-2xl font-bold text-blue-600">Keuanganku</a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <?php if (isset($_SESSION['loggedin'])): ?>
                            <a href="index.php" class="px-3 py-2 rounded-md text-sm font-medium <?= ($currentPage == 'index.php') ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-100' ?>">Dashboard</a>
                            <a href="transaksi.php" class="px-3 py-2 rounded-md text-sm font-medium <?= ($currentPage == 'transaksi.php') ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-100' ?>">Transaksi</a>
                            <a href="laporan.php" class="px-3 py-2 rounded-md text-sm font-medium <?= ($currentPage == 'laporan.php') ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-100' ?>">Laporan</a>
                            <a href="kategori.php" class="px-3 py-2 rounded-md text-sm font-medium <?= ($currentPage == 'kategori.php') ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-100' ?>">Kategori</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="hidden md:block">
                <div class="ml-4 flex items-center md:ml-6">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <span class="text-gray-700 text-sm mr-4">Halo, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                        <a href="logout.php" class="px-3 py-2 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">Login</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="-mr-2 flex md:hidden">
                <button @click="open = !open" type="button" class="bg-gray-100 inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:bg-gray-200"></button>
            </div>
        </div>
    </div>

    <div x-show="open" class="md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
             <?php if (isset($_SESSION['loggedin'])): ?>
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage == 'index.php') ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-100' ?>">Dashboard</a>
                <a href="transaksi.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage == 'transaksi.php') ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-100' ?>">Transaksi</a>
                <a href="laporan.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage == 'laporan.php') ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-100' ?>">Laporan</a>
                <a href="kategori.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage == 'kategori.php') ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-100' ?>">Kategori</a>
                <div class="border-t border-gray-200 mt-2 pt-2">
                    <span class="block px-3 py-2 rounded-md text-base font-medium text-gray-700">Halo, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                    <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50">Logout</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container mx-auto p-4 sm:p-6 lg:p-8">