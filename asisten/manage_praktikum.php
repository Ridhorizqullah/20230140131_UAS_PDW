<?php
$pageTitle = 'Kelola Praktikum';
$activePage = 'praktikum';
require_once '../config.php';
include_once 'templates/header.php';

$message = '';
$message_type = '';

// --- LOGIKA CRUD ---

// 1. Handle DELETE
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {
    $id_to_delete = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM mata_praktikum WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Mata praktikum berhasil dihapus!";
        $message_type = 'success';
    } else {
        $message = "Gagal menghapus. Praktikum ini mungkin masih memiliki modul atau pendaftar. Error: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// 2. Handle CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_praktikum'])) {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    $praktikum_id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($praktikum_id > 0) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $praktikum_id);
        $action_message = "diperbarui";
    } else {
        // CREATE
        $stmt = $conn->prepare("INSERT INTO mata_praktikum (nama_praktikum, deskripsi) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama_praktikum, $deskripsi);
        $action_message = "ditambahkan";
    }
    
    if ($stmt->execute()) {
        $message = "Mata praktikum berhasil " . $action_message . "!";
        $message_type = 'success';
    } else {
        $message = "Gagal. Error: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// --- LOGIKA PENGAMBILAN DATA ---

// Statistik
$total_praktikum = $conn->query("SELECT COUNT(id) as total FROM mata_praktikum")->fetch_assoc()['total'];
$total_modul = $conn->query("SELECT COUNT(id) as total FROM modul_praktikum")->fetch_assoc()['total'];
$total_pendaftar = $conn->query("SELECT COUNT(id) as total FROM pendaftaran_praktikum")->fetch_assoc()['total'];

// Filter dan Pencarian
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT mp.*, 
               (SELECT COUNT(id) FROM modul_praktikum WHERE praktikum_id = mp.id) as jumlah_modul,
               (SELECT COUNT(id) FROM pendaftaran_praktikum WHERE praktikum_id = mp.id) as jumlah_mahasiswa
        FROM mata_praktikum mp";

if (!empty($filter_search)) {
    $sql .= " WHERE mp.nama_praktikum LIKE ?";
}
$sql .= " ORDER BY mp.created_at DESC";

$stmt_praktikum = $conn->prepare($sql);
if (!empty($filter_search)) {
    $search_param = "%" . $filter_search . "%";
    $stmt_praktikum->bind_param("s", $search_param);
}
$stmt_praktikum->execute();
$praktikum_list_result = $stmt_praktikum->get_result();
?>

<!-- Kartu Statistik -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-md border border-purple-100 hover:shadow-lg transition-shadow duration-300">
        <div class="flex items-center space-x-4">
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="fas fa-flask text-purple-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Praktikum</p>
                <p class="text-2xl font-bold text-purple-800"><?php echo $total_praktikum; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md border border-purple-100 hover:shadow-lg transition-shadow duration-300">
        <div class="flex items-center space-x-4">
            <div class="bg-indigo-100 p-3 rounded-full">
                <i class="fas fa-book text-indigo-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Modul</p>
                <p class="text-2xl font-bold text-indigo-800"><?php echo $total_modul; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md border border-purple-100 hover:shadow-lg transition-shadow duration-300">
        <div class="flex items-center space-x-4">
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Pendaftar</p>
                <p class="text-2xl font-bold text-green-800"><?php echo $total_pendaftar; ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="mb-4 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>" id="alert-box">
        <div class="flex items-center">
            <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
            <?php echo $message; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Filter dan Tabel Praktikum -->
<div class="bg-white p-6 rounded-xl shadow-md border border-purple-100">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-xl font-semibold text-purple-800 flex items-center">
            <i class="fas fa-list-ul mr-3 text-purple-600"></i> Daftar Mata Praktikum
        </h2>
        <div class="flex items-center gap-3">
            <form action="manage_praktikum.php" method="GET" class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" name="search" placeholder="Cari praktikum..." 
                           class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           value="<?php echo htmlspecialchars($filter_search); ?>">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                    Cari
                </button>
            </form>
            <button onclick="openPraktikumModal()" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-all flex items-center">
                <i class="fas fa-plus mr-2"></i> Tambah
            </button>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-purple-50">
                <tr>
                    <th class="py-3 px-6 text-left text-xs font-medium text-purple-800 uppercase tracking-wider">Nama Praktikum</th>
                    <th class="py-3 px-6 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Jumlah Modul</th>
                    <th class="py-3 px-6 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Jumlah Mahasiswa</th>
                    <th class="py-3 px-6 text-center text-xs font-medium text-purple-800 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if ($praktikum_list_result->num_rows > 0): ?>
                    <?php while($row = $praktikum_list_result->fetch_assoc()): ?>
                        <tr class="hover:bg-purple-50 transition-colors">
                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="text-sm font-medium text-purple-900"><?php echo htmlspecialchars($row['nama_praktikum']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 70)) . '...'; ?></div>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center text-sm text-gray-600"><?php echo $row['jumlah_modul']; ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-center text-sm text-gray-600"><?php echo $row['jumlah_mahasiswa']; ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-center text-sm font-medium">
                                <button onclick='openPraktikumModal(<?php echo json_encode($row, JSON_HEX_APOS); ?>)' 
                                        class="text-indigo-600 hover:text-indigo-900 mr-4" 
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_praktikum'], ENT_QUOTES); ?>')" 
                                        class="text-red-600 hover:text-red-900" 
                                        title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-3xl text-purple-300 mb-2"></i>
                            <p>Tidak ada praktikum ditemukan.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Praktikum -->
