<?php
// ---- BLOK PENJAGA ----
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id']; // Ambil user_id dari session
// ---- AKHIR BLOK PENJAGA ----
// --- TAHAP 1: LOGIKA FILTER ---
require_once '_includes/functions.php'; // <--- TAMBAHKAN DI SINI
require_once '_includes/db.php';

// Menangkap nilai filter dari URL (metode GET)
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$type = $_GET['type'] ?? null;

// --- TAHAP 2: MEMBANGUN QUERY SQL DINAMIS ---
$transactions = [];
$params = [];
$types = '';

// Query dasar
$sql = "SELECT 
            transactions.*, 
            categories.name AS category_name 
        FROM 
            transactions 
        LEFT JOIN 
            categories ON transactions.category_id = categories.id 
        WHERE 
            transactions.user_id = ?"; // Selalu filter berdasarkan user_id

$params[] = 1; // ID user, sementara 1
$types .= 'i';

// Array untuk menampung kondisi WHERE tambahan
$conditions = [];

// Tambahkan kondisi jika filter ada
if ($start_date) {
    $conditions[] = "transactions.transaction_date >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if ($end_date) {
    $conditions[] = "transactions.transaction_date <= ?";
    $params[] = $end_date;
    $types .= 's';
}
if ($type) {
    $conditions[] = "transactions.type = ?";
    $params[] = $type;
    $types .= 's';
}

// Gabungkan semua kondisi tambahan ke query utama
if (count($conditions) > 0) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Tambahkan pengurutan
$sql .= " ORDER BY transactions.transaction_date DESC, transactions.id DESC";

// --- TAHAP 3: EKSEKUSI QUERY ---
if ($stmt = $conn->prepare($sql)) {
    // Bind semua parameter yang sudah terkumpul
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();

// Memasukkan file header
include '_includes/_header.php';
?>

<header class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">
        Daftar Transaksi
    </h1>
    <p class="text-gray-500">Lihat, saring, dan kelola semua transaksi Anda.</p>
</header>

<div class="bg-white p-6 rounded-xl shadow-lg mb-8">
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Filter Transaksi</h3>
    <form action="transaksi.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-600">Dari Tanggal</label>
            <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date ?? '') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
        </div>
        
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-600">Sampai Tanggal</label>
            <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date ?? '') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
        </div>
        
        <div>
            <label for="type" class="block text-sm font-medium text-gray-600">Jenis</label>
            <select name="type" id="type" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm">
                <option value="">Semua Jenis</option>
                <option value="income" <?= ($type == 'income') ? 'selected' : '' ?>>Pemasukan</option>
                <option value="expense" <?= ($type == 'expense') ? 'selected' : '' ?>>Pengeluaran</option>
            </select>
        </div>

        <div class="md:col-span-3 flex justify-end items-center space-x-3">
            <a href="transaksi.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Reset</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">
                Terapkan Filter
            </button>
        </div>
    </form>
</div>
<div class="bg-white shadow-lg rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-10 text-gray-500">
                            Tidak ada transaksi yang cocok dengan filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tr): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d M Y', strtotime($tr['transaction_date'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($tr['description']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($tr['category_name']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                <span class="<?= $tr['type'] == 'income' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= ($tr['type'] == 'income' ? '+' : '-') ?> Rp <?= number_format($tr['amount'], 0, ',', '.') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="edit-transaksi.php?id=<?= $tr['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-4">Edit</a>
                                <button type="button" data-url="hapus-transaksi.php?id=<?= $tr['id'] ?>" class="tombol-hapus text-red-600 hover:text-red-900">Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '_includes/_footer.php'; ?>