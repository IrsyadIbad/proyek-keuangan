<?php
// Mulai session untuk menangani pesan feedback
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Inisialisasi array untuk menampung pesan error
$errors = [];

// Cek apakah form sudah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Masukkan file koneksi database
    require_once '_includes/db.php';

    // 2. Ambil dan bersihkan data dari form
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 3. Validasi Input
    if (empty($username)) {
        $errors[] = "Username wajib diisi.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password minimal harus 8 karakter.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Konfirmasi password tidak cocok.";
    }

    // 4. Cek apakah email sudah terdaftar (jika tidak ada error validasi sebelumnya)
    if (empty($errors)) {
        $sql_check = "SELECT id FROM users WHERE email = ?";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "Email ini sudah terdaftar. Silakan gunakan email lain.";
            }
            $stmt_check->close();
        }
    }
    
    // 5. Jika semua validasi lolos, lanjutkan proses
    if (empty($errors)) {
        // Enkripsi (hash) password demi keamanan
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Siapkan SQL untuk menyimpan pengguna baru
        $sql_insert = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password);
            
            // Eksekusi dan redirect
            if ($stmt_insert->execute()) {
                // Set pesan sukses di session
                $_SESSION['success_message'] = "Registrasi berhasil! Silakan masuk.";
                // Arahkan ke halaman login
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Terjadi kesalahan. Silakan coba lagi.";
            }
            $stmt_insert->close();
        }
    }
    $conn->close();
}

// Memasukkan file header
include '_includes/_header.php';
?>

<div class="flex items-center justify-center min-h-[calc(100vh-150px)]">
    <div class="w-full max-w-md">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                Buat Akun Baru
            </h1>
            <p class="text-gray-500 mt-2">Mulai kelola keuanganmu hari ini.</p>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-lg">
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
                    <strong class="font-bold">Oops!</strong>
                    <ul class="mt-2 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" id="username" value="<?= htmlspecialchars($username ?? '') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: budi santoso" required>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email ?? '') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="anda@email.com" required>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Minimal 8 karakter" required>
                    </div>
                    
                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                        <input type="password" name="password_confirm" id="password_confirm" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Ketik ulang password" required>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition duration-200">
                        Daftar
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Sudah punya akun? 
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    Masuk di sini
                </a>
            </p>
        </div>
    </div>
</div>

<?php
include '_includes/_footer.php';
?>