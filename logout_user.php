<?php
session_start();
// Hapus semua session pengguna
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
// Arahkan kembali ke halaman utama
header('Location: index.php');
exit();
?>