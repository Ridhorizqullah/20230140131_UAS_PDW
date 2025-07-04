<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login atau bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Mahasiswa - <?php echo $pageTitle ?? 'SIMPRAK'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #ffffff, #f8fafc);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::before {
            width: 100%;
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .nav-link.active::before {
            width: 100%;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            transform: translateY(-2px);
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .logout-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .logout-btn:hover::before {
            left: 100%;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(255, 107, 107, 0.3);
        }
        
        .mobile-menu {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            margin-top: 0.5rem;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .mobile-menu-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .mobile-menu-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 0.5rem 1rem;
            margin-right: 1rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .main-content {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: calc(100vh - 64px);
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    
    <nav class="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="dashboard.php" class="logo text-2xl font-bold tracking-wide flex items-center">
                            <i class="fas fa-graduation-cap mr-2"></i>
                            SIMPRAK
                        </a>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <?php
                                // Menyiapkan class untuk link aktif dan tidak aktif dengan tema modern
                                $activeClass = 'active text-white';
                                $inactiveClass = 'text-gray-200 hover:text-white';
                            ?>
                            <a href="dashboard.php" class="nav-link <?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                <i class="fas fa-home mr-2"></i>
                                Dashboard
                            </a>
                            <a href="my_courses.php" class="nav-link <?php echo ($activePage == 'my_courses') ? $activeClass : $inactiveClass; ?> px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                <i class="fas fa-book mr-2"></i>
                                Praktikum Saya
                            </a>
                            <a href="detail_praktikum.php?id=1" class="nav-link <?php echo ($activePage == 'lihat detail') ? $activeClass : $inactiveClass; ?> px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                Detail Praktikum
                            </a>
                            <a href="../katalog.php" class="nav-link <?php echo ($activePage == 'katalog') ? $activeClass : $inactiveClass; ?> px-4 py-2 rounded-lg text-sm font-medium flex items-center">
                                <i class="fas fa-search mr-2"></i>
                                Cari Praktikum
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <div class="user-info flex items-center text-white text-sm">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span class="font-medium"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?></span>
                        </div>
                        <a href="../logout.php" class="logout-btn text-white font-medium py-2 px-4 rounded-lg transition-all duration-300 flex items-center">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </a>
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-btn" class="mobile-menu-btn text-white p-2 rounded-lg">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="mobile-menu hidden md:hidden mx-4 mb-4">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="dashboard.php" class="nav-link <?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-lg text-base font-medium">
                    <i class="fas fa-home mr-2"></i>
                    Dashboard
                </a>
                <a href="my_courses.php" class="nav-link <?php echo ($activePage == 'my_courses') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-lg text-base font-medium">
                    <i class="fas fa-book mr-2"></i>
                    Praktikum Saya
                </a>
                <a href="detail_praktikum.php?id=1" class="nav-link <?php echo ($activePage == 'lihat detail') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-lg text-base font-medium">
                    <i class="fas fa-info-circle mr-2"></i>
                    Detail Praktikum
                </a>
                <a href="../katalog.php" class="nav-link <?php echo ($activePage == 'katalog') ? $activeClass : $inactiveClass; ?> block px-3 py-2 rounded-lg text-base font-medium">
                    <i class="fas fa-search mr-2"></i>
                    Cari Praktikum
                </a>
                <div class="pt-4 border-t border-gray-200">
                    <div class="flex items-center px-3 py-2">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)); ?>
                        </div>
                        <span class="text-white font-medium"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?></span>
                    </div>
                    <a href="../logout.php" class="logout-btn block w-full text-left px-3 py-2 rounded-lg text-base font-medium text-white mt-2">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        
        <script>
            // Mobile menu toggle
            document.getElementById('mobile-menu-btn').addEventListener('click', function() {
                const menu = document.getElementById('mobile-menu');
                menu.classList.toggle('hidden');
            });
            
            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                const menu = document.getElementById('mobile-menu');
                const button = document.getElementById('mobile-menu-btn');
                
                if (!menu.contains(event.target) && !button.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });
        </script>