<?php
include 'koneksi.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id_admin_aktif = $_SESSION['user_id'];
$nama_admin = "Admin Perpustakaan"; // Ditambahkan untuk display nama admin di navbar
$pesan_sukses = "";
$pesan_error = "";

// --- LOGIKA UPDATE (Ubah Status Peminjaman) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    
    $kode_pinjam = $conn->real_escape_string($_POST['kode_pinjam']);
    $new_status = $conn->real_escape_string($_POST['new_status']);
    
    // Tentukan kolom dan nilai yang akan diupdate
    $update_fields = "status_pinjam = '$new_status', id_admin = $id_admin_aktif";
    
    if ($new_status == 'disetujui') {
        // --- LOGIKA PENGURANGAN STOK BUKU ---
        $sql_get_books = "SELECT id_buku FROM detil_peminjaman WHERE kode_pinjam = '$kode_pinjam'";
        $result_books = $conn->query($sql_get_books);
        
        if ($result_books && $result_books->num_rows > 0) {
            $is_stock_updated = true;
            
            while ($row_book = $result_books->fetch_assoc()) {
                $id_buku = $row_book['id_buku'];
                $sql_update_stock = "UPDATE buku SET jumlah_stok = jumlah_stok - 1 WHERE id_buku = $id_buku AND jumlah_stok > 0";
                
                if ($conn->query($sql_update_stock) !== TRUE) {
                    $is_stock_updated = false;
                    $pesan_error = "Error: Gagal mengurangi stok untuk buku ID $id_buku. Peminjaman tidak disetujui.";
                    break;
                }
            }
            
            if ($is_stock_updated) {
                $tgl_ambil = date('Y-m-d H:i:s');
                $tgl_wajibkembali = date('Y-m-d', strtotime('+7 days')); 
                $update_fields .= ", tgl_ambil = '$tgl_ambil', tgl_wajibkembali = '$tgl_wajibkembali'";
                
                $sql_update = "UPDATE peminjaman SET $update_fields WHERE kode_pinjam = '$kode_pinjam'";
                if ($conn->query($sql_update) === TRUE) {
                    $pesan_sukses = "Status peminjaman **$kode_pinjam** berhasil diubah menjadi **disetujui**. Stok buku telah dikurangi.";
                } else {
                    $pesan_error = "Error saat mengubah status peminjaman: " . $conn->error;
                }
            }
        } else {
            $pesan_error = "Error: Tidak ada buku terdaftar untuk kode peminjaman $kode_pinjam.";
        }
    } 
    elseif ($new_status == 'selesai') {
        // --- LOGIKA PENAMBAHAN STOK BUKU ---
        $sql_get_books = "SELECT id_buku FROM detil_peminjaman WHERE kode_pinjam = '$kode_pinjam'";
        $result_books = $conn->query($sql_get_books);
        
        if ($result_books && $result_books->num_rows > 0) {
             while ($row_book = $result_books->fetch_assoc()) {
                $id_buku = $row_book['id_buku'];
                $sql_update_stock = "UPDATE buku SET jumlah_stok = jumlah_stok + 1 WHERE id_buku = $id_buku";
                $conn->query($sql_update_stock);
            }
        }
        
        $tgl_kembali = date('Y-m-d H:i:s');
        $update_fields .= ", tgl_kembali = '$tgl_kembali'";
        
        $sql_update = "UPDATE peminjaman SET $update_fields WHERE kode_pinjam = '$kode_pinjam'";
        
        if ($conn->query($sql_update) === TRUE) {
            $pesan_sukses = "Status peminjaman **$kode_pinjam** berhasil diubah menjadi **selesai**. Stok buku telah dikembalikan.";
        } else {
            $pesan_error = "Error saat mengubah status: " . $conn->error;
        }

    }
    // Jika status adalah 'ditolak'
    elseif ($new_status == 'ditolak') {
        $sql_update = "UPDATE peminjaman SET $update_fields WHERE kode_pinjam = '$kode_pinjam'";
        if ($conn->query($sql_update) === TRUE) {
            $pesan_sukses = "Status peminjaman **$kode_pinjam** berhasil diubah menjadi **ditolak**.";
        } else {
            $pesan_error = "Error saat mengubah status: " . $conn->error;
        }
    }
}
// --- AKHIR LOGIKA UPDATE ---


// --- LOGIKA READ (Mengambil Daftar Semua Peminjaman) ---
$sql_read = "
    SELECT 
        p.kode_pinjam, 
        p.tgl_pesan, 
        p.tgl_wajibkembali, 
        p.status_pinjam, 
        pm.nama_peminjam,
        p.tgl_ambil
    FROM peminjaman p
    JOIN peminjam pm ON p.id_peminjam = pm.id_peminjam
    ORDER BY p.tgl_pesan DESC
";

