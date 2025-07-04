<?php
// filepath: c:\xampp\htdocs\tugas\tugas\katalog.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// --- LOGIKA PENCARIAN ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_practicums = "SELECT id, nama_praktikum, deskripsi, created_at FROM mata_praktikum";
$params = [];
$types = '';

if (!empty($search_query)) {
    $sql_practicums .= " WHERE nama_praktikum LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= 's';
}
$sql_practicums .= " ORDER BY created_at DESC";

$stmt_practicums = $conn->prepare($sql_practicums);
if (!empty($params)) {
    $stmt_practicums->bind_param($types, ...$params);
}
$stmt_practicums->execute();
$all_practicums_result = $stmt_practicums->get_result();

// --- LOGIKA CEK STATUS PENDAFTARAN (JIKA MAHASISWA LOGIN) ---
$registered_practicum_ids = [];
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa') {
    $mahasiswa_id = $_SESSION['user_id'];
    $sql_registered = "SELECT praktikum_id FROM pendaftaran_praktikum WHERE mahasiswa_id = ?";
    $stmt_registered = $conn->prepare($sql_registered);
    $stmt_registered->bind_param("i", $mahasiswa_id);
    $stmt_registered->execute();
    $registered_result = $stmt_registered->get_result();
    while ($row = $registered_result->fetch_assoc()) {
        $registered_practicum_ids[] = $row['praktikum_id'];
    }
    $stmt_registered->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Praktikum - SIMPRAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .search-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background: linear-gradient(135deg, #5a6fd1 0%, #6a42a1 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .practicum-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(118, 75, 162, 0.1);
        }
        
        .practicum-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
            border-color: rgba(118, 75, 162, 0.2);
        }
        
        .register-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        
        .register-btn:hover {
            background: linear-gradient(135deg, #5a6fd1 0%, #6a42a1 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .login-btn {
            transition: all 0.3s ease;
            border: 1px solid #667eea;
        }
        
        .login-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigasi Publik -->
    <nav class="navbar text-white sticky top-0 z-10">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="katalog.php" class="text-2xl font-bold tracking-wide flex items-center">
                <i class="fas fa-graduation-cap mr-2"></i>
                SIMPRAK
            </a>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="nav-link hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    <a href="logout.php" class="logout-btn text-white font-medium py-2 px-4 rounded-lg transition-all duration-300 flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="login-btn text-purple-100 hover:text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                    <a href="register.php" class="register-btn text-white font-medium py-2 px-4 rounded-lg transition-all duration-300 flex items-center">
                        <i class="fas fa-user-plus mr-2"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Header Halaman dan Form Pencarian -->
        <div class="text-center bg-white p-8 rounded-xl shadow-lg mb-8 border border-purple-100">
            <h1 class="text-4xl font-extrabold text-purple-800 mb-4">Katalog Mata Praktikum</h1>
            <p class="text-gray-600 mt-2 max-w-2xl mx-auto">Temukan dan daftar untuk praktikum yang Anda minati. Mulai perjalanan belajar Anda bersama kami hari ini!</p>
            <form method="GET" action="katalog.php" class="mt-6 max-w-lg mx-auto">
                <div class="flex shadow-md rounded-lg overflow-hidden">
                    <input type="text" name="search" class="w-full px-4 py-3 border-0 focus:ring-2 focus:ring-purple-500" placeholder="Cari berdasarkan nama praktikum..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-btn text-white font-bold py-3 px-5 flex items-center">
                        <i class="fas fa-search mr-2"></i> Cari
                    </button>
                </div>
            </form>
        </div>

        <!-- Notifikasi (AJAX) -->
        <div id="ajax-alert" class="mb-4 p-4 rounded-lg text-center hidden border"></div>

        <!-- Daftar Praktikum -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($all_practicums_result->num_rows > 0): ?>
                <?php while ($praktikum = $all_practicums_result->fetch_assoc()): ?>
                    <div class="practicum-card bg-white rounded-xl shadow-md p-6 flex flex-col justify-between hover:shadow-xl">
                        <div>
                            <div class="flex items-center mb-3">
                                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-flask text-purple-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-purple-900"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                            </div>
                            <p class="text-gray-600 text-sm h-20 overflow-hidden mb-4"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
                            <div class="text-xs text-gray-400">
                                <i class="far fa-calendar-alt mr-1"></i> 
                                <?php echo date('d M Y', strtotime($praktikum['created_at'])); ?>
                            </div>
                        </div>
                        <div class="mt-6">
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa'): ?>
                                <?php if (in_array($praktikum['id'], $registered_practicum_ids)): ?>
                                    <span id="status-<?php echo $praktikum['id']; ?>" class="w-full block text-center bg-green-500 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Terdaftar
                                    </span>
                                <?php else: ?>
                                    <!-- Tombol Daftar Sekarang dengan Modal Konfirmasi -->
                                    <button type="button"
                                        onclick="showModal('<?php echo $praktikum['id']; ?>')"
                                        id="btn-daftar-<?php echo $praktikum['id']; ?>"
                                        class="w-full register-btn text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300 flex items-center justify-center">
                                        <i class="fas fa-user-plus mr-2"></i> Daftar Sekarang
                                    </button>
                                    <!-- Modal Konfirmasi -->
                                    <div id="modal-<?php echo $praktikum['id']; ?>" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
                                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 border border-purple-100">
                                            <div class="flex items-center mb-4">
                                                <div class="bg-purple-100 p-2 rounded-full mr-3">
                                                    <i class="fas fa-exclamation-circle text-purple-600"></i>
                                                </div>
                                                <h3 class="text-lg font-bold text-purple-800">Konfirmasi Pendaftaran</h3>
                                            </div>
                                            <p class="mb-6 text-gray-600">Apakah Anda yakin ingin mendaftar praktikum <b><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></b>?</p>
                                            <form id="form-daftar-<?php echo $praktikum['id']; ?>" onsubmit="return daftarPraktikumAjax(event, '<?php echo $praktikum['id']; ?>');">
                                                <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
                                                <div class="flex justify-end gap-3">
                                                    <button type="button" 
                                                            onclick="closeModal('<?php echo $praktikum['id']; ?>')" 
                                                            class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium transition-colors">
                                                        Tidak
                                                    </button>
                                                    <button type="submit" 
                                                            class="px-4 py-2 rounded-lg register-btn text-white font-medium transition-colors flex items-center">
                                                        <i class="fas fa-check mr-2"></i> Ya, Daftar
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="w-full block text-center login-btn text-purple-600 hover:text-purple-800 font-bold py-2 px-4 rounded-lg transition-colors duration-300 flex items-center justify-center">
                                    <i class="fas fa-sign-in-alt mr-2"></i> Login untuk Daftar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12 bg-white rounded-lg shadow-md border border-purple-100">
                    <i class="fas fa-search text-4xl text-purple-400 mb-4"></i>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Tidak Ditemukan</h3>
                    <p class="mt-1 text-sm text-gray-500">Tidak ada mata praktikum yang sesuai dengan pencarian Anda.</p>
                    <a href="katalog.php" class="mt-4 inline-block text-purple-600 hover:text-purple-800">
                        <i class="fas fa-undo mr-1"></i> Lihat Semua Praktikum
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showModal(id) {
            document.getElementById('modal-' + id).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(id) {
            document.getElementById('modal-' + id).classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // AJAX daftar praktikum
        function daftarPraktikumAjax(e, id) {
            e.preventDefault();
            const form = document.getElementById('form-daftar-' + id);
            const formData = new FormData(form);
            
            const alertBox = document.getElementById('ajax-alert');
            alertBox.classList.remove('hidden');
            alertBox.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses pendaftaran...';
            alertBox.className = "mb-4 p-4 rounded-lg text-center bg-blue-100 text-blue-800 border border-blue-200";
            
            fetch('mahasiswa/daftar_praktikum.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeModal(id);
                if (data.status === 'success') {
                    alertBox.className = "mb-4 p-4 rounded-lg text-center bg-green-100 text-green-800 border border-green-200";
                    alertBox.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Pendaftaran praktikum berhasil!';
                    document.getElementById('btn-daftar-' + id).outerHTML =
                        '<span id="status-' + id + '" class="w-full block text-center bg-green-500 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">' +
                        '<i class="fas fa-check-circle mr-2"></i> Terdaftar</span>';
                } else if (data.status === 'kelas_penuh') {
                    alertBox.className = "mb-4 p-4 rounded-lg text-center bg-red-100 text-red-800 border border-red-200";
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Kelas yang Anda pilih sudah penuh.';
                } else if (data.status === 'error') {
                    alertBox.className = "mb-4 p-4 rounded-lg text-center bg-red-100 text-red-800 border border-red-200";
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Gagal mendaftar, Anda mungkin sudah terdaftar.';
                } else if (data.status === 'notloggedin') {
                    alertBox.className = "mb-4 p-4 rounded-lg text-center bg-red-100 text-red-800 border border-red-200";
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Anda harus login sebagai mahasiswa untuk mendaftar.';
                } else {
                    alertBox.className = "mb-4 p-4 rounded-lg text-center bg-red-100 text-red-800 border border-red-200";
                    alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Terjadi kesalahan. Silakan coba lagi.';
                }
                
                setTimeout(() => {
                    alertBox.style.transition = 'opacity 0.5s';
                    alertBox.style.opacity = '0';
                    setTimeout(() => { 
                        alertBox.classList.add('hidden'); 
                        alertBox.style.opacity = '1'; 
                    }, 500);
                }, 4000);
            })
            .catch(() => {
                closeModal(id);
                alertBox.className = "mb-4 p-4 rounded-lg text-center bg-red-100 text-red-800 border border-red-200";
                alertBox.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Gagal terhubung ke server. Silakan coba lagi.';
                
                setTimeout(() => {
                    alertBox.style.transition = 'opacity 0.5s';
                    alertBox.style.opacity = '0';
                    setTimeout(() => { 
                        alertBox.classList.add('hidden'); 
                        alertBox.style.opacity = '1'; 
                    }, 500);
                }, 4000);
            });
            
            return false;
        }
    </script>
</body>
</html>
<?php
$stmt_practicums->close();
$conn->close();
?>