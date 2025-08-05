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

$errors = [];

// --- LOGIKA MENYIMPAN TRANSAKSI & KATEGORI BARU (METHOD POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ambil data dari form
    $amount = str_replace('.', '', $_POST['amount']);
    $type = $_POST['type'];
    $category_name = trim($_POST['category_name']);
    $transaction_date = $_POST['transaction_date'];
    $description = trim($_POST['description']);
    $category_id = null;

    // Validasi dasar
    if (empty($category_name)) { $errors[] = "Nama kategori tidak boleh kosong."; }
    if (empty($amount)) { $errors[] = "Jumlah tidak boleh kosong."; }

    if (empty($errors)) {
        // 1. Cek apakah kategori sudah ada di database
        $sql_find_category = "SELECT id FROM categories WHERE name = ? AND user_id = ?";
        if($stmt_find = $conn->prepare($sql_find_category)) {
            $stmt_find->bind_param("si", $category_name, $user_id);
            $stmt_find->execute();
            $result_find = $stmt_find->get_result();
            if($result_find->num_rows > 0) {
                $category_id = $result_find->fetch_assoc()['id'];
            } else {
                // 2. Jika tidak ada, buat kategori baru
                $sql_insert_category = "INSERT INTO categories (user_id, name, type) VALUES (?, ?, ?)";
                if($stmt_insert = $conn->prepare($sql_insert_category)) {
                    $stmt_insert->bind_param("iss", $user_id, $category_name, $type);
                    $stmt_insert->execute();
                    $category_id = $conn->insert_id;
                    $stmt_insert->close();
                }
            }
            $stmt_find->close();
        }

        // 3. Simpan transaksi dengan category_id yang benar
        if ($category_id) {
            $sql_insert_trx = "INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
            if ($stmt_trx = $conn->prepare($sql_insert_trx)) {
                $stmt_trx->bind_param("iidsss", $user_id, $category_id, $amount, $type, $description, $transaction_date);
                if ($stmt_trx->execute()) {
                    set_notification('success', 'Transaksi baru berhasil ditambahkan.');
                    header("Location: index.php");
                    exit();
                } else {
                    $errors[] = "Gagal menyimpan transaksi.";
                }
                $stmt_trx->close();
            }
        } else {
            $errors[] = "Error: Tidak bisa menemukan atau membuat kategori.";
        }
    }
}

// --- LOGIKA MENGAMBIL DATA KATEGORI UNTUK FORM DROPDOWN ---
$categories = [];
$sql_categories = "SELECT id, name FROM categories WHERE user_id = ? ORDER BY name";
if($stmt_cat = $conn->prepare($sql_categories)) {
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
    <h1 class="text-2xl font-bold text-gray-800">Tambah Transaksi Baru</h1>
    <p class="text-gray-500">Catat pemasukan atau pengeluaran Anda.</p>
</header>

<div class="bg-white p-8 rounded-xl shadow-lg">
    <form action="tambah-transaksi.php" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div x-data="{ 
                amount: '',
                get rawAmount() { return this.amount.replace(/\./g, '') },
                formatAmount() {
                    if (!this.amount) return;
                    let number = this.amount.replace(/\./g, '');
                    this.amount = new Intl.NumberFormat('id-ID').format(number);
                }
            }">
                <label for="amount_display" class="block text-sm font-medium text-gray-700">Jumlah (Rp)</label>
                <input type="text" id="amount_display" x-model="amount" @input="formatAmount" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" placeholder="50.000" inputmode="numeric" required>
                <input type="hidden" name="amount" :value="rawAmount">
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Jenis Transaksi</label>
                <select name="type" id="type" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" required>
                    <option value="expense">Pengeluaran</option>
                    <option value="income">Pemasukan</option>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label for="category-input" class="block text-sm font-medium text-gray-700">Kategori</label>
                <div class="relative">
                    <input type="text" 
                           id="category-input"
                           name="category_name" 
                           class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Ketik atau pilih kategori"
                           autocomplete="off" 
                           required>
                    
                    <div id="category-dropdown" 
                         class="absolute hidden z-10 w-full mt-1 bg-white rounded-md shadow-lg max-h-60 overflow-auto border">
                        </div>
                </div>
            </div>

            <div>
                <label for="transaction_date" class="block text-sm font-medium text-gray-700">Tanggal</label>
                <input type="date" name="transaction_date" id="transaction_date" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" value="<?= date('Y-m-d'); ?>" required>
            </div>
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi Singkat</label>
                <textarea name="description" id="description" rows="3" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" placeholder="Contoh: Bayar tagihan listrik"></textarea>
            </div>
        </div>
        <div class="mt-8 text-right">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg">
                Simpan Transaksi
            </button>
        </div>
    </form>
</div>

<script>
    // Melempar data kategori dari variabel PHP $categories ke variabel JavaScript `categoriesData`
    const categoriesData = <?= json_encode($categories) ?>;
</script>

<?php include '_includes/_footer.php'; ?>