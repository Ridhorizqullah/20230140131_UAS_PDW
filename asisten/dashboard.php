<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once '../config.php';
include_once 'templates/header.php';

// --- LOGIKA PENGAMBILAN DATA STATISTIK ---

// 1. Total Modul
$total_modul_result = $conn->query("SELECT COUNT(id) as total FROM modul_praktikum");
$total_modul = $total_modul_result->fetch_assoc()['total'];

// 2. Total Laporan Masuk
$total_laporan_result = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum");
$total_laporan = $total_laporan_result->fetch_assoc()['total'];

// 3. Laporan Belum Dinilai
$laporan_pending_result = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum WHERE nilai IS NULL");
$laporan_pending = $laporan_pending_result->fetch_assoc()['total'];

// 4. Aktivitas Laporan Terbaru (5 terakhir)
$sql_recent = "SELECT lp.id, u.nama as nama_mahasiswa, mp.nama_modul, lp.submitted_at
               FROM laporan_praktikum lp
               JOIN users u ON lp.mahasiswa_id = u.id
               JOIN modul_praktikum mp ON lp.modul_id = mp.id
               ORDER BY lp.submitted_at DESC
               LIMIT 5";
$recent_activities = $conn->query($sql_recent);

// --- FUNGSI BANTU (HELPER FUNCTIONS) ---

// Fungsi untuk mendapatkan inisial nama
function getInitials($name) {
    $words = explode(' ', $name, 2);
    $initials = '';
    if (count($words) >= 2) {
        $initials = strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
    } elseif (!empty($name)) {
        $initials = strtoupper(substr($name, 0, 2));
    }
    return $initials;
}

// Fungsi untuk format waktu "time ago"
function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'tahun', 'm' => 'bulan', 'w' => 'minggu', 'd' => 'hari', 'h' => 'jam', 'i' => 'menit', 's' => 'detik',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yang lalu' : 'baru saja';
}
?>

<!-- Konten utama dashboard dimulai di sini -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-purple-900 mb-2">Selamat datang kembali!</h1>
    <p class="text-gray-600">Berikut adalah ringkasan aktivitas terbaru di sistem.</p>
</div>

<!-- Card Statistik -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Total Modul -->
    <div class="bg-white p-6 rounded-xl shadow-md border border-purple-100 hover:shadow-lg transition-shadow duration-300">
        <div class="flex items-center space-x-4">
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="fas fa-book text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
                <p class="text-2xl font-bold text-purple-800"><?php echo $total_modul; ?></p>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-purple-50">
            <a href="modul.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center">
                <i class="fas fa-eye mr-2"></i> Lihat semua modul
            </a>
        </div>
    </div>

    <!-- Total Laporan -->
    <div class="bg-white p-6 rounded-xl shadow-md border border-purple-100 hover:shadow-lg transition-shadow duration-300">
        <div class="flex items-center space-x-4">
            <div class="bg-indigo-100 p-3 rounded-full">
                <i class="fas fa-file-upload text-indigo-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Laporan Masuk</p>
                <p class="text-2xl font-bold text-indigo-800"><?php echo $total_laporan; ?></p>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-purple-50">
            <a href="laporan.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center">
                <i class="fas fa-list mr-2"></i> Kelola laporan
            </a>
        </div>
    </div>

    <!-- Laporan Belum Dinilai -->
    <div class="bg-white p-6 rounded-xl shadow-md border border-purple-100 hover:shadow-lg transition-shadow duration-300">
        <div class="flex items-center space-x-4">
            <div class="bg-pink-100 p-3 rounded-full">
                <i class="fas fa-clock text-pink-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
                <p class="text-2xl font-bold text-pink-600"><?php echo $laporan_pending; ?></p>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-purple-50">
            <a href="laporan.php?status=belum_dinilai" class="text-pink-600 hover:text-pink-800 text-sm font-medium flex items-center">
                <i class="fas fa-arrow-right mr-2"></i> Nilai sekarang
            </a>
        </div>
    </div>
</div>

<!-- Aktivitas Terbaru -->
<div class="bg-white p-6 rounded-xl shadow-md border border-purple-100">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-purple-800 flex items-center">
            <i class="fas fa-history mr-3 text-purple-600"></i> Aktivitas Laporan Terbaru
        </h3>
        <a href="laporan.php" class="text-sm text-purple-600 hover:text-purple-800 font-medium">
            Lihat Semua <i class="fas fa-chevron-right ml-1"></i>
        </a>
    </div>
    
    <div class="space-y-4">
        <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
            <?php while($activity = $recent_activities->fetch_assoc()): ?>
                <div class="flex items-start p-3 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-200 to-indigo-200 flex items-center justify-center mr-4 shrink-0">
                        <span class="font-bold text-purple-800"><?php echo getInitials($activity['nama_mahasiswa']); ?></span>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-800">
                            <strong class="text-purple-900"><?php echo htmlspecialchars($activity['nama_mahasiswa']); ?></strong> mengumpulkan laporan untuk <strong class="text-indigo-700"><?php echo htmlspecialchars($activity['nama_modul']); ?></strong>
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="far fa-clock mr-1"></i> <?php echo time_ago($activity['submitted_at']); ?>
                        </p>
                    </div>
                    <div class="ml-4">
                        <a href="laporan_detail.php?id=<?php echo $activity['id']; ?>" class="text-xs text-purple-600 hover:text-purple-800">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-inbox text-4xl text-purple-300 mb-3"></i>
                <p class="text-gray-500">Belum ada aktivitas laporan.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>