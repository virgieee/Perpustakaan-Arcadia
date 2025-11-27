<?php
include 'koneksi.php'; 

// Proteksi halaman: Pastikan hanya peminjam yang sudah login yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peminjam') {
    header("Location: login.php");
    exit();
}

$id_peminjam_aktif = $_SESSION['user_id'];

// Query untuk mendapatkan data nama peminjam
$sql_user = "SELECT nama_peminjam FROM peminjam WHERE id_peminjam = $id_peminjam_aktif";
$result_user = $conn->query($sql_user);
$peminjam_data = $result_user->fetch_assoc();
$nama_peminjam = $peminjam_data['nama_peminjam'];

// Query untuk menampilkan daftar pemesanan peminjaman buku per peminjam
$sql = "SELECT kode_pinjam, tgl_pesan, tgl_wajibkembali, status_pinjam 
        FROM peminjaman 
        WHERE id_peminjam = $id_peminjam_aktif 
        ORDER BY tgl_pesan DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pemesanan Peminjaman</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            background-color: #ffffff; 
        }
        
        .navbar {
            background-color: #d4e9f7;
            padding: 8px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #214453;
            font-size: 20px;
            font-weight: bold;
        }
        
        .navbar-brand img {
            width: 30px;
            height: 30px;
        }
        
        .navbar-user-info {
            color: #214453;
            font-size: 14px;
        }
        
        .navbar-user-info a {
            color: #0066cc;
            text-decoration: none;
            font-weight: bold;
        }
        
        .navbar-user-info a:hover {
            text-decoration: underline;
        }

        /* --- CSS Content & Table --- */
        .content-wrapper {
            margin: 20px; 
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn-add { background-color: #214453; color: white; padding: 10px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .btn-add:hover { background-color: #0066cc; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="peminjaman_list.php" class="navbar-brand">
            <img src="books.png" alt="Icon">
            Arcadia
        </a>
        <div class="navbar-user-info">
            <p>Selamat datang, <b><?php echo htmlspecialchars($nama_peminjam); ?></b> | <a href="logout.php">Logout</a></p>
        </div>
    </div>
    <div class="content-wrapper">
        <h1>Daftar Pemesanan Anda</h1>
        
        <a href="peminjaman_tambah_buku.php" class="btn-add">➕ Buat Pemesanan Baru</a>
        
        <h2>Riwayat Pemesanan</h2>
        <table>
            <thead>
                <tr>
                    <th>Kode Pinjam</th>
                    <th>Tanggal Pesan</th>
                    <th>Wajib Kembali</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['kode_pinjam']; ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($row['tgl_pesan'])); ?></td>
                            <td>
                                <?php 
                                if (!empty($row['tgl_wajibkembali']) && $row['tgl_wajibkembali'] !== '0000-00-00') {
                                    echo date('d-m-Y', strtotime($row['tgl_wajibkembali']));
                                } else {
                                    echo '-'; 
                                }
                                ?>
                            </td>
                            <td><strong style="color: <?php echo ($row['status_pinjam'] == 'diproses') ? 'orange' : (($row['status_pinjam'] == 'disetujui') ? 'green' : 'black'); ?>;"><?php echo strtoupper($row['status_pinjam']); ?></strong></td>
                            <td>
                                <a href="peminjaman_detail.php?kode=<?php echo $row['kode_pinjam']; ?>">Lihat Detail</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Belum ada pemesanan yang dibuat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>