<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Daftar Praktikum';
$activePage = 'katalog';

$header_path = __DIR__ . '/templates/header_mahasiswa.php';
$footer_path = __DIR__ . '/templates/footer_mahasiswa.php';

require_once __DIR__ . '/../config.php';

$message = '';

// Handle pendaftaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['daftar'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
        $message = "Anda harus login sebagai mahasiswa untuk mendaftar praktikum.";
    } else {
        $mahasiswa_id = $_SESSION['user_id'];
        $praktikum_id = $_POST['praktikum_id'];

        $sql_check = "SELECT id FROM pendaftaran_praktikum WHERE mahasiswa_id = ? AND praktikum_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $mahasiswa_id, $praktikum_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $message = "Anda sudah terdaftar pada praktikum ini.";
        } else {
            $sql_insert = "INSERT INTO pendaftaran_praktikum (mahasiswa_id, praktikum_id) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $mahasiswa_id, $praktikum_id);
            $stmt_insert->execute();
            $message = $stmt_insert->affected_rows > 0 ? "Berhasil mendaftar praktikum!" : "Gagal mendaftar. Coba lagi.";
        }
    }
}

// Ambil semua mata praktikum
$sql = "SELECT id, nama_praktikum, deskripsi, created_at FROM mata_praktikum ORDER BY created_at DESC";
$result = $conn->query($sql);

// Ambil data praktikum yang sudah diikuti oleh mahasiswa
$praktikum_diikuti = [];
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa') {
    $mahasiswa_id = $_SESSION['user_id'];
    $sql_diikuti = "SELECT praktikum_id FROM pendaftaran_praktikum WHERE mahasiswa_id = ?";
    $stmt_diikuti = $conn->prepare($sql_diikuti);
    $stmt_diikuti->bind_param("i", $mahasiswa_id);
    $stmt_diikuti->execute();
    $result_diikuti = $stmt_diikuti->get_result();

    while ($row = $result_diikuti->fetch_assoc()) {
        $praktikum_diikuti[] = $row['praktikum_id'];
    }
}

if (file_exists($header_path)) {
    include_once $header_path;
} else {
    die("<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #fff0f0; border: 1px solid #ffbaba; color: #d8000c;'>
        <strong>Error:</strong> File <code>header_mahasiswa.php</code> tidak ditemukan.
    </div>");
}
?>

<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 2rem 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><polygon points="0,0 1000,0 1000,100"/></svg>');
        background-size: cover;
    }
    
    .hero-content {
        position: relative;
        z-index: 1;
    }
    
    .praktikum-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    
    .praktikum-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    
    .praktikum-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }
    
    .praktikum-title {
        color: #1f2937;
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .praktikum-description {
        color: #6b7280;
        line-height: 1.6;
        margin-bottom: 1rem;
    }
    
    .praktikum-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        color: #9ca3af;
        margin-bottom: 1.5rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
        width: 100%;
        cursor: pointer;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        width: 100%;
        cursor: not-allowed;
        opacity: 0.8;
    }
    
    .btn-outline {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-outline:hover {
        background: #667eea;
        color: white;
        text-decoration: none;
    }
    
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 0.75rem;
        margin-bottom: 2rem;
        border: 1px solid;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .alert-success {
        background: #ecfdf5;
        color: #065f46;
        border-color: #10b981;
    }
    
    .alert-info {
        background: #eff6ff;
        color: #1e40af;
        border-color: #3b82f6;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    .grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stats-badge {
        background: #f3f4f6;
        color: #374151;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .hero-section {
            padding: 2rem 0;
            margin-bottom: 1rem;
        }
        
        .grid-container {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .praktikum-card {
            padding: 1rem;
        }
    }
</style>

<!-- Hero Section -->
<div class="hero-section">
    <div class="hero-content max-w-7xl mx-auto px-4">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4">🎓 Katalog Praktikum</h1>
            <p class="text-xl opacity-90">Jelajahi dan daftarkan diri Anda pada mata praktikum yang tersedia</p>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4">
    <!-- Notifikasi -->
    <?php if (!empty($message)): ?>
        <div class="alert <?php echo strpos($message, 'Berhasil') !== false ? 'alert-success' : 'alert-info'; ?>">
            <span class="text-2xl"><?php echo strpos($message, 'Berhasil') !== false ? '✅' : 'ℹ️'; ?></span>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Daftar Praktikum -->
    <?php if ($result->num_rows > 0): ?>
        <!-- Stats -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">Mata Praktikum Tersedia</h2>
            <div class="stats-badge">
                <?php echo $result->num_rows; ?> praktikum ditemukan
            </div>
        </div>

        <div class="grid-container">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="praktikum-card">
                    <div class="praktikum-title">
                        <span class="text-2xl">🔬</span>
                        <?php echo htmlspecialchars($row['nama_praktikum']); ?>
                    </div>
                    
                    <div class="praktikum-description">
                        <?php echo htmlspecialchars($row['deskripsi']); ?>
                    </div>
                    
                    <div class="praktikum-meta">
                        <span>📅</span>
                        <span>Dibuat pada: <?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
                    </div>

                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa'): ?>
                        <?php if (in_array($row['id'], $praktikum_diikuti)): ?>
                            <button class="btn-success" disabled>
                                ✅ Sudah Terdaftar
                            </button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="praktikum_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="daftar" class="btn-primary">
                                    📝 Daftar Praktikum
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center">
                            <p class="text-gray-500 mb-3">🔐 Login diperlukan untuk mendaftar</p>
                            <a href="../login.php" class="btn-outline">
                                Masuk Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Belum Ada Praktikum</h3>
            <p class="text-gray-500">Mata praktikum akan segera tersedia. Silakan kembali lagi nanti!</p>
        </div>
    <?php endif; ?>
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