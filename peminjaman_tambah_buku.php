<?php
include 'koneksi.php'; 

// Proteksi halaman. Pastikan hanya Peminjam yang sudah login yang bisa mengakses.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'peminjam') {
    header("Location: login.php");
    exit();
}

$pesan_sukses = "";
$pesan_error = "";
$id_peminjam_aktif = $_SESSION['user_id'];
$kode_pinjam = null; 

// 1. Cek apakah ada kode pinjam di URL
if (isset($_GET['kode'])) {
    $kode_pinjam = $conn->real_escape_string($_GET['kode']);
}

// 2. Jika kode pinjam TIDAK ADA, buat transaksi BARU secara otomatis
if (is_null($kode_pinjam)) {
    
    // Generate Kode Pinjam Unik
    $tgl_sekarang = date('Ymd');
    $random_part = substr(md5(uniqid(rand(), true)), 0, 6);
    $kode_pinjam_baru = "PINJ-{$tgl_sekarang}-{$random_part}";
    
    $tgl_pesan = date('Y-m-d H:i:s');
    $status_awal = 'diproses'; 
    
    // Query INSERT ke tabel peminjaman
    $sql_insert_peminjaman = "INSERT INTO peminjaman (kode_pinjam, id_peminjam, tgl_pesan, status_pinjam) 
             VALUES ('$kode_pinjam_baru', $id_peminjam_aktif, '$tgl_pesan', '$status_awal')";

    if ($conn->query($sql_insert_peminjaman) === TRUE) {
        // Jika sukses, redirect ke halaman ini sendiri dengan kode baru
        header("Location: peminjaman_tambah_buku.php?kode=$kode_pinjam_baru");
        exit();
    } else {
        // Jika gagal membuat peminjaman awal (fatal error)
        die("Fatal Error: Gagal membuat transaksi peminjaman baru: " . $conn->error);
    }
}


// 3. Cek Status Peminjaman yang sedang dikerjakan
$sql_check = "SELECT status_pinjam FROM peminjaman WHERE kode_pinjam = '$kode_pinjam' AND id_peminjam = $id_peminjam_aktif";
$result_check = $conn->query($sql_check);

if ($result_check->num_rows == 0) {
    // Peminjaman tidak ditemukan atau bukan miliknya
    header("Location: peminjaman_list.php");
    exit();
}

$row_check = $result_check->fetch_assoc();
$status_pinjam_saat_ini = $row_check['status_pinjam'];

// Hanya boleh menambah/hapus buku jika status masih DIPROSES
if ($status_pinjam_saat_ini !== 'diproses') {
    $pesan_error = "Peminjaman ini sudah tidak bisa diubah karena statusnya adalah **" . strtoupper($status_pinjam_saat_ini) . "**.";
}


// --- LOGIKA CREATE (Tambah Buku ke Detil Peminjaman) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah_buku') {
    if ($status_pinjam_saat_ini === 'diproses') {
        $id_buku = $conn->real_escape_string($_POST['id_buku']);

        // Cek apakah buku sudah ada di detil peminjaman (pencegahan duplikat form)
        $sql_check_duplicate = "SELECT 1 FROM detil_peminjaman WHERE kode_pinjam = '$kode_pinjam' AND id_buku = $id_buku";
        $result_duplicate = $conn->query($sql_check_duplicate);

        if ($result_duplicate->num_rows > 0) {
            $pesan_error = "Buku ini sudah ada di daftar peminjaman.";
        } else {
             // Query INSERT ke tabel detil_peminjaman
            $sql_insert = "INSERT INTO detil_peminjaman (kode_pinjam, id_buku) VALUES ('$kode_pinjam', $id_buku)";

            if ($conn->query($sql_insert) === TRUE) {
                $pesan_sukses = "Buku berhasil ditambahkan ke daftar peminjaman.";
            } else {
                 $pesan_error = "Error saat menambahkan buku: " . $conn->error;
            }
        }
    }
}
// --- AKHIR LOGIKA CREATE ---


// --- LOGIKA DELETE (Hapus Buku dari Detil Peminjaman) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'hapus_buku') {
    if ($status_pinjam_saat_ini === 'diproses') {
        $id_buku_to_delete = $conn->real_escape_string($_POST['id_buku_delete']);

        // Query DELETE dari tabel detil_peminjaman
        $sql_delete = "DELETE FROM detil_peminjaman WHERE kode_pinjam = '$kode_pinjam' AND id_buku = $id_buku_to_delete";

        if ($conn->query($sql_delete) === TRUE) {
            $pesan_sukses = "Buku berhasil dihapus dari daftar peminjaman.";
        } else {
            $pesan_error = "Error saat menghapus buku: " . $conn->error;
        }
    }
}
// --- AKHIR LOGIKA DELETE ---


// --- LOGIKA READ (Mengambil Daftar Buku yang Dipinjam dan Buku Tersedia) ---

// 4. Ambil daftar buku yang sudah dipesan (Detil Peminjaman)
$sql_dipinjam = "
    SELECT d.id_buku, b.judul_buku, b.nama_pengarang
    FROM detil_peminjaman d
    JOIN buku b ON d.id_buku = b.id_buku
    WHERE d.kode_pinjam = '$kode_pinjam'
    ORDER BY b.judul_buku ASC
