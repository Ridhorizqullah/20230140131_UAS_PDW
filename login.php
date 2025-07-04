<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'asisten') {
        header("Location: asisten/dashboard.php");
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
    }
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
    } else {
        $sql = "SELECT id, nama, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Password benar, simpan semua data penting ke session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                // ====== INI BAGIAN YANG DIUBAH ======
                // Logika untuk mengarahkan pengguna berdasarkan peran (role)
                if ($user['role'] == 'asisten') {
                    header("Location: asisten/dashboard.php");
                    exit();
                } elseif ($user['role'] == 'mahasiswa') {
                    header("Location: mahasiswa/dashboard.php");
                    exit();
                } else {
                    // Fallback jika peran tidak dikenali
                    $message = "Peran pengguna tidak valid.";
                }
                // ====== AKHIR DARI BAGIAN YANG DIUBAH ======

            } else {
                $message = "Password yang Anda masukkan salah.";
            }
        } else {
            $message = "Akun dengan email tersebut tidak ditemukan.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Akademik</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%" r="50%"><stop offset="0%" style="stop-color:rgba(255,255,255,0.1)"/><stop offset="100%" style="stop-color:rgba(255,255,255,0)"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="400" cy="700" r="120" fill="url(%23a)"/></svg>') no-repeat center center;
            background-size: cover;
            opacity: 0.3;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 50px 40px;
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .container:hover {
            transform: translateY(-8px);
            box-shadow: 0 35px 70px rgba(0, 0, 0, 0.2);
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
        }

        .logo::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine 3s infinite;
        }

        .logo::after {
            content: "🎓";
            font-size: 30px;
            position: relative;
            z-index: 1;
        }

        @keyframes shine {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        h2 {
            color: #333;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            font-weight: 400;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #555;
            font-weight: 600;
            font-size: 15px;
            transition: color 0.3s ease;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 18px 55px 18px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            color: #333;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-group input:focus + .input-icon {
            color: #667eea;
            transform: translateY(-50%) scale(1.1);
        }

        .form-group input::placeholder {
            color: #aaa;
            font-size: 14px;
        }

        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #aaa;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 25px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            width: 100%;
            font-size: 17px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .message {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            border: 1px solid;
            animation: slideIn 0.4s ease;
            position: relative;
        }

        .message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 12px 12px 0 0;
        }

        .message.success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-color: #6ee7b7;
        }

        .message.success::before {
            background: linear-gradient(135deg, #059669, #10b981);
        }

        .message:not(.success) {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #b91c1c;
            border-color: #fca5a5;
        }

        .message:not(.success)::before {
            background: linear-gradient(135deg, #dc2626, #ef4444);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }

        .register-link p {
            color: #666;
            font-size: 15px;
            margin-bottom: 10px;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: inline-block;
            background: rgba(102, 126, 234, 0.1);
        }

        .register-link a:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(102, 126, 234, 0.05);
        }

        .forgot-password a:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        @media (max-width: 480px) {
            .container {
                padding: 35px 25px;
                margin: 15px;
                border-radius: 20px;
            }
            
            h2 {
                font-size: 28px;
            }
            
            .form-group input {
                padding: 16px 50px 16px 18px;
            }
            
            .btn {
                padding: 16px 20px;
                font-size: 16px;
            }
        }

        /* Floating particles animation */
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) {
            width: 20px;
            height: 20px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .particle:nth-child(2) {
            width: 15px;
            height: 15px;
            top: 70%;
            left: 80%;
            animation-delay: 2s;
        }

        .particle:nth-child(3) {
            width: 25px;
            height: 25px;
            top: 40%;
            left: 5%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
</head>
<body>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    
    <div class="container">
        <div class="header">
            <div class="logo"></div>
            <h2>Selamat Datang</h2>
            <p class="subtitle">Masuk ke Sistem Akademik</p>
        </div>

        <?php 
            if (isset($_GET['status']) && $_GET['status'] == 'registered') {
                echo '<div class="message success">🎉 Registrasi berhasil! Silakan login.</div>';
            }
            if (!empty($message)) {
                echo '<div class="message">⚠️ ' . $message . '</div>';
            }
        ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" placeholder="Masukkan alamat email Anda" required>
                    <span class="input-icon">📧</span>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="Masukkan kata sandi Anda" required>
                    <span class="input-icon">🔒</span>
                </div>
            </div>
            <button type="submit" class="btn">Masuk</button>
        </form>

        <div class="forgot-password">
            <a href="forgot-password.php">Lupa kata sandi?</a>
        </div>

        <div class="register-link">
            <p>Belum punya akun?</p>
            <a href="register.php">Daftar di sini</a>
        </div>
    </div>

    <script>
        // Add smooth focus transitions
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.form-group').classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.form-group').classList.remove('focused');
            });
        });

        // Add form submission animation
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn');
            btn.style.transform = 'scale(0.95)';
            btn.innerHTML = '<div style="display: inline-block; width: 20px; height: 20px; border: 2px solid transparent; border-top: 2px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>';
            
            setTimeout(() => {
                btn.style.transform = 'scale(1)';
            }, 100);
        });

        // Add CSS for button loading animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>