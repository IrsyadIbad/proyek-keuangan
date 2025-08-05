<?php
// Selalu mulai session jika belum ada, karena notifikasi bergantung pada session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fungsi untuk mengatur pesan notifikasi (flash message).
 *
 * @param string $type Tipe notifikasi (e.g., 'success', 'info', 'danger').
 * @param string $message Pesan yang ingin ditampilkan.
 */
function set_notification($type, $message) {
    $_SESSION['notification'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Fungsi untuk menampilkan pesan notifikasi jika ada.
 * Pesan akan otomatis dihapus setelah ditampilkan.
 */
function display_notification() {
    if (isset($_SESSION['notification'])) {
        $type = $_SESSION['notification']['type'];
        $message = $_SESSION['notification']['message'];

        // Menentukan kelas warna dan ikon berdasarkan tipe
        $details = [
            'success' => ['icon' => '...', 'color_classes' => 'bg-green-100 text-green-800'],
            'info' => ['icon' => '...', 'color_classes' => 'bg-blue-100 text-blue-800'],
            'danger' => ['icon' => '...', 'color_classes' => 'bg-red-100 text-red-800']
        ];
        
        $icon = $details[$type]['icon'] ?? ''; // Ikon SVG bisa kamu salin dari versi sebelumnya jika mau
        $colors = $details[$type]['color_classes'] ?? 'bg-gray-100 text-gray-800';

        // Tampilkan HTML notifikasi dengan kelas baru
        echo "
        <div class='toast {$colors}'>
            <div class='font-bold'>" . htmlspecialchars($message) . "</div>
        </div>
        ";

        unset($_SESSION['notification']);
    }
}