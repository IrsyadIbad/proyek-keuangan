<?php
// ---- BLOK PENJAGA ----
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id']; // Ambil user_id dari session
// ---- AKHIR BLOK PENJAGA ----
// Memasukkan file koneksi database
require_once '_includes/functions.php';
require_once '_includes/db.php';
// Cek apakah ada ID yang dikirim melalui URL
if (isset($_GET['id'])) {
    $transaction_id = $_GET['id'];
    $user_id = 1; // Nanti akan dinamis

    // Siapkan SQL statement untuk menghapus data
    $sql = "DELETE FROM transactions WHERE id = ? AND user_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind ID transaksi dan user ID ke statement
        $stmt->bind_param("ii", $transaction_id, $user_id);

        // Eksekusi statement
        if ($stmt->execute()) {
            // Jika berhasil, kembali ke halaman utama
            header("Location: index.php");
            set_notification('info', 'Transaksi berhasil diperbarui.');
            exit();
        } else {
            echo "Error: Gagal menghapus data.";
        }

        $stmt->close();
    }
} else {
    // Jika tidak ada ID, kembali ke halaman utama
    header("Location: index.php");
    exit();
}

$conn->close();
?>