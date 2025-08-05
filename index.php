<?php
// ---- BLOK PENJAGA ----
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id']; // Ambil user_id dari session
// ---- AKHIR BLOK PENJAGA ----

require_once '_includes/functions.php'; // <--- TAMBAHKAN DI SINI
require_once '_includes/db.php';

// --- TAHAP 1: TENTUKAN RENTANG TANGGAL BERDASARKAN FILTER ---
$period = $_GET['period'] ?? 'all_time';
$params = [$user_id];
$types = 'i';
$date_condition = '';
$date_condition_chart = ''; 

if ($period == 'this_month') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    $date_condition = " AND transaction_date BETWEEN ? AND ?";
    $date_condition_chart = " AND t.transaction_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= 'ss';
} elseif ($period == 'last_month') {
    $start_date = date('Y-m-01', strtotime('first day of last month'));
    $end_date = date('Y-m-t', strtotime('last day of last month'));
    $date_condition = " AND transaction_date BETWEEN ? AND ?";
    $date_condition_chart = " AND t.transaction_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= 'ss';
} elseif ($period == 'this_year') {
    $start_date = date('Y-01-01');
    $end_date = date('Y-12-31');
    $date_condition = " AND transaction_date BETWEEN ? AND ?";
    $date_condition_chart = " AND t.transaction_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= 'ss';
}

// --- TAHAP 2: AMBIL DATA TRANSAKSI SESUAI FILTER ---
$transactions = [];
$sql = "SELECT * FROM transactions WHERE user_id = ? {$date_condition} ORDER BY transaction_date DESC, id DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    $stmt->close();
}

// --- TAHAP 3: HITUNG ULANG RINGKASAN ---
$totalIncome = 0;
$totalExpense = 0;
foreach ($transactions as $tr) {
    if ($tr['type'] == 'income') {
        $totalIncome += $tr['amount'];
    } else {
        $totalExpense += $tr['amount'];
    }
}
$balance = $totalIncome - $totalExpense;

// --- TAHAP 4: LOGIKA UNTUK DATA GRAFIK ---
$chart_labels = [];
$chart_data = [];
$sql_chart = "SELECT c.name AS category_name, SUM(t.amount) AS total_amount 
              FROM transactions t
              JOIN categories c ON t.category_id = c.id
              WHERE t.user_id = ? AND t.type = 'expense' {$date_condition_chart}
              GROUP BY c.name ORDER BY total_amount DESC";

if($stmt_chart = $conn->prepare($sql_chart)){
    $stmt_chart->bind_param($types, ...$params);
    $stmt_chart->execute();
    $result_chart = $stmt_chart->get_result();
    if ($result_chart->num_rows > 0) {
        while($row = $result_chart->fetch_assoc()) {
            $chart_labels[] = $row['category_name'];
            $chart_data[] = (int)$row['total_amount'];
        }
    }
    $stmt_chart->close();
}

// --- TAHAP 5: HITUNG INSIGHT SETELAH SEMUA DATA SIAP (VERSI BARU) ---
$averageDailyExpense = 0;
if ($period != 'all_time' && $totalExpense > 0) {
    $daysInPeriod = 0;

    // Logika baru yang lebih pintar untuk menentukan pembagi
    if ($period == 'this_month') {
        // Jika periode adalah 'bulan ini', pembaginya adalah tanggal hari ini
        $daysInPeriod = (int)date('j'); // Mengambil tanggal hari ini (misal: 5)
    } else {
        // Untuk periode lain yang sudah selesai (bulan lalu, tahun ini), gunakan selisih hari
        $date1 = date_create($start_date);
        $date2 = date_create($end_date);
        $interval = date_diff($date1, $date2);
        $daysInPeriod = $interval->days + 1;
    }

    if ($daysInPeriod > 0) {
        $averageDailyExpense = $totalExpense / $daysInPeriod;
    }
}

$biggestExpenseCategory = "Tidak ada pengeluaran";
if (!empty($chart_labels)) {
    $biggestExpenseCategory = $chart_labels[0];
}

$conn->close();

include '_includes/_header.php';
// ... Sisa kode HTML untuk index.php tidak perlu diubah ...
// Pastikan sisa file HTML-mu sama seperti sebelumnya
?>

<header class="mb-8">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">
            Dashboard
        </h1>
        <a href="tambah-transaksi.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-transform transform hover:scale-105">
            + Tambah Transaksi
        </a>
    </div>
</header>

<div class="mb-6 flex items-center justify-start space-x-2">
    <span class="text-sm font-medium text-gray-600">Tampilkan:</span>
    <a href="index.php?period=this_month" class="px-3 py-1 text-sm font-semibold rounded-full <?= ($period == 'this_month') ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">Bulan Ini</a>
    <a href="index.php?period=last_month" class="px-3 py-1 text-sm font-semibold rounded-full <?= ($period == 'last_month') ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">Bulan Lalu</a>
    <a href="index.php?period=this_year" class="px-3 py-1 text-sm font-semibold rounded-full <?= ($period == 'this_year') ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">Tahun Ini</a>
    <a href="index.php?period=all_time" class="px-3 py-1 text-sm font-semibold rounded-full <?= ($period == 'all_time') ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">Semua</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4 card-animate transition-all duration-200 ease-in-out hover:shadow-xl hover:-translate-y-1">
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 10v-1m6-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Total Pemasukan</h3>
            <p class="text-2xl font-bold text-gray-800">Rp <?= number_format($totalIncome, 0, ',', '.') ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4 card-animate transition-all duration-200 ease-in-out hover:shadow-xl hover:-translate-y-1">
        <div class="bg-red-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Total Pengeluaran</h3>
            <p class="text-2xl font-bold text-gray-800">Rp <?= number_format($totalExpense, 0, ',', '.') ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4 card-animate transition-all duration-200 ease-in-out hover:shadow-xl hover:-translate-y-1">
        <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Saldo Akhir</h3>
            <p class="text-2xl font-bold text-gray-800">Rp <?= number_format($balance, 0, ',', '.') ?></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4 card-animate">
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Rata-rata Pengeluaran/Hari</h3>
            <p class="text-lg font-bold text-gray-800">Rp <?= number_format($averageDailyExpense, 0, ',', '.') ?></p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4 card-animate">
        <div class="bg-red-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500">Pengeluaran Terbesar di</h3>
            <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($biggestExpenseCategory) ?></p>
        </div>
    </div>
</div>


<div class="mt-8 grid grid-cols-1 lg:grid-cols-5 gap-8">

    <div class="lg:col-span-3">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700">Aktivitas Terbaru</h2>
            <a href="transaksi.php" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                Lihat Semua &rarr;
            </a>
        </div>
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <tbody class="divide-y divide-gray-200">
                        <?php
                        $recent_transactions = array_slice($transactions, 0, 5);
                        ?>
                        <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td class="text-center py-10 text-gray-500">
                                    Belum ada aktivitas pada periode ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transactions as $tr): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($tr['description']) ?></div>
                                        <div class="text-sm text-gray-500"><?= date('d M Y', strtotime($tr['transaction_date'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                        <span class="<?= $tr['type'] == 'income' ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= ($tr['type'] == 'income' ? '+' : '-') ?> Rp <?= number_format($tr['amount'], 0, ',', '.') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Ringkasan Pengeluaran</h2>
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div class="relative h-64"><canvas id="expenseChart"></canvas></div>
        </div>
    </div>

</div>

<?php include '_includes/_footer.php'; ?>