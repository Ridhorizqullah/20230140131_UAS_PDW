<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$header_path = __DIR__ . '/templates/header_mahasiswa.php';
$footer_path = __DIR__ . '/templates/footer_mahasiswa.php';

require_once __DIR__ . '/../config.php';

if (file_exists($header_path)) {
    include_once $header_path;
} else {
    die("<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #fff0f0; border: 1px solid #ffbaba; color: #d8000c;'>
            <strong>Error:</strong> File <code>header_mahasiswa.php</code> tidak ditemukan di folder <code>mahasiswa/templates/</code>.
         </div>");
}

if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

$mahasiswa_id = $_SESSION['user_id'];
$nama_mahasiswa = $_SESSION['nama'];

// --- STATISTIK ---
$stmt_praktikum = $conn->prepare("SELECT COUNT(*) as total FROM pendaftaran_praktikum WHERE mahasiswa_id = ?");
$stmt_praktikum->bind_param("i", $mahasiswa_id);
$stmt_praktikum->execute();
$total_praktikum = $stmt_praktikum->get_result()->fetch_assoc()['total'];
$stmt_praktikum->close();

$stmt_selesai = $conn->prepare("SELECT COUNT(*) as total FROM laporan_praktikum WHERE mahasiswa_id = ? AND nilai IS NOT NULL");
$stmt_selesai->bind_param("i", $mahasiswa_id);
$stmt_selesai->execute();
$total_selesai = $stmt_selesai->get_result()->fetch_assoc()['total'];
$stmt_selesai->close();

$stmt_menunggu = $conn->prepare("SELECT COUNT(*) as total FROM laporan_praktikum WHERE mahasiswa_id = ? AND nilai IS NULL");
$stmt_menunggu->bind_param("i", $mahasiswa_id);
$stmt_menunggu->execute();
$total_menunggu = $stmt_menunggu->get_result()->fetch_assoc()['total'];
$stmt_menunggu->close();

// Notifikasi nilai terakhir
$sql_notif = "SELECT lp.praktikum_id, m.nama_modul, lp.nilai, lp.submitted_at
              FROM laporan_praktikum lp
              JOIN modul_praktikum m ON lp.modul_id = m.id
              WHERE lp.mahasiswa_id = ? AND lp.nilai IS NOT NULL
              ORDER BY lp.submitted_at DESC
              LIMIT 3";
$stmt_notif = $conn->prepare($sql_notif);
$stmt_notif->bind_param("i", $mahasiswa_id);
$stmt_notif->execute();
$notifikasi_list = $stmt_notif->get_result();
$stmt_notif->close();
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
        font-family: 'Inter', sans-serif;
    }
    
    .dashboard-container {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .welcome-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.5rem;
        border-radius: 24px;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    .welcome-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    .welcome-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }
    
    .welcome-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }
    
    .stat-card {
        background: white;
        padding: 2rem;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover::before {
        transform: scaleX(1);
    }
    
    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    .stat-number {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-label {
        color: #64748b;
        font-weight: 500;
        font-size: 1rem;
    }
    
    .stat-icon {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        opacity: 0.8;
    }
    
    .notifications-section {
        background: white;
        padding: 2rem;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .notification-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .notification-item {
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .notification-item:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border-color: #667eea;
    }
    
    .notification-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .notification-item:hover::before {
        opacity: 1;
    }
    
    .notification-content {
        color: #334155;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    .notification-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    
    .notification-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }
    
    .notification-time {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 400;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #64748b;
        font-style: italic;
    }
    
    .empty-state::before {
        content: '📭';
        display: block;
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .welcome-section {
            padding: 2rem;
        }
        
        .welcome-title {
            font-size: 2rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .stat-card {
            padding: 1.5rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
        }
        
        .notifications-section {
            padding: 1.5rem;
        }
    }
    
    /* Animation */
    .dashboard-container > * {
        animation: fadeInUp 0.6s ease forwards;
        opacity: 0;
        transform: translateY(20px);
    }
    
    .welcome-section {
        animation-delay: 0.1s;
    }
    
    .stats-grid {
        animation-delay: 0.2s;
    }
    
    .notifications-section {
        animation-delay: 0.3s;
    }
    
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stat-card:nth-child(1) { animation-delay: 0.4s; }
    .stat-card:nth-child(2) { animation-delay: 0.5s; }
    .stat-card:nth-child(3) { animation-delay: 0.6s; }
</style>

<div class="dashboard-container">
    <!-- Selamat Datang -->
    <div class="welcome-section">
        <h1 class="welcome-title">Halo, <?php echo htmlspecialchars(strtok($nama_mahasiswa, ' ')); ?> 👋</h1>
        <p class="welcome-subtitle">Selamat datang kembali di SIMPRAK. Semangat terus menyelesaikan tugasmu!</p>
    </div>

    <!-- Statistik -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-number"><?php echo $total_praktikum; ?></div>
            <p class="stat-label">Praktikum Diikuti</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?php echo $total_selesai; ?></div>
            <p class="stat-label">Tugas Selesai</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-number"><?php echo $total_menunggu; ?></div>
            <p class="stat-label">Tugas Menunggu</p>
        </div>
    </div>

    <!-- Notifikasi Terbaru -->
    <div class="notifications-section">
        <h2 class="section-title">🔔 Notifikasi Terbaru</h2>
        <ul class="notification-list">
            <?php if ($notifikasi_list && $notifikasi_list->num_rows > 0): ?>
                <?php while($notif = $notifikasi_list->fetch_assoc()): ?>
                    <li class="notification-item">
                        <div class="notification-content">
                            Nilai untuk <a href="detail_praktikum.php?id=<?php echo $notif['praktikum_id']; ?>" class="notification-link">
                                <?php echo htmlspecialchars($notif['nama_modul']); ?>
                            </a> telah diberikan.
                        </div>
                        <div class="notification-time">
                            Dikirim: <?php echo date('d M Y, H:i', strtotime($notif['submitted_at'])); ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li class="empty-state">
                    Tidak ada notifikasi terbaru.
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php
$conn->close();
if (file_exists($footer_path)) {
    include_once $footer_path;
} else {
    die("<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #fff0f0; border: 1px solid #ffbaba; color: #d8000c;'>
            <strong>Error:</strong> File <code>footer_mahasiswa.php</code> tidak ditemukan.
         </div>");
}
?>