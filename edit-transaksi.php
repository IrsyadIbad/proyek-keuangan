<?php
// ===================================================================
//  BAGIAN 1: PROSES DULU (SEMUA LOGIKA PHP TANPA HTML)
// ===================================================================

// ---- BLOK PENJAGA & FUNGSI ----
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
require_once '_includes/functions.php';
require_once '_includes/db.php';
// ---- AKHIR BLOK ----

// Inisialisasi variabel
$transaction = null;
$errors = [];

// --- LOGIKA UPDATE DATA SAAT FORM DI-SUBMIT (METHOD POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil semua data baru dari form
    $transaction_id = $_POST['id']; // Ambil dari input hidden
    $amount = str_replace('.', '', $_POST['amount']); // Hapus titik dari amount
    $type = $_POST['type'];
    $category_id = $_POST['category_id'];
    $transaction_date = $_POST['transaction_date'];
    $description = trim($_POST['description']);

    // Siapkan SQL statement untuk UPDATE
    $sql_update = "UPDATE transactions SET amount = ?, type = ?, category_id = ?, description = ?, transaction_date = ? WHERE id = ? AND user_id = ?";
    
    if ($stmt_update = $conn->prepare($sql_update)) {
        // Bind parameter
        $stmt_update->bind_param("dsissii", $amount, $type, $category_id, $description, $transaction_date, $transaction_id, $user_id);
        
        if ($stmt_update->execute()) {
            // PENTING: Atur notifikasi SEBELUM redirect
            set_notification('info', 'Transaksi berhasil diperbarui.');
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Error saat memperbarui data: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

// --- LOGIKA MENGAMBIL DATA AWAL UNTUK DITAMPILKAN (METHOD GET) ---
$transaction_id_get = $_GET['id'] ?? null;
if (!$transaction_id_get && empty($errors)) {
    // Redirect jika tidak ada ID dan tidak ada eror dari proses POST
    header("Location: index.php");
    exit();
}

// Ambil data transaksi yang spesifik untuk mengisi form
$sql_select = "SELECT * FROM transactions WHERE id = ? AND user_id = ?";
if ($stmt_select = $conn->prepare($sql_select)) {
    $stmt_select->bind_param("ii", $transaction_id_get, $user_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    if ($result->num_rows === 1) {
        $transaction = $result->fetch_assoc();
    } else {
        // Jika transaksi tidak ditemukan, beri notifikasi dan redirect
        set_notification('danger', 'Transaksi tidak ditemukan atau Anda tidak memiliki akses.');
        header("Location: index.php");
        exit();
    }
    $stmt_select->close();
}

// Ambil data kategori untuk dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories WHERE user_id = ? ORDER BY name";
if ($stmt_cat = $conn->prepare($sql_categories)) {
    $stmt_cat->bind_param("i", $user_id);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    while($row = $result_cat->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt_cat->close();
}
$conn->close();

// ===================================================================
//  BAGIAN 2: BARU CETAK HTML SETELAH SEMUA LOGIKA SELESAI
// ===================================================================
include '_includes/_header.php';
?>

<header class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Edit Transaksi</h1>
    <p class="text-gray-500">Perbarui data transaksi Anda.</p>
</header>

<div class="bg-white p-8 rounded-xl shadow-lg">
    <form action="edit-transaksi.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($transaction['id']) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div x-data="{ 
                amount: '<?= number_format($transaction['amount'], 0, ',', '.') ?>',
                get rawAmount() { return this.amount.replace(/\./g, '') },
                formatAmount() {
                    if (!this.amount) return;
                    let number = this.amount.replace(/\./g, '');
                    this.amount = new Intl.NumberFormat('id-ID').format(number);
                }
            }">
                <label for="amount_display" class="block text-sm font-medium text-gray-700">Jumlah (Rp)</label>
                <input type="text" id="amount_display" x-model="amount" @input="formatAmount" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="50.000" inputmode="numeric" required>
                <input type="hidden" name="amount" :value="rawAmount">
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Jenis Transaksi</label>
                <select name="type" id="type" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="expense" <?= $transaction['type'] == 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
                    <option value="income" <?= $transaction['type'] == 'income' ? 'selected' : '' ?>>Pemasukan</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="category_id" class="block text-sm font-medium text-gray-700">Kategori</label>
                <select name="category_id" id="category_id" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $transaction['category_id'] == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="transaction_date" class="block text-sm font-medium text-gray-700">Tanggal</label>
                <input type="date" name="transaction_date" id="transaction_date" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" value="<?= htmlspecialchars($transaction['transaction_date']) ?>" required>
            </div>
            
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi Singkat</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm"><?= htmlspecialchars($transaction['description']) ?></textarea>
            </div>
        </div>

        <div class="mt-8 text-right">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<?php include '_includes/_footer.php'; ?>