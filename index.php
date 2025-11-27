<?php include 'koneksi.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Landing Page - Perpustakaan Arcadia</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #ffffff; }
        

        .navbar {
            background-color: #d4e9f7;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        /* Content */
        .container { 
            max-width: 600px; 
            margin: 100px auto; 
            padding: 30px; 
            border: 1px solid #bcc7ccff; 
            border-radius: 10px; 
            background-color: white; 
            text-align: center;
        }
        
        .btn { 
            display: inline-block; 
            padding: 12px 25px; 
            margin: 10px; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-register { 
            background-color: #214453; 
            width: 40%; 
            border: 2px solid #214453;
            color: white;
        }
        
        .btn-register:hover {
            background-color: #0066cc; /* Biru terang */
            border-color: #0066cc;
        }
        
        .btn-login { 
            background-color: #ffffff; 
            width: 40%; 
            border: 2px solid #214453;
            color: #214453;
        }
        
        .btn-login:hover {
            background-color: #e8f4f8; /* Biru muda */
            border-color: #0066cc;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <a href="index.php" class="navbar-brand">
            <img src="books.png" alt="Icon">
            Arcadia
        </a>
        <div class="navbar-menu">
            <a href="login.php">Login</a>
            <a href="registrasi.php">Registrasi</a>
        </div>
    </div>

    <!-- Content -->
    <div class="container">
        <h1>Sistem Peminjaman Buku Arcadia</h1>
        <p>Akses cepat ke koleksi buku kami. Segera registrasi atau login!</p>

        <a href="registrasi.php" class="btn btn-register">Registrasi Peminjam</a>
        <br>
        <a href="login.php" class="btn btn-login">Login</a>
    </div>
</body>
</html>