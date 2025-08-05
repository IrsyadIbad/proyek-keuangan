<?php
// Informasi untuk koneksi database
$db_host = 'localhost'; // atau 127.0.0.1
$db_user = 'root';
$db_pass = ''; // Kosongkan jika tidak ada password
$db_name = 'keuangan_pribadi';

// Membuat koneksi
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
?>