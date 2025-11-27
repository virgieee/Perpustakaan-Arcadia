<?php
include 'koneksi.php'; 

// Keterangan: Proteksi halaman. Pastikan hanya admin yang sudah login yang bisa mengakses.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$nama_admin = "Admin Perpustakaan"; 
$pesan_sukses = "";
$pesan_error = "";
$mode = 'tambah'; 
$data_edit = []; 

// --- LOGIKA CREATE & UPDATE (Tambah/Ubah Buku) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // Ambil dan bersihkan input
    $judul = $conn->real_escape_string($_POST['judul_buku']);
    $tgl_terbit = $conn->real_escape_string($_POST['tgl_terbit']);
    $pengarang = $conn->real_escape_string($_POST['nama_pengarang']);
    $penerbit = $conn->real_escape_string($_POST['nama_penerbit']);
    $stok = $conn->real_escape_string($_POST['jumlah_stok']); 
    $action = $_POST['action'];

    if ($action == 'tambah') {
        // Query INSERT (CREATE)
        $sql = "INSERT INTO buku (judul_buku, tgl_terbit, nama_pengarang, nama_penerbit, jumlah_stok) 
                VALUES ('$judul', '$tgl_terbit', '$pengarang', '$penerbit', '$stok')"; 
        
        if ($conn->query($sql) === TRUE) {
            $pesan_sukses = "Buku **$judul** berhasil ditambahkan! Stok: $stok.";
        } else {
            $pesan_error = "Error saat menambah buku: " . $conn->error;
        }

    } elseif ($action == 'edit' && isset($_POST['id_buku'])) {
        // Query UPDATE
        $id_buku = $conn->real_escape_string($_POST['id_buku']);
        $sql = "UPDATE buku SET 
                judul_buku = '$judul', 
                tgl_terbit = '$tgl_terbit', 
                nama_pengarang = '$pengarang', 
                nama_penerbit = '$penerbit',
                jumlah_stok = '$stok' 
                WHERE id_buku = $id_buku";
        
        if ($conn->query($sql) === TRUE) {
            $pesan_sukses = "Buku **$judul** berhasil diubah! Stok: $stok.";
        } else {
            $pesan_error = "Error saat mengubah buku: " . $conn->error;
        }
    }
}
// --- AKHIR LOGIKA CREATE & UPDATE ---

// --- LOGIKA DELETE (Hapus Buku) ---
if (isset($_GET['action']) && $_GET['action'] == 'hapus' && isset($_GET['id'])) {
    $id_buku = $conn->real_escape_string($_GET['id']);
    
    // Query DELETE
    $sql_delete = "DELETE FROM buku WHERE id_buku = $id_buku";
    
    if ($conn->query($sql_delete) === TRUE) {
        $pesan_sukses = "Buku berhasil dihapus!";
    } else {
        $pesan_error = "Error saat menghapus buku. Mungkin buku ini sudah terikat dengan detil peminjaman: " . $conn->error;
    }
    // Redirect untuk menghilangkan parameter GET agar halaman bersih
    header("Location: admin_manajemen_buku.php");
    exit();
}
// --- AKHIR LOGIKA DELETE ---

// --- LOGIKA EDIT (Mengambil Data untuk Formulir) ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $mode = 'edit';
    $id_buku = $conn->real_escape_string($_GET['id']);
    
    // Query SELECT * akan mengambil semua kolom, termasuk jumlah_stok
    $sql_get_data = "SELECT * FROM buku WHERE id_buku = $id_buku"; 
    $result_get_data = $conn->query($sql_get_data);
    
    if ($result_get_data->num_rows == 1) {
        $data_edit = $result_get_data->fetch_assoc();
    } else {
        $pesan_error = "Data buku tidak ditemukan.";
        $mode = 'tambah'; 
    }
}
// --- AKHIR LOGIKA EDIT ---


