<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login atau bukan asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'asisten') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Asisten - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        secondary: {
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-item {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .sidebar-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: #0ea5e9;
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.3s ease;
        }
        .sidebar-item:hover::before {
            transform: scaleY(1);
        }
        .sidebar-item.active::before {
            transform: scaleY(1);
        }
        .sidebar-item.active {
            background: rgba(14, 165, 233, 0.08);
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-sm border-r border-gray-200 flex flex-col fixed h-full z-10">
        <div class="p-6 text-center border-b border-gray-100">
            <h3 class="text-xl font-semibold text-gray-800">Panel Asisten</h3>
            <div class="flex items-center justify-center mt-4">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-medium shadow-sm">
                    <?php echo substr(htmlspecialchars($_SESSION['nama']), 0, 1); ?>
                </div>
                <div class="ml-3 text-left">
                    <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
                    <span class="text-xs text-primary-600 font-medium">Asisten</span>
                </div>
            </div>
        </div>
        <nav class="flex-grow px-3 py-6">
            <ul class="space-y-1">
                <?php 
                    $activeClass = 'active text-primary-600';
                    $inactiveClass = 'text-gray-600 hover:text-primary-600';
                ?>
                <li class="sidebar-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                    <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> flex items-center px-4 py-2.5 rounded-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        <span class="font-medium">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item <?php echo ($activePage == 'praktikum') ? 'active' : ''; ?>">
                    <a href="manage_praktikum.php" class="<?php echo ($activePage == 'praktikum') ? $activeClass : $inactiveClass; ?> flex items-center px-4 py-2.5 rounded-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                        <span class="font-medium">Kelola Praktikum</span>
                    </a>
                </li>
                <li class="sidebar-item <?php echo ($activePage == 'modul') ? 'active' : ''; ?>">
                    <a href="modul.php" class="<?php echo ($activePage == 'modul') ? $activeClass : $inactiveClass; ?> flex items-center px-4 py-2.5 rounded-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        <span class="font-medium">Manajemen Modul</span>
                    </a>
                </li>
                <li class="sidebar-item <?php echo ($activePage == 'laporan') ? 'active' : ''; ?>">
                    <a href="laporan.php" class="<?php echo ($activePage == 'laporan') ? $activeClass : $inactiveClass; ?> flex items-center px-4 py-2.5 rounded-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75c0-.231-.035-.454-.1-.664M6.75 7.5h1.5M6.75 12h1.5m6.75 0h1.5m-1.5 3h1.5m-1.5 3h1.5M4.5 6.75h1.5v1.5H4.5v-1.5zM4.5 12h1.5v1.5H4.5v-1.5zM4.5 17.25h1.5v1.5H4.5v-1.5z" />
                        </svg>
                        <span class="font-medium">Laporan Masuk</span>
                    </a>
                </li>
                <li class="sidebar-item <?php echo ($activePage == 'users') ? 'active' : ''; ?>">
                    <a href="pengguna.php" class="<?php echo ($activePage == 'users') ? $activeClass : $inactiveClass; ?> flex items-center px-4 py-2.5 rounded-lg transition-all duration-200">
                        <svg class="w-5 h-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-2.438c.155-.19.293-.385.435-.586a21.115 21.115 0 00-9.252-12.121 9.337 9.337 0 00-3.463-.684A9.365 9.365 0 005.475 6.474a21.115 21.115 0 00-9.252 12.121c.142.201.28.396.435.586a9.337 9.337 0 004.121 2.438 9.38 9.38 0 002.625.372M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="font-medium">Kelola Pengguna</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="p-4 mt-auto border-t border-gray-100">
            <a href="../logout.php" class="flex items-center justify-center bg-white border border-gray-200 hover:border-primary-500 hover:text-primary-600 text-gray-600 font-medium py-2 px-4 rounded-lg transition-all duration-200 w-full shadow-sm hover:shadow-md">
                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                <span>Keluar</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col ml-64">
        <header class="bg-white shadow-sm p-6 sticky top-0 z-10">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white font-medium mr-3 shadow-sm">
                            <?php echo substr(htmlspecialchars($_SESSION['nama']), 0, 1); ?>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
                            <p class="text-xs text-primary-600 font-medium">Asisten</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <main class="flex-1 p-6 lg:p-8 bg-gray-50">