<div id="praktikum-modal" class="fixed z-20 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        
        <div class="inline-block align-bottom bg-white rounded-xl shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-purple-100">
            <form action="manage_praktikum.php" method="POST">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-purple-800" id="praktikum-modal-title">Tambah Praktikum Baru</h3>
                        <button type="button" onclick="closePraktikumModal()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <input type="hidden" name="id" id="praktikum-id">
                        
                        <div>
                            <label for="praktikum-nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Praktikum</label>
                            <input type="text" id="praktikum-nama" name="nama_praktikum" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                                   required>
                        </div>
                        
                        <div>
                            <label for="praktikum-deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea id="praktikum-deskripsi" name="deskripsi" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 rounded-b-xl">
                    <button type="button" onclick="closePraktikumModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                        Batal
                    </button>
                    <button type="submit" name="save_praktikum" 
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
                        <h3 class="text-lg font-bold text-gray-900">Hapus Praktikum</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Anda yakin ingin menghapus praktikum <strong id="praktikum-name-to-delete" class="text-purple-800"></strong>? 
                                Semua modul dan data pendaftaran terkait akan ikut terhapus.
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
                <form action="manage_praktikum.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="praktikum-id-to-delete">
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
    const praktikumModal = document.getElementById('praktikum-modal');
    const deleteModal = document.getElementById('delete-modal');

    function openPraktikumModal(data = null) {
        const form = praktikumModal.querySelector('form');
        form.reset();
        if (data) {
            document.getElementById('praktikum-modal-title').textContent = 'Edit Praktikum';
            document.getElementById('praktikum-id').value = data.id;
            document.getElementById('praktikum-nama').value = data.nama_praktikum;
            document.getElementById('praktikum-deskripsi').value = data.deskripsi;
        } else {
            document.getElementById('praktikum-modal-title').textContent = 'Tambah Praktikum Baru';
            document.getElementById('praktikum-id').value = '';
        }
        praktikumModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closePraktikumModal() {
        praktikumModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function openDeleteModal(id, name) {
        document.getElementById('praktikum-name-to-delete').textContent = name;
        document.getElementById('praktikum-id-to-delete').value = id;
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