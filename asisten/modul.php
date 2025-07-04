<?php
$pageTitle = 'Manajemen Modul';
$activePage = 'modul';
require_once '../config.php';
include_once 'templates/header.php';

$message = '';
$message_type = '';

// --- LOGIKA CRUD MODUL ---

// 1. Handle DELETE
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['modul_id'])) {
    $modul_id_to_delete = (int)$_POST['modul_id'];
    
    // Ambil nama file untuk dihapus dari server
    $stmt_file = $conn->prepare("SELECT file_materi FROM modul_praktikum WHERE id = ?");
    $stmt_file->bind_param("i", $modul_id_to_delete);
    $stmt_file->execute();
    $file_result = $stmt_file->get_result()->fetch_assoc();
    $stmt_file->close();
    
    // Hapus dari database
    $stmt_delete = $conn->prepare("DELETE FROM modul_praktikum WHERE id = ?");
    $stmt_delete->bind_param("i", $modul_id_to_delete);
    if ($stmt_delete->execute()) {
        // Jika berhasil, hapus file dari server
        if ($file_result && !empty($file_result['file_materi'])) {
            $file_path = '../uploads/materi/' . $file_result['file_materi'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $message = "Modul berhasil dihapus!";
        $message_type = 'success';
    } else {
        $message = "Gagal menghapus modul. Error: " . $stmt_delete->error;
        $message_type = 'error';
    }
    $stmt_delete->close();
}

// 2. Handle CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_modul'])) {
    $praktikum_id = (int)$_POST['praktikum_id'];
    $nama_modul = trim($_POST['nama_modul']);
    $deskripsi = trim($_POST['deskripsi']);
    $modul_id = isset($_POST['modul_id']) && !empty($_POST['modul_id']) ? (int)$_POST['modul_id'] : 0;
    
    $file_materi = $_POST['current_file'] ?? ''; // Ambil file yang sudah ada

    // Handle file upload
    if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == 0) {
        $upload_dir = '../uploads/materi/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $original_name = basename($_FILES["file_materi"]["name"]);
        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $unique_name = "materi_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
        $target_file = $upload_dir . $unique_name;
        
        if (move_uploaded_file($_FILES["file_materi"]["tmp_name"], $target_file)) {
            // Hapus file lama jika ada file baru yang diupload saat update
            if ($modul_id > 0 && !empty($file_materi) && file_exists($upload_dir . $file_materi)) {
                unlink($upload_dir . $file_materi);
            }
            $file_materi = $unique_name;
        } else {
             $message = "Gagal mengunggah file materi.";
             $message_type = 'error';
        }
    }

    if (empty($message)) {
        if ($modul_id > 0) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE modul_praktikum SET nama_modul = ?, deskripsi = ?, file_materi = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nama_modul, $deskripsi, $file_materi, $modul_id);
            $action_message = "diperbarui";
        } else {
            // CREATE
            $stmt = $conn->prepare("INSERT INTO modul_praktikum (praktikum_id, nama_modul, deskripsi, file_materi) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $praktikum_id, $nama_modul, $deskripsi, $file_materi);
            $action_message = "ditambahkan";
        }
        
        if ($stmt->execute()) {
            $message = "Modul berhasil " . $action_message . "!";
            $message_type = 'success';
        } else {
            $message = "Gagal. Error: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// --- LOGIKA PENGAMBILAN DATA ---

// Ambil daftar semua praktikum untuk dropdown
$praktikum_options = $conn->query("SELECT id, nama_praktikum FROM mata_praktikum ORDER BY nama_praktikum");

// Tentukan praktikum yang sedang dipilih
$selected_praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : 0;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_praktikum_id = (int)$_POST['praktikum_id'];
}

// Ambil modul untuk praktikum yang dipilih
$modul_list = [];
$nama_praktikum_terpilih = '';
if ($selected_praktikum_id > 0) {
    $stmt_nama = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
    $stmt_nama->bind_param("i", $selected_praktikum_id);
    $stmt_nama->execute();
    $nama_praktikum_terpilih = $stmt_nama->get_result()->fetch_assoc()['nama_praktikum'] ?? '';
    $stmt_nama->close();

    $stmt_modul = $conn->prepare("SELECT * FROM modul_praktikum WHERE praktikum_id = ? ORDER BY created_at ASC");
    $stmt_modul->bind_param("i", $selected_praktikum_id);
    $stmt_modul->execute();
    $result = $stmt_modul->get_result();
    while ($row = $result->fetch_assoc()) {
        $modul_list[] = $row;
    }
    $stmt_modul->close();
}
?>

<!-- Filter Praktikum -->
<div class="bg-white p-6 rounded-xl shadow-md border border-purple-100 mb-8">
    <form action="modul.php" method="GET">
        <label for="praktikum_id" class="block text-sm font-medium text-gray-700 mb-2">Pilih Mata Praktikum untuk Dikelola:</label>
        <div class="flex gap-2">
            <select name="praktikum_id" id="praktikum_id" 
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                <option value="">-- Pilih Praktikum --</option>
                <?php mysqli_data_seek($praktikum_options, 0); while($p = $praktikum_options->fetch_assoc()): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($selected_praktikum_id == $p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['nama_praktikum']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" 
                    class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                Tampilkan
            </button>
        </div>
    </form>
</div>

<?php if ($selected_praktikum_id > 0): ?>
    <?php if (!empty($message)): ?>
        <div class="mb-4 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>" id="alert-box">
            <div class="flex items-center">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabel Daftar Modul -->
    <div class="bg-white p-6 rounded-xl shadow-md border border-purple-100">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h2 class="text-xl font-semibold text-purple-800 flex items-center">
                <i class="fas fa-book-open mr-3 text-purple-600"></i> 
                Daftar Modul untuk: <span class="text-indigo-600 ml-2"><?php echo htmlspecialchars($nama_praktikum_terpilih); ?></span>
            </h2>
            <button onclick="openModulModal(<?php echo $selected_praktikum_id; ?>)" 
                    class="w-full md:w-auto bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-all flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i> Tambah Modul
            </button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-purple-50">
                    <tr>
                        <th class="py-3 px-6 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">Nama Modul</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">File Materi</th>
                        <th class="py-3 px-6 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (!empty($modul_list)): ?>
                        <?php foreach($modul_list as $modul): ?>
                            <tr class="hover:bg-purple-50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="text-sm font-medium text-purple-900"><?php echo htmlspecialchars($modul['nama_modul']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($modul['deskripsi'], 0, 70)) . '...'; ?></div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-sm">
                                    <?php if (!empty($modul['file_materi'])): ?>
                                        <a href="../uploads/materi/<?php echo $modul['file_materi']; ?>" 
                                           class="text-indigo-600 hover:text-indigo-800 hover:underline flex items-center" 
                                           target="_blank">
                                            <i class="fas fa-file-alt mr-2"></i> Lihat File
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400"><i class="fas fa-times-circle mr-2"></i> Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-center text-sm font-medium">
                                    <button onclick='openModulModal(<?php echo $selected_praktikum_id; ?>, <?php echo json_encode($modul, JSON_HEX_APOS); ?>)' 
                                            class="text-indigo-600 hover:text-indigo-900 mr-4" 
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="openDeleteModal(<?php echo $modul['id']; ?>, '<?php echo htmlspecialchars($modul['nama_modul'], ENT_QUOTES); ?>')" 
                                            class="text-red-600 hover:text-red-900" 
                                            title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-3xl text-purple-300 mb-2"></i>
                                <p>Belum ada modul untuk praktikum ini.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Tambah/Edit Modul -->
<div id="modul-modal" class="fixed z-20 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        
        <div class="inline-block align-bottom bg-white rounded-xl shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-purple-100">
            <form action="modul.php?praktikum_id=<?php echo $selected_praktikum_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-purple-800" id="modul-modal-title">Tambah Modul Baru</h3>
                        <button type="button" onclick="closeModulModal()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <input type="hidden" name="praktikum_id" id="modul-praktikum-id">
                        <input type="hidden" name="modul_id" id="modul-id">
                        <input type="hidden" name="current_file" id="modul-current-file">
                        
                        <div>
                            <label for="modul-nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Modul</label>
                            <input type="text" id="modul-nama" name="nama_modul" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                                   required>
                        </div>
                        
                        <div>
                            <label for="modul-deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea id="modul-deskripsi" name="deskripsi" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
                        </div>
                        
                        <div>
                            <label for="modul-file" class="block text-sm font-medium text-gray-700 mb-1">File Materi (Opsional)</label>
                            <div id="current-file-info" class="text-sm text-gray-500 mb-2"></div>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-300 transition-colors">
                                <input type="file" id="modul-file" name="file_materi" 
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div class="text-center">
                                    <i class="fas fa-cloud-upload-alt text-purple-500 text-3xl mb-2"></i>
                                    <p class="text-sm text-gray-600">Klik untuk memilih file atau drag & drop</p>
                                    <p class="text-xs text-gray-400 mt-1">Format: PDF, DOCX, PPTX (Maks: 10MB)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-xl">
                    <button type="button" onclick="closeModulModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                        Batal
                    </button>
                    <button type="submit" name="save_modul" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="delete-modal" class="fixed z-20 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        
        <div class="inline-block align-bottom bg-white rounded-xl shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-purple-100">
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="flex items-start">
                    <div class="bg-red-100 p-3 rounded-full mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Hapus Modul</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Anda yakin ingin menghapus modul <strong id="modul-name-to-delete" class="text-purple-800"></strong>? 
                                Tindakan ini tidak dapat dibatalkan dan file materi terkait juga akan dihapus.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                    Batal
                </button>
                <form action="modul.php?praktikum_id=<?php echo $selected_praktikum_id; ?>" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="modul_id" id="modul-id-to-delete">
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const modulModal = document.getElementById('modul-modal');
    const deleteModal = document.getElementById('delete-modal');

    function openModulModal(praktikumId, data = null) {
        const form = modulModal.querySelector('form');
        form.reset();
        document.getElementById('modul-praktikum-id').value = praktikumId;
        const currentFileInfo = document.getElementById('current-file-info');
        currentFileInfo.innerHTML = '';

        if (data) {
            document.getElementById('modul-modal-title').textContent = 'Edit Modul';
            document.getElementById('modul-id').value = data.id;
            document.getElementById('modul-nama').value = data.nama_modul;
            document.getElementById('modul-deskripsi').value = data.deskripsi;
            if (data.file_materi) {
                document.getElementById('modul-current-file').value = data.file_materi;
                currentFileInfo.innerHTML = `File saat ini: <a href="../uploads/materi/${data.file_materi}" class="text-purple-600 hover:underline" target="_blank">${data.file_materi}</a>`;
            }
        } else {
            document.getElementById('modul-modal-title').textContent = 'Tambah Modul Baru';
            document.getElementById('modul-id').value = '';
            document.getElementById('modul-current-file').value = '';
        }
        modulModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModulModal() {
        modulModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function openDeleteModal(id, name) {
        document.getElementById('modul-name-to-delete').textContent = name;
        document.getElementById('modul-id-to-delete').value = id;
        deleteModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    const alertBox = document.getElementById('alert-box');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.transition = 'opacity 0.5s';
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 500);
        }, 5000);
    }
</script>

<?php 
$conn->close();
include_once 'templates/footer.php'; 
?>