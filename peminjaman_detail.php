<?php
include 'koneksi.php'; 

// Keterangan: Proteksi dasar. Pastikan user sudah login.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 1. Ambil kode pinjam dari URL
if (!isset($_GET['kode'])) {
    // Keterangan: Jika tidak ada kode, redirect kembali ke daftar
    header("Location: " . ($_SESSION['role'] == 'admin' ? 'admin_' : '') . "peminjaman_list.php");
    exit();
}

$kode_pinjam = $conn->real_escape_string($_GET['kode']);
$role = $_SESSION['role'];
$id_user_aktif = $_SESSION['user_id'];

// --- QUERY DETIL PEMINJAMAN UTAMA ---
$filter_role = "";
if ($role == 'peminjam') {
    $filter_role = " AND p.id_peminjam = $id_user_aktif";
} 

$sql_utama = "
    SELECT 
        p.kode_pinjam, 
        p.tgl_pesan, 
        p.tgl_ambil,
        p.tgl_wajibkembali, 
        p.tgl_kembali, 
        p.status_pinjam, 
        pm.nama_peminjam,
        a.nama_admin 
    FROM peminjaman p
    JOIN peminjam pm ON p.id_peminjam = pm.id_peminjam
    LEFT JOIN admin a ON p.id_admin = a.id_admin 
    WHERE p.kode_pinjam = '$kode_pinjam' $filter_role
";
$result_utama = $conn->query($sql_utama);

if ($result_utama->num_rows == 0) {
    $pesan_error = "Detil peminjaman dengan kode $kode_pinjam tidak ditemukan atau Anda tidak memiliki akses.";
    $data_peminjaman = false;
} else {
    $data_peminjaman = $result_utama->fetch_assoc();
}

// --- QUERY DETIL BUKU YANG DIPESAN ---
$sql_detil_buku = "
    SELECT b.judul_buku, b.nama_pengarang, b.nama_penerbit
    FROM detil_peminjaman dp
    JOIN buku b ON dp.id_buku = b.id_buku
    WHERE dp.kode_pinjam = '$kode_pinjam'
";
$result_detil_buku = $conn->query($sql_detil_buku);

// Tentukan link kembali (Sesuai role)
$link_kembali = ($role == 'admin' ? 'admin_' : '') . 'peminjaman_list.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detil Peminjaman | <?php echo $kode_pinjam; ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Konten dimulai dari atas */
            min-height: 100vh;
            margin: 0; 
            padding: 50px 0; /* Padding atas bawah untuk container */
            background-color: #f0f8ff; 
        }

        .container { 
            width: 100%;
            max-width: 650px; /* Diperkecil sesuai permintaan */
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); 
        }
        
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

        .detail-grid { 
            display: grid; 
            grid-template-columns: 150px 1fr; 
            gap: 10px; 
            margin-bottom: 20px; 
        }
        .detail-grid strong { 
            font-weight: bold; 
            text-align: right; 
        }
        .detail-grid span {
            text-align: left;
        }

        .status { font-weight: bold; padding: 5px 8px; border-radius: 4px; display: inline-block; font-size: 0.9em; }
        .status-diproses { background-color: #ffecb3; color: #ff9800; }
        .status-disetujui { background-color: #c8e6c9; color: #4caf50; }
        .status-ditolak { background-color: #ffcdd2; color: #f44336; }
        .status-selesai { background-color: #e3f2fd; color: #2196f3; }
        
        /* Tabel Buku */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9e9e9; color: #214453; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        
        <a href="<?php echo $link_kembali; ?>" class="btn-kembali">← Kembali ke Daftar</a>

        <header>
            <h1>Detil Pemesanan Peminjaman</h1>
            <hr>
        </header>

        <?php if ($data_peminjaman): ?>
            
            <?php 
                $status_lower = strtolower($data_peminjaman['status_pinjam']);
                $status_class = 'status-' . str_replace(' ', '-', $status_lower);
            ?>

            <h2>Kode Pinjam: <?php echo $data_peminjaman['kode_pinjam']; ?></h2>
            
            <div class="detail-grid">
                <strong>Peminjam</strong>
                <span>: <?php echo htmlspecialchars($data_peminjaman['nama_peminjam']); ?></span>
                
                <strong>Tgl Pesan</strong>
                <span>: <?php echo date('d M Y H:i', strtotime($data_peminjaman['tgl_pesan'])); ?></span>

                <strong>Status</strong>
                <span>: <span class="status <?php echo $status_class; ?>"><?php echo strtoupper($data_peminjaman['status_pinjam']); ?></span></span>

                <strong>Tgl Ambil/Setuju</strong>
                <span>: <?php echo $data_peminjaman['tgl_ambil'] ? date('d M Y H:i', strtotime($data_peminjaman['tgl_ambil'])) : '—'; ?></span>

                <strong>Wajib Kembali</strong>
                <span>: <?php echo $data_peminjaman['tgl_wajibkembali'] ? date('d M Y', strtotime($data_peminjaman['tgl_wajibkembali'])) : '—'; ?></span>
                
                <strong>Tgl Kembali Fisik</strong>
                <span>: <?php echo $data_peminjaman['tgl_kembali'] ? date('d M Y H:i', strtotime($data_peminjaman['tgl_kembali'])) : '—'; ?></span>
                
                <strong>Admin Proses</strong>
                <span>: <?php echo $data_peminjaman['nama_admin'] ? htmlspecialchars($data_peminjaman['nama_admin']) : '—'; ?></span>
            </div>

            <hr>

            <h3>Daftar Buku yang Dipinjam</h3>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Judul Buku</th>
                        <th>Pengarang</th>
                        <th>Penerbit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_detil_buku->num_rows > 0): ?>
                        <?php $no = 1; while($row = $result_detil_buku->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['judul_buku']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_pengarang']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_penerbit']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4">Tidak ada buku terdaftar dalam peminjaman ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
        <?php else: ?>
            <div class="alert-error">
                <?php echo $pesan_error; ?>
            </div>
        <?php endif; ?>

    </div>
    
    <?php $conn->close(); ?>
</body>
</html>