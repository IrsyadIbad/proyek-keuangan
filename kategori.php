<?php
// ---- BLOK PENJAGA ----
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
// ---- AKHIR BLOK PENJAGA ----

// TAMBAHKAN BARIS INI
require_once '_includes/functions.php';

require_once '_includes/db.php';

$errors = [];

// --- LOGIKA UNTUK MENAMBAH KATEGORI BARU (CREATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];

    if (empty($name)) { $errors[] = "Nama kategori tidak boleh kosong."; }
    if (empty($type)) { $errors[] = "Jenis kategori harus dipilih."; }

    if (empty($errors)) {
        $sql_check = "SELECT id FROM categories WHERE user_id = ? AND name = ?";
        if($stmt_check = $conn->prepare($sql_check)){
            $stmt_check->bind_param("is", $user_id, $name);
            $stmt_check->execute();
            $stmt_check->store_result();
            if($stmt_check->num_rows > 0){
                $errors[] = "Kategori dengan nama '{$name}' sudah ada.";
            }
            $stmt_check->close();
        }

        if(empty($errors)){
            $sql_insert = "INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)";
            if ($stmt_insert = $conn->prepare($sql_insert)) {
                $stmt_insert->bind_param("iss", $user_id, $name, $type);
                if ($stmt_insert->execute()) {
                    set_notification('success', 'Kategori baru berhasil ditambahkan.');
                    header("Location: kategori.php");
                    exit();
                } else {
                    $errors[] = "Gagal menyimpan kategori.";
                }
                $stmt_insert->close();
            }
        }
    }
}


// --- Mengambil semua kategori milik pengguna (READ) ---
$categories = [];
$sql = "SELECT id, name, type FROM categories WHERE user_id = ? ORDER BY type, name";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    $stmt->close();
}
$conn->close();

include '_includes/_header.php';
?>

<header class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Manajemen Kategori</h1>
    <p class="text-gray-500">Tambah, edit, atau hapus kategori pemasukan dan pengeluaran Anda.</p>
</header>

<div class="bg-white p-6 rounded-xl shadow-lg mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Tambah Kategori Baru</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="kategori.php" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nama Kategori</label>
                <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Jenis</label>
                <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="income">Pemasukan</option>
                    <option value="expense">Pengeluaran</option>
                </select>
            </div>
            <div class="self-end">
                <button type="submit" name="add_category" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md">
                    Tambah Kategori
                </button>
            </div>
        </div>
    </form>
</div>


<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="3" class="text-center py-10 text-gray-500">
                            Anda belum memiliki kategori.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($category['name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php if ($category['type'] == 'income'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Pemasukan
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Pengeluaran
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="edit-kategori.php?id=<?= $category['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                                <button type="button" 
    data-url="hapus-kategori.php?id=<?= $category['id'] ?>" 
    class="tombol-hapus text-red-600 hover:text-red-900">
    Hapus
</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '_includes/_footer.php';
?>