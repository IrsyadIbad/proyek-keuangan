<?php
// ---- BLOK PENJAGA ----
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
// ---- AKHIR BLOK PENJAGA ----

require_once '_includes/functions.php';
require_once '_includes/db.php';
$errors = [];
$category = null;

// --- BAGIAN 1: MENGAMBIL ID KATEGORI DARI URL ---
$category_id = $_GET['id'] ?? null;
if (!$category_id) {
    set_notification('info', 'Kategori berhasil diperbarui.');
    header("Location: kategori.php"); // Jika tidak ada ID, kembali ke halaman utama
    exit();
}

// --- BAGIAN 2: LOGIKA PROSES UPDATE DATA (METHOD POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $type = $_POST['type'];

    if (empty($name)) { $errors[] = "Nama kategori tidak boleh kosong."; }
    if (empty($type)) { $errors[] = "Jenis kategori harus dipilih."; }

    if (empty($errors)) {
        // Cek duplikasi nama (kecuali untuk kategori itu sendiri)
        $sql_check = "SELECT id FROM categories WHERE user_id = ? AND name = ? AND id != ?";
        if($stmt_check = $conn->prepare($sql_check)){
            $stmt_check->bind_param("isi", $user_id, $name, $category_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if($stmt_check->num_rows > 0){
                $errors[] = "Kategori dengan nama '{$name}' sudah ada.";
            }
            $stmt_check->close();
        }

        if(empty($errors)){
            $sql_update = "UPDATE categories SET name = ?, type = ? WHERE id = ? AND user_id = ?";
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("ssii", $name, $type, $category_id, $user_id);
                if ($stmt_update->execute()) {
                    header("Location: kategori.php");
                    exit();
                } else {
                    $errors[] = "Gagal memperbarui kategori.";
                }
                $stmt_update->close();
            }
        }
    }
}

// --- BAGIAN 3: AMBIL DATA SAAT INI UNTUK DITAMPILKAN DI FORM ---
$sql_select = "SELECT id, name, type FROM categories WHERE id = ? AND user_id = ?";
if ($stmt_select = $conn->prepare($sql_select)) {
    $stmt_select->bind_param("ii", $category_id, $user_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    if ($result->num_rows === 1) {
        $category = $result->fetch_assoc();
    } else {
        // Jika kategori tidak ditemukan atau bukan milik user, redirect
        header("Location: kategori.php");
        exit();
    }
    $stmt_select->close();
}

$conn->close();
include '_includes/_header.php';
?>

<header class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Edit Kategori</h1>
    <p class="text-gray-500">Perbarui nama atau jenis kategori Anda.</p>
</header>

<div class="bg-white p-6 rounded-xl shadow-lg">

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($category): // Hanya tampilkan form jika kategori ditemukan ?>
    <form action="edit-kategori.php?id=<?= $category['id'] ?>" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($category['name']) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Jenis</label>
                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="income" <?= ($category['type'] == 'income') ? 'selected' : '' ?>>Pemasukan</option>
                    <option value="expense" <?= ($category['type'] == 'expense') ? 'selected' : '' ?>>Pengeluaran</option>
                </select>
            </div>
            <div class="self-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
    <?php else: ?>
        <p class="text-center text-gray-500">Kategori tidak ditemukan.</p>
    <?php endif; ?>
</div>

<?php
include '_includes/_footer.php';
?>