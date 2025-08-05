<?php
// ---- BLOK PENJAGA ----
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
// ---- AKHIR BLOK PENJAGA ----

require_once '_includes/functions.php';
require_once '_includes/db.php';
// Cek apakah ada ID yang dikirim melalui URL
if (isset($_GET['id'])) {
    $category_id = $_GET['id'];

    // PENTING: Sebelum menghapus kategori, kita harus menangani transaksi yang terkait.
    // Opsi 1 (dipilih): Set category_id menjadi NULL pada transaksi terkait.
    // Ini menjaga riwayat transaksi tetap ada meskipun kategorinya dihapus.
    $sql_update_transactions = "UPDATE transactions SET category_id = NULL WHERE category_id = ? AND user_id = ?";
    if ($stmt_update = $conn->prepare($sql_update_transactions)) {
        $stmt_update->bind_param("ii", $category_id, $user_id);
        $stmt_update->execute();
        $stmt_update->close();
    }

    // Siapkan SQL statement untuk menghapus kategori
    $sql_delete_category = "DELETE FROM categories WHERE id = ? AND user_id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete_category)) {
        // Bind ID kategori dan user ID ke statement
        $stmt_delete->bind_param("ii", $category_id, $user_id);

        // Eksekusi statement
        if ($stmt_delete->execute()) {
            // Jika berhasil, kembali ke halaman kategori
            header("Location: kategori.php");
            set_notification('danger', 'Kategori telah dihapus.');
            exit();
        } else {
            echo "Error: Gagal menghapus kategori.";
        }
        $stmt_delete->close();
    }
} else {
    // Jika tidak ada ID, kembali ke halaman kategori
    header("Location: kategori.php");
    exit();
}

$conn->close();
?>