";
$result_dipinjam = $conn->query($sql_dipinjam);

// Kumpulkan ID buku yang sudah dipilih untuk dikecualikan dari daftar buku tersedia
$buku_dipilih_ids = [];
if ($result_dipinjam->num_rows > 0) {
    // Reset pointer result_dipinjam (Jika perlu, namun kita akan re-fetch di bawah)
    $temp_result_dipinjam = $conn->query($sql_dipinjam);
    while ($row = $temp_result_dipinjam->fetch_assoc()) {
        $buku_dipilih_ids[] = $row['id_buku'];
    }
}
$buku_dipilih_string = empty($buku_dipilih_ids) ? "0" : implode(',', $buku_dipilih_ids);


// 5. Ambil daftar buku yang tersedia (tidak termasuk yang sudah dipilih)
$sql_buku_tersedia = "
    SELECT id_buku, judul_buku, nama_pengarang 
    FROM buku 
    WHERE id_buku NOT IN ($buku_dipilih_string)
    ORDER BY judul_buku ASC
";
$result_buku_tersedia = $conn->query($sql_buku_tersedia);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Buku | Kode: <?php echo $kode_pinjam; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color:#f0f8ff; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9e9e9; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .form-add { margin-bottom: 20px; }
        .form-add select, .form-add button { padding: 10px; margin-right: 10px; border-radius: 4px; border: 1px solid #ccc; }
        .form-add button { background-color: #007bff; color: white; border: none; cursor: pointer; }
        .form-add button:hover { background-color: #0056b3; }
        .btn-delete { background-color: #f44336; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .btn-finish { 
            background-color: #214453; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 20px auto; 
            width: fit-content; 
            display: block; 
            font-weight: bold; 
            text-align: center;
            text-decoration: none; 
        }
        .btn-finish:hover { background-color: #0066cc; }
        
        .btn-kembali {
            display: inline-block;
            margin-bottom: 25px;
            color: #214453;
            text-decoration: none;
            font-weight: bold;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .btn-kembali:hover {
            background-color: #e9e9e9;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo $link_kembali; ?>" class="btn-kembali">← Kembali ke Daftar</a>
        <h2>Detil Pemesanan | Kode: <?php echo $kode_pinjam; ?></h2>
        <p>Status Peminjaman: **<?php echo strtoupper($status_pinjam_saat_ini); ?>**</p>
        
        <p>Anda sedang membuat pesanan baru. Tambahkan buku yang Anda inginkan ke daftar di bawah ini.</p>

        <?php if (!empty($pesan_sukses)): ?>
            <div class="alert-success">✅ <?php echo $pesan_sukses; ?></div>
        <?php endif; ?>
        <?php if (!empty($pesan_error)): ?>
            <div class="alert-error">❌ <?php echo $pesan_error; ?></div>
        <?php endif; ?>

        
        <h3>1. Buku yang Sudah Dipilih</h3>
        <table>
            <thead>
                <tr>
                    <th>Judul Buku</th>
                    <th>Pengarang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $result_dipinjam_display = $conn->query($sql_dipinjam);
                if ($result_dipinjam_display->num_rows > 0): ?>
                    <?php while($row = $result_dipinjam_display->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_pengarang']); ?></td>
                            <td>
                                <?php if ($status_pinjam_saat_ini === 'diproses'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="hapus_buku">
                                        <input type="hidden" name="id_buku_delete" value="<?php echo $row['id_buku']; ?>">
                                        <input type="hidden" name="kode_pinjam" value="<?php echo $kode_pinjam; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Yakin ingin menghapus buku ini?');">Hapus</button>
                                    </form>
                                <?php else: ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">Belum ada buku yang ditambahkan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <hr>

        <?php if ($status_pinjam_saat_ini === 'diproses'): ?>
            <h3>2. Tambah Buku ke Daftar Pesanan</h3>
            <form method="POST" class="form-add">
                <input type="hidden" name="action" value="tambah_buku">
                <input type="hidden" name="kode_pinjam" value="<?php echo $kode_pinjam; ?>">
                
                <label for="id_buku">Pilih Buku:</label>
                <select name="id_buku" id="id_buku" required>
                    <?php if ($result_buku_tersedia->num_rows > 0): ?>
                        <?php while($row = $result_buku_tersedia->fetch_assoc()): ?>
                            <option value="<?php echo $row['id_buku']; ?>">
                                <?php echo htmlspecialchars($row['judul_buku']) . " (" . htmlspecialchars($row['nama_pengarang']) . ")"; ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>Semua buku sudah dipilih atau tidak ada buku tersedia.</option>
                    <?php endif; ?>
                </select>
                
                <button type="submit">Tambah Buku</button>
            </form>
            
            <hr>

            <p>Jika sudah selesai memilih buku, klik tombol di bawah untuk kembali ke daftar.</p>
            <a href="peminjaman_list.php" class="btn-finish">Selesai Memesan & Kembali ke Daftar</a>
            
        <?php endif; ?>
    </div>
    
    <?php $conn->close(); ?>
</body>
</html>