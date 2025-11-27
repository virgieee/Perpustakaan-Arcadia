<?php 
// ... (Bagian PHP tidak berubah) ...
include 'koneksi.php'; 

// Cek apakah pengguna sudah login
if (isset($_SESSION['user_id'])) {
    $target_page = ($_SESSION['role'] == 'peminjam') ? 'peminjaman_list.php' : 'admin_peminjaman_list.php';
    header("Location: " . $target_page);
    exit();
}

$pesan_error = "";
$foto_path = NULL; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nama = $conn->real_escape_string($_POST['nama_peminjam']);
    $user = $conn->real_escape_string($_POST['user_peminjam']);
    $pass = $conn->real_escape_string($_POST['pass_peminjam']); 
    $konf_pass = $conn->real_escape_string($_POST['konf_pass']);

    if ($pass !== $konf_pass) {
        $pesan_error = "Konfirmasi password tidak cocok.";
    } 
    elseif (strlen($pass) > 20) {
        $pesan_error = "Password tidak boleh melebihi 20 karakter.";
    }
    else {
        $tgl_daftar = date('Y-m-d'); 
        
        // --- LOGIKA UPLOAD FOTO (Opsional) ---
        if (isset($_FILES['foto_peminjam']) && $_FILES['foto_peminjam']['error'] == 0) {
            $target_dir = "uploads/foto_peminjam/";
            $file_extension = pathinfo($_FILES['foto_peminjam']['name'], PATHINFO_EXTENSION);
            $new_file_name = "peminjam_" . uniqid() . "." . $file_extension;
            $target_file = $target_dir . $new_file_name;
            $uploadOk = 1;

            $check = getimagesize($_FILES['foto_peminjam']['tmp_name']);
            if($check === false) {
                $pesan_error = "File yang diunggah bukan gambar.";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (move_uploaded_file($_FILES['foto_peminjam']['tmp_name'], $target_file)) {
                    $foto_path = $target_file; 
                } else {
                    $pesan_error = "Terjadi kesalahan saat mengunggah file.";
                }
            }
        }
        
        if (empty($pesan_error)) {
            $sql = "INSERT INTO peminjam (nama_peminjam, tgl_daftar, user_peminjam, pass_peminjam, status_peminjam, foto_peminjam) 
                    VALUES ('$nama', '$tgl_daftar', '$user', '$pass', 'aktif', " . 
                    ($foto_path ? "'$foto_path'" : "NULL") . ")";

            if ($conn->query($sql) === TRUE) {
                echo "<script>alert('Pendaftaran Peminjam Berhasil! Akun Anda Aktif. Silakan masuk.'); window.location.href='login.php';</script>";
                exit();
            } else {
                if ($conn->errno == 1062) {
                     $pesan_error = "Username sudah digunakan. Silakan pilih yang lain.";
                } else {
                     $pesan_error = "Error: " . $conn->error;
                }
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran Peminjam</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            background-color: #ffffff; 
        }
        
        .navbar {
            background-color: #d4e9f7;
            padding: 15px 30px;
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
        
        .navbar-menu a {
            margin-left: 20px;
            text-decoration: none;
            color: #214453;
            transition: color 0.3s;
        }
        
        .navbar-menu a:hover {
            color: #0066cc;
        }
        
        .container { 
            max-width: 450px; 
            margin: 50px auto; 
            padding: 30px; 
            border: 1px solid #bcc7ccff; 
            border-radius: 10px; 
            background-color: white; 
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15); 
        }
        
        input[type=text], input[type=password], input[type=file] { 
            width: 100%; 
            padding: 10px; 
            margin: 8px 0 15px 0; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }

        .pesan-error { color: red; margin-bottom: 10px; text-align: left; }
        
        .btn { 
            display: block; 
            padding: 12px 25px; 
            margin: 10px 0; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold;
            transition: all 0.3s;
            width: 100%; 
            box-sizing: border-box; 
            background-color: #214453; 
            border: 2px solid #214453;
            color: white;
        }

        .btn:hover {
            background-color: #ffffff; 
            border-color: #0066cc;
            color: #0066cc;
            cursor: pointer;
        }
        
        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php" class="navbar-brand">
            <img src="books.png" alt="Icon">
            Arcadia
        </a>
        <div class="navbar-menu">
            <a href="login.php">Login</a>
        </div>
    </div>

    <div class="container">
        <h2>Registrasi</h2>
        <?php if (!empty($pesan_error)): ?>
            <p class="pesan-error">⚠️ <?php echo $pesan_error; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="registrasi.php" enctype="multipart/form-data">
            
            <label>Nama Peminjam</label>
            <input type="text" name="nama_peminjam" placeholder="Nama Lengkap" value="<?php echo htmlspecialchars($nama ?? ''); ?>" required>
            
            <label>Username</label>
            <input type="text" name="user_peminjam" placeholder="Username unik" value="<?php echo htmlspecialchars($user ?? ''); ?>" required>
            
            <label>Password</label>
            <input type="password" name="pass_peminjam" placeholder="Password (Max 20 Karakter)" maxlength="20" required> 
            
            <label>Konfirmasi Password</label>
            <input type="password" name="konf_pass" placeholder="Ulangi Password" maxlength="20" required>
            
            <label>Foto Peminjam (Opsional)</label>
            <input type="file" name="foto_peminjam"> 
            
            <button type="submit" class="btn">Daftar Akun</button>
            
        </form>
        <p style="margin-top: 15px;">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
</html>