// --- LOGIKA READ (Mengambil Daftar Semua Buku) ---
$sql_read = "SELECT * FROM buku ORDER BY judul_buku ASC"; 
$result_read = $conn->query($sql_read);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Buku (Admin)</title>
    <style>
        /* CSS Disesuaikan: body padding 20px, navbar margin 0 */
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #ffffff; 
        }
        
        .navbar {
            background-color: #dff3ff;
            padding: 8px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: auto; 
            box-sizing: border-box;
            /* Margin negatif dipertahankan untuk mengimbangi padding body 
               agar navbar tetap full-width (sesuai layout sebelumnya) */
            margin: -20px -20px 20px -20px; 
        }
        
        .navbar-brand {
            display: flex; align-items: center; gap: 10px; text-decoration: none;
            color: #214453; font-size: 20px; font-weight: bold;
        }
        .navbar-brand img { width: 30px; height: 30px; }
        
        .navbar-info-container {
            display: flex; align-items: center; gap: 20px;
        }
        
        .navbar-nav a {
            color: #214453; text-decoration: none; font-weight: 600;
            padding: 5px 10px; border-radius: 4px; margin-right: 10px;
        }
        .navbar-nav a:hover { background-color: #b3d9ff; }

        .navbar-user-info {
            color: #214453; font-size: 14px;
        }
        
        .navbar-user-info a {
            color: #0066cc; text-decoration: none; font-weight: bold;
        }
        
        .navbar-user-info a:hover { text-decoration: underline; }

        /* Hapus styling .form-section karena container ini akan dihapus. 
           Beri sedikit margin pada form agar tidak terlalu menempel ke alert/navbar. */
        form {
            margin-bottom: 30px; 
            padding: 15px 0; /* Memberi sedikit padding vertical agar konten form tidak terlalu padat */
        }
        
        input[type=text], input[type=date], input[type=number] { 
            width: calc(100% - 22px); padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; 
        }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px;}
        .btn-update { background-color: #ffc107; }
        .btn-delete { background-color: #f44336; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #c3e6cb;}
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #f5c6cb;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white;}
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9e9e9; }
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
                <a href="admin_peminjaman_list.php">Manajemen Peminjaman</a> 
                
                <a href="admin_manajemen_buku.php" style="background-color: #b3d9ff;">Manajemen Buku</a>
            </div>
            
            <div class="navbar-user-info">
                <p>Selamat datang, <b><?php echo htmlspecialchars($nama_admin); ?></b> | <a href="logout.php">Logout</a></p>
            </div>
        </div>
    </div>
    
    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert-success">✅ <?php echo $pesan_sukses; ?></div>
    <?php endif; ?>
    <?php if (!empty($pesan_error)): ?>
        <div class="alert-error">❌ <?php echo $pesan_error; ?></div>
    <?php endif; ?>
        
    <h2><?php echo ($mode == 'edit' ? 'Ubah Data Buku' : 'Tambah Buku Baru'); ?></h2>
    <form method="POST" action="admin_manajemen_buku.php">
        
        <input type="hidden" name="action" value="<?php echo $mode; ?>">
        <?php if ($mode == 'edit'): ?>
            <input type="hidden" name="id_buku" value="<?php echo $data_edit['id_buku']; ?>">
        <?php endif; ?>

        <label for="judul_buku">Judul Buku</label>
        <input type="text" name="judul_buku" required 
            value="<?php echo htmlspecialchars($data_edit['judul_buku'] ?? ''); ?>">

        <label for="tgl_terbit">Tanggal Terbit</label>
        <input type="date" name="tgl_terbit" required 
            value="<?php echo htmlspecialchars($data_edit['tgl_terbit'] ?? ''); ?>">

        <label for="nama_pengarang">Nama Pengarang</label>
        <input type="text" name="nama_pengarang" required 
            value="<?php echo htmlspecialchars($data_edit['nama_pengarang'] ?? ''); ?>">

        <label for="nama_penerbit">Nama Penerbit</label>
        <input type="text" name="nama_penerbit" required 
            value="<?php echo htmlspecialchars($data_edit['nama_penerbit'] ?? ''); ?>">
        
        <label for="jumlah_stok">Jumlah Stok</label>
        <input type="number" name="jumlah_stok" required min="0" 
            value="<?php echo htmlspecialchars($data_edit['jumlah_stok'] ?? 0); ?>">
            
        <button type="submit" class="<?php echo ($mode == 'edit' ? 'btn-update' : ''); ?>">
            <?php echo ($mode == 'edit' ? 'Simpan Perubahan' : 'Tambah Buku'); ?>
        </button>
        
        <?php if ($mode == 'edit'): ?>
            <a href="admin_manajemen_buku.php" style="text-decoration: none;">
                <button type="button" class="btn-delete" style="background-color: #6c757d;">Batal Edit</button>
            </a>
        <?php endif; ?>
    </form>
    <h2>Daftar Buku Tersedia</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Judul Buku</th>
                <th>Tgl Terbit</th>
                <th>Pengarang</th>
                <th>Penerbit</th>
                <th>Stok</th>        <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result_read->num_rows > 0): ?>
                <?php while($row = $result_read->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id_buku']; ?></td>
                        <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['tgl_terbit'])); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_pengarang']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_penerbit']); ?></td>
                        <td><?php echo $row['jumlah_stok']; ?></td> <td>
                            <a href="admin_manajemen_buku.php?action=edit&id=<?php echo $row['id_buku']; ?>">
                                <button type="button" class="btn-update">Ubah</button>
                            </a>
                            <a href="admin_manajemen_buku.php?action=hapus&id=<?php echo $row['id_buku']; ?>" 
                               onclick="return confirm('Yakin ingin menghapus buku <?php echo htmlspecialchars($row['judul_buku']); ?>? Tindakan ini tidak dapat dibatalkan!');">
                                <button type="button" class="btn-delete">Hapus</button>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">Belum ada data buku yang tersimpan.</td></tr> 
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php $conn->close(); ?>

</body>
</html>