$result = $conn->query($sql_read);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Pemesanan (Admin)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        
        .navbar {
            background-color: #dff3ff;
            padding: 8px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: auto; 
            box-sizing: border-box;
            margin: -20px -20px 20px -20px; 
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
        
        .navbar-info-container {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .navbar-nav a {
            color: #214453;
            text-decoration: none;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 4px;
            margin-right: 10px;
        }
        .navbar-nav a:hover {
            background-color: #b3d9ff;
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
        
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 15px; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px; }
        .status-diproses { background-color: #ffe0b2; } 
        .status-disetujui { background-color: #c8e6c9; } 
        .status-ditolak { background-color: #ffcdd2; }
        .status-selesai { background-color: #e0f7fa; } 
        .btn-action { margin-right: 5px; padding: 5px 10px; cursor: pointer;}
        /* Gaya untuk tombol Detil */
        .btn-detail { background-color: #007bff; color: white; border: none; border-radius: 3px; text-decoration: none;} 
        .btn-approve { background-color: #4CAF50; color: white; border: none; border-radius: 3px; }
        .btn-reject { background-color: #f44336; color: white; border: none; border-radius: 3px; }
        .btn-complete { background-color: #2196F3; color: white; border: none; border-radius: 3px; }
        
    </style>
</head>
<body>
    
    <div class="navbar">
        <a href="admin_peminjaman_list.php" class="navbar-brand">
            <img src="books.png" alt="Icon">
            Arcadia
        </a>
        
        <div class="navbar-info-container">
            <div class="navbar-nav">
                <a href="admin_peminjaman_list.php" style="background-color: #b3d9ff;">Manajemen Peminjaman</a> 
                
                <a href="admin_manajemen_buku.php">Manajemen Buku</a>
            </div>
            
            <div class="navbar-user-info">
                <p>Selamat datang, <b><?php echo htmlspecialchars($nama_admin); ?></b> | <a href="logout.php">Logout</a></p>
            </div>
        </div>
    </div>
    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert-success"><?php echo $pesan_sukses; ?></div>
    <?php endif; ?>
    <?php if (!empty($pesan_error)): ?>
        <div class="alert-error"><?php echo $pesan_error; ?></div>
    <?php endif; ?>

    <h2>Daftar Seluruh Pemesanan</h2>
    
    <table>
        <thead>
            <tr>
                <th>Kode Pinjam</th>
                <th>Peminjam</th>
                <th>Tgl Pesan</th>
                <th>Tgl Ambil/Setuju</th>
                <th>Wajib Kembali</th>
                <th>Status</th>
                <th>Aksi Admin</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <?php 
                        $status_class = 'status-' . strtolower(str_replace(' ', '-', $row['status_pinjam']));
                    ?>
                    <tr class="<?php echo $status_class; ?>">
                        <td><?php echo $row['kode_pinjam']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_peminjam']); ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($row['tgl_pesan'])); ?></td>
                        <td><?php echo $row['tgl_ambil'] ? date('d-m-Y H:i', strtotime($row['tgl_ambil'])) : '-'; ?></td>
                        
                        <td>
                            <?php 
                            if (!empty($row['tgl_wajibkembali']) && $row['tgl_wajibkembali'] !== '0000-00-00') {
                                echo date('d-m-Y', strtotime($row['tgl_wajibkembali']));
                            } else {
                                echo '-'; 
                            }
                            ?>
                        </td>
                        
                        <td><strong><?php echo strtoupper($row['status_pinjam']); ?></strong></td>
                        <td>
                            <a href="peminjaman_detail.php?kode=<?php echo $row['kode_pinjam']; ?>">Lihat Detail</a>
                            
                            <?php if ($row['status_pinjam'] == 'diproses'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Setujui peminjaman ini? Tanggal ambil akan dicatat hari ini dan stok buku akan dikurangi.');">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="kode_pinjam" value="<?php echo $row['kode_pinjam']; ?>">
                                    <input type="hidden" name="new_status" value="disetujui">
                                    <button type="submit" class="btn-action btn-approve">Setujui</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Tolak peminjaman ini?');">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="kode_pinjam" value="<?php echo $row['kode_pinjam']; ?>">
                                    <input type="hidden" name="new_status" value="ditolak">
                                    <button type="submit" class="btn-action btn-reject">Tolak</button>
                                </form>
                            <?php elseif ($row['status_pinjam'] == 'disetujui'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Tandai sebagai selesai / sudah dikembalikan? Stok buku akan ditambahkan kembali.');">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="kode_pinjam" value="<?php echo $row['kode_pinjam']; ?>">
                                    <input type="hidden" name="new_status" value="selesai">
                                    <button type="submit" class="btn-action btn-complete">Selesai</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">Tidak ada pemesanan peminjaman yang tercatat.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php $conn->close(); ?>
</body>
</html>