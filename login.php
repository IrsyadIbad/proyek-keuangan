<?php
if (session_status() === PHP_SESSION_NONE) { session_start();
}

// ---- LOGIKA BARU UNTUK "INGAT SAYA" ----
// Cek jika pengguna BELUM login TAPI PUNYA cookie "ingat saya"
if (!isset($_SESSION['loggedin']) && isset($_COOKIE['remember_me'])) {
    
    require_once '_includes/db.php'; // Hubungkan ke DB jika diperlukan

    // 1. Pisahkan selector dan validator dari cookie
    list($selector, $validator) = explode(':', $_COOKIE['remember_me']);

    // 2. Cari selector di database
    $sql = "SELECT * FROM auth_tokens WHERE selector = ? AND expires >= NOW()";
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $token = $result->fetch_assoc();

            // 3. Verifikasi validator
            if (password_verify($validator, $token['hashed_validator'])) {
                // 4. Jika valid, loginkan pengguna
                // Ambil data pengguna dari tabel users
                $sql_user = "SELECT id, username FROM users WHERE id = ?";
                if($stmt_user = $conn->prepare($sql_user)){
                    $stmt_user->bind_param("i", $token['user_id']);
                    $stmt_user->execute();
                    $user_result = $stmt_user->get_result();
                    $user = $user_result->fetch_assoc();

                    // Set session seolah-olah baru login
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
// ---- AKHIR LOGIKA "INGAT SAYA" ----

require_once '_includes/db.php';

$errors = [];
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = "Email dan password wajib diisi.";
    }

    if (empty($errors)) {
        $sql = "SELECT id, username, password FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    if (isset($_POST['remember_me'])) {
                        $selector = bin2hex(random_bytes(16));
                        $validator = bin2hex(random_bytes(32));
                        $hashed_validator = password_hash($validator, PASSWORD_DEFAULT);
                        $expires = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 hari

                        $sql_token = "INSERT INTO auth_tokens (selector, hashed_validator, user_id, expires) VALUES (?, ?, ?, ?)";
                        if($stmt_token = $conn->prepare($sql_token)) {
                            $stmt_token->bind_param("ssis", $selector, $hashed_validator, $user['id'], $expires);
                            $stmt_token->execute();
                            $stmt_token->close();

                            $cookie_value = $selector . ':' . $validator;
                            setcookie('remember_me', $cookie_value, time() + 60 * 60 * 24 * 30, '/', '', false, true);
                        }
                    }
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $errors[] = "Email atau password salah.";
                }
            } else {
                $errors[] = "Email atau password salah.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
include '_includes/_header.php';
?>

<div class="flex items-center justify-center min-h-[calc(100vh-150px)]">
    <div class="w-full max-w-md">
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Selamat Datang Kembali</h1>
            <p class="text-gray-500 mt-2">Masuk untuk melanjutkan ke dashboard-mu.</p>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-lg">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6">
                    <strong class="font-bold">Sukses!</strong>
                    <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="anda@email.com" required>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Password Anda" required>
                    </div>

                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                            Ingat Saya
                        </label>
                    </div>

                </div>
                <div class="mt-8">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg">Masuk</button>
                </div>
            </form>
        </div>
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Belum punya akun? <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">Daftar di sini</a>
            </p>
        </div>
    </div>
</div>

<?php
include '_includes/_footer.php';
?>