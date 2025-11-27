<?php
// Memulai sesi PHP untuk mengelola status login
session_start(); 

date_default_timezone_set('Asia/Jakarta');

// Detail Koneksi Database (Disertai komentar/keterangan)
$servername = "localhost";
$username = "root";     // Sesuaikan dengan username database Anda
$password = "";         // Sesuaikan dengan password database Anda
$dbname = "perpustakaan_arcadia"; // Nama database sesuai permintaan

// Membuat koneksi ke database
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi, hentikan jika gagal
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
?>