<?php
// ---- BLOK PENJAGA ----
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id']; // Ambil user_id dari session
// ---- AKHIR BLOK PENJAGA ----
require_once '_includes/functions.php'; // <--- TAMBAHKAN DI SINI
require_once '_includes/db.php';
include '_includes/_header.php';

// --- LOGIKA UNTUK PEMILIH PERIODE ---
$user_id = 1;
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// --- LOGIKA UNTUK MENGAMBIL DATA LAPORAN ---
$report_summary = ['total_income' => 0, 'total_expense' => 0];
$expense_details = [];

// Query 1: Untuk Ringkasan (Total Pemasukan & Pengeluaran)
$sql_summary = "SELECT 
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                FROM transactions
                WHERE user_id = ? AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";

if ($stmt_summary = $conn->prepare($sql_summary)) {
    $stmt_summary->bind_param("iii", $user_id, $selected_month, $selected_year);
    $stmt_summary->execute();
    $result = $stmt_summary->get_result();
    $summary_data = $result->fetch_assoc();
    if ($summary_data) {
        $report_summary['total_income'] = $summary_data['total_income'] ?? 0;
        $report_summary['total_expense'] = $summary_data['total_expense'] ?? 0;
    }
    $stmt_summary->close();
}
$net_profit = $report_summary['total_income'] - $report_summary['total_expense'];

// Query 2: Untuk Rincian Pengeluaran per Kategori
$sql_details = "SELECT c.name as category_name, SUM(t.amount) as total_amount 
                FROM transactions t
                JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ? AND t.type = 'expense' AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?
                GROUP BY c.name
                ORDER BY total_amount DESC";

if ($stmt_details = $conn->prepare($sql_details)) {
    $stmt_details->bind_param("iii", $user_id, $selected_month, $selected_year);
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();
    if ($result_details->num_rows > 0) {
        while($row = $result_details->fetch_assoc()) {
            $expense_details[] = $row;
        }
    }
    $stmt_details->close();
}

$conn->close();
?>

<header class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">
        Laporan Keuangan
    </h1>
    <p class="text-gray-500">Analisis pemasukan dan pengeluaran Anda per periode.</p>
</header>

<div class="bg-white p-6 rounded-xl shadow-lg">
    
    <form action="laporan.php" method="GET" class="flex items-end space-x-4 mb-6 pb-6 border-b">
        <div>
            <label for="month" class="block text-sm font-medium text-gray-700">Bulan</label>
            <select name="month" id="month" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= $num ?>" <?= ($selected_month == $num) ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="year" class="block text-sm font-medium text-gray-700">Tahun</label>
            <select name="year" id="year" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">Tampilkan</button>
        </div>
    </form>
    
    <div class="pt-6">
        <h2 class="text-xl font-semibold mb-4">Laporan untuk: <?= htmlspecialchars($months[(int)$selected_month]) ?> <?= htmlspecialchars($selected_year) ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-green-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-green-800">Total Pemasukan</h3>
                <p class="text-2xl font-bold text-green-700">Rp <?= number_format($report_summary['total_income'], 0, ',', '.') ?></p>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-red-800">Total Pengeluaran</h3>
                <p class="text-2xl font-bold text-red-700">Rp <?= number_format($report_summary['total_expense'], 0, ',', '.') ?></p>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800">Selisih</h3>
                <p class="text-2xl font-bold <?= $net_profit >= 0 ? 'text-blue-700' : 'text-red-700' ?>">
                    Rp <?= number_format($net_profit, 0, ',', '.') ?>
                </p>
            </div>
        </div>

        <h3 class="text-lg font-semibold mb-4">Rincian Pengeluaran per Kategori</h3>
        <?php if (empty($expense_details)): ?>
            <p class="text-gray-500">Tidak ada data pengeluaran pada periode ini.</p>
        <?php else: ?>
            <div class="overflow-x-auto border rounded-lg">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($expense_details as $detail): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($detail['category_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">
                                    Rp <?= number_format($detail['total_amount'], 0, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Memasukkan file footer
include '_includes/_footer.php';
?>