<?php
$pageTitle = 'Kelola Pengguna';
$activePage = 'users';
require_once '../config.php';
include_once 'templates/header.php';

$message = '';
$message_type = '';

// --- LOGIKA CRUD (TIDAK ADA PERUBAHAN) ---

// 1. Handle DELETE
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {
    $id_to_delete = (int)$_POST['id'];
    if ($id_to_delete === $_SESSION['user_id']) {
        $message = "Anda tidak dapat menghapus akun Anda sendiri!";
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            $message = "Pengguna berhasil dihapus!";
            $message_type = 'success';
        } else {
            $message = "Gagal menghapus pengguna. Error: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// 2. Handle CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_user'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);
    $user_id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0;

    // Validasi email unik (kecuali untuk user yang sedang diedit)
    $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check_email->bind_param("si", $email, $user_id);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();

    if ($stmt_check_email->num_rows > 0) {
        $message = "Email sudah digunakan oleh pengguna lain.";
        $message_type = 'error';
    } else {
        if ($user_id > 0) {
            // UPDATE
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nama, $email, $role, $hashed_password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nama, $email, $role, $user_id);
            }
            $action_message = "diperbarui";
        } else {
            // CREATE
            if (empty($password)) {
                $message = "Password wajib diisi untuk pengguna baru.";
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                $action_message = "ditambahkan";
            }
        }
        
        if (empty($message) && isset($stmt)) {
            if ($stmt->execute()) {
                $message = "Pengguna berhasil " . $action_message . "!";
                $message_type = 'success';
            } else {
                $message = "Gagal. Error: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
    $stmt_check_email->close();
}

// --- LOGIKA PENGAMBILAN DATA (TIDAK ADA PERUBAHAN) ---

// Statistik
$total_users = $conn->query("SELECT COUNT(id) as total FROM users")->fetch_assoc()['total'];
$total_mahasiswa = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'mahasiswa'")->fetch_assoc()['total'];
$total_asisten = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'asisten'")->fetch_assoc()['total'];

// Filter
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) ? trim($_GET['role']) : '';

$sql = "SELECT id, nama, email, role, created_at FROM users";
$where_clauses = [];
$params = [];
$types = '';

if (!empty($filter_search)) {
    $where_clauses[] = "(nama LIKE ? OR email LIKE ?)";
    $search_param = "%" . $filter_search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}
if (!empty($filter_role)) {
    $where_clauses[] = "role = ?";
    $params[] = $filter_role;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY created_at DESC";

$stmt_users = $conn->prepare($sql);
if (!empty($params)) {
    $stmt_users->bind_param($types, ...$params);
}
$stmt_users->execute();
$users_list_result = $stmt_users->get_result();

function getInitials($name) {
    $words = explode(' ', $name, 2);
    $initials = '';
    foreach ($words as $w) {
        $initials .= strtoupper($w[0]);
    }
    return $initials;
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Kelola Pengguna</h1>
    <p class="mt-1 text-sm text-gray-600">Tambah, edit, atau hapus data pengguna sistem.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="relative bg-white p-6 rounded-2xl shadow-sm overflow-hidden border border-gray-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <div class="relative z-10">
            <h3 class="text-sm font-medium text-gray-500">Total Pengguna</h3>
            <p class="mt-1 text-3xl font-bold text-gray-900"><?php echo $total_users; ?></p>
        </div>
        <svg class="absolute top-4 right-4 h-16 w-16 text-purple-500 opacity-20 z-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5zM10.5 18.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg>
    </div>
    <div class="relative bg-white p-6 rounded-2xl shadow-sm overflow-hidden border border-gray-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <div class="relative z-10">
            <h3 class="text-sm font-medium text-gray-500">Total Mahasiswa</h3>
            <p class="mt-1 text-3xl font-bold text-sky-600"><?php echo $total_mahasiswa; ?></p>
        </div>
        <svg class="absolute top-4 right-4 h-16 w-16 text-sky-500 opacity-20 z-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path d="M12 14l9-5-9-5-9 5 9 5z" /><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" /></svg>
    </div>
    <div class="relative bg-white p-6 rounded-2xl shadow-sm overflow-hidden border border-gray-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
        <div class="relative z-10">
            <h3 class="text-sm font-medium text-gray-500">Total Asisten</h3>
            <p class="mt-1 text-3xl font-bold text-green-600"><?php echo $total_asisten; ?></p>
        </div>
        <svg class="absolute top-4 right-4 h-16 w-16 text-green-500 opacity-20 z-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286zm0 13.036h.008v.008h-.008v-.008z" /></svg>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>" id="alert-box">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <?php if ($message_type == 'success'): ?>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                <?php else: ?>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                <?php endif; ?>
            </svg>
            <span><?php echo $message; ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Daftar Pengguna</h2>
            <div class="flex items-center gap-4 w-full sm:w-auto">
                <form action="manage_users.php" method="GET" class="flex-grow sm:flex-grow-0 flex items-center gap-2">
                    <input type="text" name="search" placeholder="Cari..." class="w-full sm:w-48 border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" value="<?php echo htmlspecialchars($filter_search); ?>">
                    <select name="role" class="border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="this.form.submit()">
                        <option value="">Semua Peran</option>
                        <option value="mahasiswa" <?php echo ($filter_role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                        <option value="asisten" <?php echo ($filter_role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                    </select>
                </form>
                <button onclick="openUserModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors flex items-center text-sm">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                    Tambah
                </button>
            </div>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pengguna</th>
                    <th class="py-3 px-6 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Peran</th>
                    <th class="py-3 px-6 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if ($users_list_result->num_rows > 0): ?>
                    <?php while($row = $users_list_result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                        <span class="font-bold text-sm text-purple-700"><?php echo getInitials($row['nama']); ?></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $row['role'] == 'asisten' ? 'bg-green-100 text-green-800' : 'bg-sky-100 text-sky-800'; ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center gap-4">
                                    <button onclick='openUserModal(<?php echo json_encode($row, JSON_HEX_APOS); ?>)' class="text-purple-600 hover:text-purple-800" title="Edit">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" /><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" /></svg>
                                    </button>
                                    <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                                        <button onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama'], ENT_QUOTES); ?>')" class="text-red-500 hover:text-red-700" title="Hapus">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.58.22-2.365.468a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193v-.443A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center py-12 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Pengguna Tidak Ditemukan</h3>
                        <p class="mt-1 text-sm text-gray-500">Tidak ada pengguna yang cocok dengan kriteria pencarian Anda.</p>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="user-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeUserModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="manage_users.php" method="POST">
                <input type="hidden" name="id" id="user-id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-center mb-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M11 5a3 3 0 11-6 0 3 3 0 016 0zM12.735 8.245A5.002 5.002 0 006.265 13H5a2 2 0 00-2 2v1a2 2 0 002 2h10a2 2 0 002-2v-1a2 2 0 00-2-2h-1.265a5.002 5.002 0 00-6.47-4.755z" /></svg>
                        </div>
                        <div class="ml-4 text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="user-modal-title">Tambah Pengguna Baru</h3>
                        </div>
                    </div>
                    <div class="mt-6 space-y-4">
                        <div>
                            <label for="user-nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                            <input type="text" id="user-nama" name="nama" class="mt-1 w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        </div>
                        <div>
                            <label for="user-email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" id="user-email" name="email" class="mt-1 w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                        </div>
                        <div>
                            <label for="user-password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" id="user-password" name="password" class="mt-1 w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Kosongkan jika tidak diubah">
                            <p class="text-xs text-gray-500 mt-1" id="password-hint">Untuk edit, kosongkan jika tidak ingin mengubah password.</p>
                        </div>
                        <div>
                            <label for="user-role" class="block text-sm font-medium text-gray-700">Peran</label>
                            <select id="user-role" name="role" class="mt-1 w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                                <option value="mahasiswa">Mahasiswa</option>
                                <option value="asisten">Asisten</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="save_user" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="closeUserModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="delete-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Hapus Pengguna</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Anda yakin ingin menghapus pengguna <strong id="user-name-to-delete" class="font-bold"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form action="manage_users.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="user-id-to-delete">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">Ya, Hapus</button>
                </form>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    const userModal = document.getElementById('user-modal');
    const deleteModal = document.getElementById('delete-modal');
    const passwordHint = document.getElementById('password-hint');

    function openUserModal(userData = null) {
        const form = userModal.querySelector('form');
        form.reset();
        document.getElementById('user-id').value = '';
        
        if (userData) {
            document.getElementById('user-modal-title').textContent = 'Edit Pengguna';
            document.getElementById('user-id').value = userData.id;
            document.getElementById('user-nama').value = userData.nama;
            document.getElementById('user-email').value = userData.email;
            document.getElementById('user-role').value = userData.role;
            document.getElementById('user-password').placeholder = 'Kosongkan jika tidak diubah';
            passwordHint.classList.remove('hidden');
        } else {
            document.getElementById('user-modal-title').textContent = 'Tambah Pengguna Baru';
            document.getElementById('user-password').placeholder = 'Wajib diisi';
            passwordHint.classList.add('hidden');
        }
        userModal.classList.remove('hidden');
    }

    function closeUserModal() {
        userModal.classList.add('hidden');
    }

    function openDeleteModal(id, name) {
        document.getElementById('user-name-to-delete').textContent = name;
        document.getElementById('user-id-to-delete').value = id;
        deleteModal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
    }

    const alertBox = document.getElementById('alert-box');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.transition = 'opacity 0.5s ease-in-out';
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 500);
        }, 5000);
    }
</script>

<?php 
$conn->close();
include_once 'templates/footer.php'; 
?>