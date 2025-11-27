<?php 
include 'koneksi.php'; 

// 1. Cek status login dan Redirect
if (isset($_SESSION['user_id'])) {
    $target_page = ($_SESSION['role'] == 'peminjam') ? 'peminjaman_list.php' : 'admin_peminjaman_list.php';
    header("Location: " . $target_page);
    exit();
}

$pesan_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $conn->real_escape_string($_POST['username']);
    $pass = $_POST['password']; 
    
    $loggedIn = false;

    // Cek 1: COBA LOGIN SEBAGAI ADMIN
    $sql_admin = "SELECT id_admin, pass_admin FROM admin WHERE user_admin = '$user'";
    $result_admin = $conn->query($sql_admin);

    if ($result_admin->num_rows == 1) {
        $row = $result_admin->fetch_assoc();
        
        if ($pass === $row['pass_admin']) {
            $_SESSION['user_id'] = $row['id_admin'];
            $_SESSION['role'] = 'admin';
            $loggedIn = true;
            header("Location: admin_peminjaman_list.php");
            exit();
        }
    }

    // Cek 2: JIKA BUKAN ADMIN, COBA LOGIN SEBAGAI PEMINJAM
    if (!$loggedIn) {
        $sql_peminjam = "SELECT id_peminjam, pass_peminjam, status_peminjam FROM peminjam WHERE user_peminjam = '$user'";
        $result_peminjam = $conn->query($sql_peminjam);
        
        if ($result_peminjam->num_rows == 1) {
            $row = $result_peminjam->fetch_assoc();
            
            if ($pass === $row['pass_peminjam']) {
                if ($row['status_peminjam'] == 'aktif') {
                    $_SESSION['user_id'] = $row['id_peminjam'];
                    $_SESSION['role'] = 'peminjam';
                    $loggedIn = true;
                    header("Location: peminjaman_list.php");
                    exit();
                } else {
                     $pesan_error = "Akun Anda tidak aktif. Silakan hubungi administrator.";
                }
            }
        }
    }

    // Cek 3: GAGAL TOTAL
    if (!$loggedIn && empty($pesan_error)) {
        $pesan_error = "Username atau password salah.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Aplikasi Perpustakaan</title>
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
        
        /* --- Content Container (Sama dengan Index.php) --- */
        .container { 
            max-width: 400px; 
            margin: 50px auto; 
            padding: 30px; 
            border: 1px solid #bcc7ccff; 
            border-radius: 10px; 
            background-color: white; 
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15); 
        }
        
        input[type=text], input[type=password]{ 
            width: 100%; 
            padding: 10px; 
            margin: 8px 0 15px 0; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        
        .btn { 
            display: block; /* Agar tombol mengambil lebar penuh */
            padding: 12px 25px; 
            margin: 10px 0; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold;
            transition: all 0.3s;
            width: 100%; 
            box-sizing: border-box; 
        }
        
        .btn-register { 
            background-color: #214453; 
            border: 2px solid #214453;
            color: white;
        }
        
        .btn-register:hover {
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
            <a href="registrasi.php">Registrasi</a>
        </div>
    </div>

    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($pesan_error)): ?>
            <p class="pesan-error">❌ <?php echo $pesan_error; ?></p>
        <?php endif; ?>
        
    <form method="POST" action="login.php">
        <label>Username</label> 
        <input type="text" name="username" placeholder="Masukkan Username" required>
        
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan Password" required>
        
        <button type="submit" class="btn btn-register">Login</button> 
    </form>
        <p style="margin-top: 15px;">Belum punya akun? <a href="registrasi.php">Daftar di sini</a></p>
    </div>
</body>
</html>