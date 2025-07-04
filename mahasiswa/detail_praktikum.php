<?php
// filepath: c:\xampp\htdocs\tugas\tugas\mahasiswa\detail_praktikum.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

$pageTitle = 'Detail Praktikum';
$activePage = 'lihat detail';

$header_path = __DIR__ . '/templates/header_mahasiswa.php';
$footer_path = __DIR__ . '/templates/footer_mahasiswa.php';

// Redirect jika bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$mahasiswa_id = $_SESSION['user_id'];

// Initialize variables
$praktikum_list = [];
$modul_map = [];
$laporan_data = [];

// Ambil semua praktikum yang diikuti mahasiswa
$stmt = $conn->prepare("SELECT mp.id, mp.nama_praktikum, mp.deskripsi 
                        FROM mata_praktikum mp
                        JOIN pendaftaran_praktikum pp ON mp.id = pp.praktikum_id
                        WHERE pp.mahasiswa_id = ?");
$stmt->bind_param("i", $mahasiswa_id);
$stmt->execute();
$praktikum_result = $stmt->get_result();
$stmt->close();

while ($praktikum = $praktikum_result->fetch_assoc()) {
    $praktikum_list[] = $praktikum;
}

// Ambil semua modul untuk semua praktikum
foreach ($praktikum_list as $praktikum) {
    $stmt = $conn->prepare("SELECT id, nama_modul, file_materi FROM modul_praktikum WHERE praktikum_id = ?");
    $stmt->bind_param("i", $praktikum['id']);
    $stmt->execute();
    $modul_result = $stmt->get_result();
    while ($modul = $modul_result->fetch_assoc()) {
        $modul_map[$praktikum['id']][] = $modul;
    }
    $stmt->close();
}

// Ambil semua laporan mahasiswa untuk semua modul
$stmt = $conn->prepare("SELECT modul_id, file_laporan, nilai FROM laporan_praktikum WHERE mahasiswa_id = ?");
$stmt->bind_param("i", $mahasiswa_id);
$stmt->execute();
$laporan_result = $stmt->get_result();

while ($row = $laporan_result->fetch_assoc()) {
    $laporan_data[$row['modul_id']] = $row;
}
$stmt->close();

if (file_exists($header_path)) {
    include_once $header_path;
}
?>

<!-- Notifikasi -->
<?php if (isset($_GET['status']) && $_GET['status'] === 'laporan_deleted'): ?>
    <div class="mb-4 p-4 rounded-lg bg-purple-100 text-purple-800 border border-purple-200">
        <i class="fas fa-check-circle mr-2"></i> Pengumpulan laporan berhasil dibatalkan.
    </div>
<?php elseif (isset($_GET['status']) && $_GET['status'] === 'laporan_edited'): ?>
    <div class="mb-4 p-4 rounded-lg bg-indigo-100 text-indigo-800 border border-indigo-200">
        <i class="fas fa-check-circle mr-2"></i> Laporan berhasil diubah.
    </div>
<?php endif; ?>

<!-- Daftar Semua Praktikum yang Diikuti -->
<?php if (count($praktikum_list) === 0): ?>
    <div class="p-6 rounded-lg bg-white shadow-md border border-purple-100 text-purple-600">
        <i class="fas fa-info-circle mr-2"></i> Anda belum terdaftar pada praktikum apapun.
    </div>
<?php else: ?>
    <?php foreach ($praktikum_list as $praktikum): ?>
        <div class="mb-8 bg-white rounded-xl shadow-lg overflow-hidden border border-purple-50">
            <!-- Informasi Praktikum -->
            <div class="p-6 bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
                <h2 class="text-2xl font-bold mb-2 flex items-center">
                    <i class="fas fa-book-open mr-3"></i> <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
                </h2>
                <p class="text-purple-100"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
            </div>

            <!-- Daftar Modul -->
            <div class="p-6">
                <h3 class="text-xl font-semibold text-purple-800 mb-4 flex items-center">
                    <i class="fas fa-list-ul mr-2"></i> Daftar Modul
                </h3>
                
                <?php if (!empty($modul_map[$praktikum['id']])): ?>
                    <div class="space-y-4">
                        <?php foreach ($modul_map[$praktikum['id']] as $modul): ?>
                            <div class="bg-white p-5 rounded-lg shadow-sm border border-purple-50 hover:shadow-md transition-shadow duration-300">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="text-lg font-bold text-purple-900 mb-2 flex items-center">
                                            <i class="fas fa-file-alt mr-2 text-purple-600"></i> <?php echo htmlspecialchars($modul['nama_modul']); ?>
                                        </h4>
                                        <p class="text-gray-600 mb-3">
                                            <span class="font-medium">Materi:</span>
                                            <?php if (!empty($modul['file_materi'])): ?>
                                                <a href="../uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" 
                                                   class="text-purple-600 hover:text-purple-800 font-medium ml-1" download>
                                                   <i class="fas fa-download mr-1"></i> Unduh Materi
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 ml-1">Belum ada file materi</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Status Laporan -->
                                <?php if (isset($laporan_data[$modul['id']])): ?>
                                    <div class="bg-purple-50 p-3 rounded-lg mb-3 border border-purple-100">
                                        <div class="flex items-center text-purple-700 mb-1">
                                            <i class="fas fa-check-circle mr-2"></i> 
                                            <span class="font-medium">Laporan telah dikumpulkan</span>
                                        </div>
                                        <div class="flex flex-wrap gap-4 text-sm">
                                            <p class="text-gray-700">
                                                <span class="font-medium">Nilai:</span> 
                                                <span class="font-bold <?php echo isset($laporan_data[$modul['id']]['nilai']) ? 'text-purple-600' : 'text-gray-500' ?>">
                                                    <?php echo $laporan_data[$modul['id']]['nilai'] ?? 'Belum dinilai'; ?>
                                                </span>
                                            </p>
                                            <p class="text-gray-500">
                                                <span class="font-medium">File:</span> 
                                                <?php echo htmlspecialchars($laporan_data[$modul['id']]['file_laporan']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-3 mt-4">
                                        <!-- Tombol Edit -->
                                        <button type="button" 
                                                onclick="showEditForm('<?php echo $modul['id']; ?>','<?php echo $praktikum['id']; ?>')" 
                                                class="flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                            <i class="fas fa-edit mr-2"></i> Edit
                                        </button>
                                        
                                        <!-- Tombol Batal Pengumpulan -->
                                        <button type="button" 
                                                onclick="showBatalModal('<?php echo $modul['id']; ?>')" 
                                                class="flex items-center bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                                            <i class="fas fa-times mr-2"></i> Batal
                                        </button>
                                    </div>
                                    
                                    <!-- Modal Konfirmasi Batal -->
                                    <div id="modal-batal-<?php echo $modul['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 border border-purple-100">
                                            <div class="flex items-center mb-4">
                                                <div class="bg-purple-100 p-2 rounded-full mr-3">
                                                    <i class="fas fa-exclamation-circle text-purple-600 text-xl"></i>
                                                </div>
                                                <h3 class="text-lg font-bold text-purple-800">Konfirmasi Batal Pengumpulan</h3>
                                            </div>
                                            <p class="mb-6 text-gray-600">Apakah Anda yakin ingin membatalkan pengumpulan laporan untuk modul ini?</p>
                                            <form method="POST" action="batal_laporan.php">
                                                <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                                <div class="flex justify-end gap-3">
                                                    <button type="button" 
                                                            onclick="closeBatalModal('<?php echo $modul['id']; ?>')" 
                                                            class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium transition-colors">
                                                        Tidak
                                                    </button>
                                                    <button type="submit" 
                                                            class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-medium transition-colors">
                                                        Ya, Batalkan
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal Edit Laporan -->
                                    <div id="modal-edit-<?php echo $modul['id']; ?>" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                                        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 border border-purple-100">
                                            <div class="flex items-center mb-4">
                                                <div class="bg-indigo-100 p-2 rounded-full mr-3">
                                                    <i class="fas fa-edit text-indigo-600 text-xl"></i>
                                                </div>
                                                <h3 class="text-lg font-bold text-indigo-800">Edit Laporan</h3>
                                            </div>
                                            <form method="POST" action="edit_laporan.php" enctype="multipart/form-data">
                                                <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                                <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
                                                <div class="mb-4">
                                                    <label class="block mb-2 font-medium text-gray-700">File Laporan Baru</label>
                                                    <div class="relative border-2 border-dashed border-purple-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                                                        <input type="file" name="file_laporan" required 
                                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                                        <div class="text-center">
                                                            <i class="fas fa-cloud-upload-alt text-purple-500 text-3xl mb-2"></i>
                                                            <p class="text-sm text-gray-600">Klik untuk memilih file atau drag & drop</p>
                                                            <p class="text-xs text-gray-400 mt-1">Format: PDF, DOCX (Maks: 5MB)</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex justify-end gap-3">
                                                    <button type="button" 
                                                            onclick="closeEditModal('<?php echo $modul['id']; ?>')" 
                                                            class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium transition-colors">
                                                        Batal
                                                    </button>
                                                    <button type="submit" 
                                                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors flex items-center">
                                                        <i class="fas fa-save mr-2"></i> Simpan
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Form Upload Laporan -->
                                    <form method="POST" action="upload_laporan.php" enctype="multipart/form-data" class="mt-4">
                                        <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                        <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">

                                        <div class="mb-4">
                                            <label class="block mb-2 font-medium text-gray-700">Upload Laporan</label>
                                            <div class="relative border-2 border-dashed border-purple-200 rounded-lg p-4 hover:border-purple-300 transition-colors">
                                                <input type="file" name="file_laporan" required 
                                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                                <div class="text-center">
                                                    <i class="fas fa-cloud-upload-alt text-purple-500 text-3xl mb-2"></i>
                                                    <p class="text-sm text-gray-600">Klik untuk memilih file atau drag & drop</p>
                                                    <p class="text-xs text-gray-400 mt-1">Format: PDF, DOCX (Maks: 5MB)</p>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" 
                                                class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 flex items-center justify-center">
                                            <i class="fas fa-upload mr-2"></i> Upload Laporan
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-purple-50 p-4 rounded-lg border border-purple-100 text-purple-600">
                        <i class="fas fa-info-circle mr-2"></i> Belum ada modul pada praktikum ini.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function showBatalModal(modulId) {
    document.getElementById('modal-batal-' + modulId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeBatalModal(modulId) {
    document.getElementById('modal-batal-' + modulId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}
function showEditForm(modulId, praktikumId) {
    document.getElementById('modal-edit-' + modulId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeEditModal(modulId) {
    document.getElementById('modal-edit-' + modulId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}
</script>

<?php
if (file_exists($footer_path)) {
    include_once $footer_path;
}
$conn->close();
?>