<?php
$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';
require_once '../config.php';
include_once 'templates/header.php';

// ... (SEMUA KODE PHP ANDA TETAP SAMA DI SINI) ...
$message = '';
$message_type = '';

// --- LOGIKA PENILAIAN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_nilai'])) {
    $laporan_id = (int)$_POST['laporan_id'];
    $nilai = $_POST['nilai'];
    $feedback = trim($_POST['feedback']);

    $stmt = $conn->prepare("UPDATE laporan_praktikum SET nilai = ?, feedback = ? WHERE id = ?");
    $stmt->bind_param("dsi", $nilai, $feedback, $laporan_id);
    if ($stmt->execute()) {
        $message = "Nilai berhasil disimpan!";
        $message_type = 'success';
    } else {
        $message = "Gagal menyimpan nilai. Error: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// --- LOGIKA FILTER ---
$filter_praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : 0;
$filter_modul_id = isset($_GET['modul_id']) ? (int)$_GET['modul_id'] : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_mahasiswa = isset($_GET['mahasiswa']) ? trim($_GET['mahasiswa']) : '';

// --- LOGIKA STATISTIK ---
$total_laporan = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum")->fetch_assoc()['total'];
$laporan_dinilai = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum WHERE nilai IS NOT NULL")->fetch_assoc()['total'];
$laporan_pending = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum WHERE nilai IS NULL")->fetch_assoc()['total'];

// Ambil daftar praktikum untuk filter
$praktikum_options = $conn->query("SELECT id, nama_praktikum FROM mata_praktikum ORDER BY nama_praktikum");

// Ambil daftar modul jika praktikum dipilih
$modul_options = null;
if ($filter_praktikum_id > 0) {
    $stmt_modul = $conn->prepare("SELECT id, nama_modul FROM modul_praktikum WHERE praktikum_id = ? ORDER BY nama_modul");
    $stmt_modul->bind_param("i", $filter_praktikum_id);
    $stmt_modul->execute();
    $modul_options = $stmt_modul->get_result();
}

// --- LOGIKA PENGAMBILAN DATA LAPORAN ---
$sql_base = "FROM laporan_praktikum lp JOIN users u ON lp.mahasiswa_id = u.id JOIN mata_praktikum mp ON lp.praktikum_id = mp.id JOIN modul_praktikum m ON lp.modul_id = m.id";
$where_clauses = [];
$params = [];
$types = '';

if ($filter_praktikum_id > 0) { $where_clauses[] = "lp.praktikum_id = ?"; $params[] = $filter_praktikum_id; $types .= 'i'; }
if ($filter_modul_id > 0) { $where_clauses[] = "lp.modul_id = ?"; $params[] = $filter_modul_id; $types .= 'i'; }
if ($filter_status === 'dinilai') { $where_clauses[] = "lp.nilai IS NOT NULL"; }
if ($filter_status === 'belum_dinilai') { $where_clauses[] = "lp.nilai IS NULL"; }
if (!empty($filter_mahasiswa)) { $where_clauses[] = "u.nama LIKE ?"; $params[] = "%" . $filter_mahasiswa . "%"; $types .= 's'; }

$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- LOGIKA PAGINATION ---
$sql_count = "SELECT COUNT(lp.id) as total " . $sql_base . $where_sql;
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) { $stmt_count->bind_param($types, ...$params); }
$stmt_count->execute();
$total_results = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

$limit = 10;
$total_pages = $total_results > 0 ? ceil($total_results / $limit) : 1;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $limit;

// --- Ambil data laporan dengan limit/offset ---
$sql_data = "SELECT lp.id, u.nama as nama_mahasiswa, mp.nama_praktikum, m.nama_modul, lp.file_laporan, lp.nilai, lp.feedback, lp.submitted_at " . $sql_base . $where_sql . " ORDER BY lp.submitted_at DESC LIMIT ?, ?";
$final_params = $params;
$final_params[] = $offset;
$final_params[] = $limit;
$final_types = $types . 'ii';

$stmt_laporan = $conn->prepare($sql_data);
if (!empty($where_clauses)) { $stmt_laporan->bind_param($final_types, ...$final_params); } else { $stmt_laporan->bind_param("ii", $offset, $limit); }
$stmt_laporan->execute();
$laporan_list = $stmt_laporan->get_result();

function getInitials($name) {
    $words = explode(' ', $name, 2);
    $initials = '';
    foreach ($words as $w) { $initials .= strtoupper($w[0]); }
    return $initials;
}
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Laporan Masuk</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola dan nilai laporan praktikum yang dikumpulkan mahasiswa.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="#" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-purple-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                            Dashboard
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Laporan Masuk</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="relative bg-white p-6 rounded-2xl shadow-sm overflow-hidden border border-gray-200 transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1">
            <div class="relative z-10">
                <h3 class="text-sm font-medium text-gray-500">Total Laporan</h3>
                <p class="mt-1 text-3xl font-bold text-gray-900"><?php echo $total_laporan; ?></p>
            </div>
            <svg class="absolute top-4 right-4 h-16 w-16 text-purple-500 opacity-20 z-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75c0-.231-.035-.454-.1-.664M6.75 7.5h1.5M6.75 12h1.5m6.75 0h1.5m-1.5 3h1.5m-1.5 3h1.5M4.5 6.75h1.5v1.5H4.5v-1.5zM4.5 12h1.5v1.5H4.5v-1.5zM4.5 17.25h1.5v1.5H4.5v-1.5z" /></svg>
        </div>
        <div class="relative bg-white p-6 rounded-2xl shadow-sm overflow-hidden border border-gray-200 transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1">
            <div class="relative z-10">
                <h3 class="text-sm font-medium text-gray-500">Sudah Dinilai</h3>
                <p class="mt-1 text-3xl font-bold text-green-600"><?php echo $laporan_dinilai; ?></p>
            </div>
            <svg class="absolute top-4 right-4 h-16 w-16 text-green-500 opacity-20 z-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div class="relative bg-white p-6 rounded-2xl shadow-sm overflow-hidden border border-gray-200 transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1">
            <div class="relative z-10">
                <h3 class="text-sm font-medium text-gray-500">Menunggu Penilaian</h3>
                <p class="mt-1 text-3xl font-bold text-amber-600"><?php echo $laporan_pending; ?></p>
            </div>
             <svg class="absolute top-4 right-4 h-16 w-16 text-amber-500 opacity-20 z-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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
            <form action="laporan.php" method="GET" id="filter-form">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div class="lg:col-span-2">
                        <label for="mahasiswa" class="block text-sm font-medium text-gray-700 mb-1">Cari Mahasiswa</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/></svg>
                            </div>
                            <input type="text" id="mahasiswa" name="mahasiswa" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:ring-purple-500 focus:border-purple-500 text-sm" placeholder="Nama atau NIM..." value="<?php echo htmlspecialchars($filter_mahasiswa); ?>">
                        </div>
                    </div>
                    <div>
                        <label for="praktikum_id" class="block text-sm font-medium text-gray-700 mb-1">Praktikum</label>
                        <select name="praktikum_id" id="praktikum_id" class="border border-gray-300 text-gray-700 rounded-lg focus:ring-purple-500 focus:border-purple-500 block w-full p-2 text-sm" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            <?php mysqli_data_seek($praktikum_options, 0); while($p = $praktikum_options->fetch_assoc()): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($filter_praktikum_id == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['nama_praktikum']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                     <div>
                        <label for="modul_id" class="block text-sm font-medium text-gray-700 mb-1">Modul</label>
                        <select name="modul_id" id="modul_id" class="border border-gray-300 text-gray-700 rounded-lg focus:ring-purple-500 focus:border-purple-500 block w-full p-2 text-sm" <?php echo !$modul_options ? 'disabled' : ''; ?>>
                            <option value="">Semua</option>
                            <?php if ($modul_options) { while($m = $modul_options->fetch_assoc()): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($filter_modul_id == $m['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['nama_modul']); ?>
                                </option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="border border-gray-300 text-gray-700 rounded-lg focus:ring-purple-500 focus:border-purple-500 block w-full p-2 text-sm">
                            <option value="">Semua</option>
                            <option value="belum_dinilai" <?php echo ($filter_status == 'belum_dinilai') ? 'selected' : ''; ?>>Belum Dinilai</option>
                            <option value="dinilai" <?php echo ($filter_status == 'dinilai') ? 'selected' : ''; ?>>Sudah Dinilai</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 mt-4">
                    <a href="laporan.php" class="text-sm font-medium text-gray-600 hover:text-purple-600">Reset Filter</a>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white font-semibold text-sm rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Terapkan
                    </button>
                </div>
            </form>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Mahasiswa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail Laporan</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($laporan_list->num_rows > 0): ?>
                        <?php while($row = $laporan_list->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                            <span class="font-bold text-sm text-purple-700"><?php echo getInitials($row['nama_mahasiswa']); ?></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama_mahasiswa']); ?></div>
                                            <div class="text-xs text-gray-500">Tgl: <?php echo date('d M Y, H:i', strtotime($row['submitted_at'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($row['nama_praktikum']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['nama_modul']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php if ($row['nilai'] !== null): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                            Sudah Dinilai
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Belum Dinilai
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-4">
                                        <button onclick='openGradeModal(<?php echo json_encode($row, JSON_HEX_APOS); ?>)' class="text-purple-600 hover:text-purple-800" title="Beri Nilai">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" /><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" /></svg>
                                        </button>
                                        <a href="download.php?type=laporan&id=<?php echo $row['id']; ?>" class="text-gray-400 hover:text-gray-600" title="Unduh Laporan">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v4.59L7.3 9.7a.75.75 0 00-1.1 1.02l3.25 3.5a.75.75 0 001.1 0l3.25-3.5a.75.75 0 10-1.1-1.02l-1.95 2.1V6.75z" clip-rule="evenodd" /></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">Tidak Ada Laporan</h3>
                                <p class="mt-1 text-sm text-gray-500">Tidak ada laporan yang sesuai dengan filter yang Anda terapkan.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200">
            <?php
                $query_params = $_GET; unset($query_params['page']); $query_string = http_build_query($query_params);
            ?>
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="text-sm text-gray-700 mb-4 md:mb-0">
                    Halaman <span class="font-bold text-gray-900"><?php echo $page; ?></span> dari <span class="font-bold text-gray-900"><?php echo $total_pages; ?></span>
                </div>
                <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <a href="<?php echo $page > 1 ? '?page='.($page-1).'&'.$query_string : '#'; ?>" class="<?php echo $page <= 1 ? 'pointer-events-none bg-gray-100 text-gray-400' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium">
                        <span class="sr-only">Previous</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo $query_string; ?>" class="<?php echo $page == $i ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="<?php echo $page < $total_pages ? '?page='.($page+1).'&'.$query_string : '#'; ?>" class="<?php echo $page >= $total_pages ? 'pointer-events-none bg-gray-100 text-gray-400' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium">
                        <span class="sr-only">Next</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    </a>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="grade-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end sm:items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeGradeModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <form action="laporan.php" method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" /><path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" /></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Form Penilaian Laporan</h3>
                            <div class="mt-4 space-y-6">
                                <input type="hidden" name="laporan_id" id="laporan-id">
                                
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                  <dt class="font-medium text-gray-500">Mahasiswa</dt>
                                  <dd class="text-gray-900 font-semibold" id="grade-mahasiswa"></dd>
                                  <dt class="font-medium text-gray-500">Modul</dt>
                                  <dd class="text-gray-900" id="grade-modul"></dd>
                                  <dt class="font-medium text-gray-500">File Laporan</dt>
                                  <dd><a id="grade-file-link" href="#" target="_blank" class="font-medium text-purple-600 hover:text-purple-800 hover:underline">Unduh & Lihat File</a></dd>
                                </dl>

                                <div class="border-t border-gray-200 pt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-2">
                                        <label for="grade-nilai" class="block text-sm font-medium text-gray-700">Nilai (0-100)</label>
                                        <input type="number" step="0.01" min="0" max="100" id="grade-nilai" name="nilai" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label for="grade-feedback" class="block text-sm font-medium text-gray-700">Feedback (Opsional)</label>
                                        <textarea id="grade-feedback" name="feedback" rows="3" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" placeholder="Berikan masukan atau koreksi untuk mahasiswa..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="simpan_nilai" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Simpan Penilaian
                    </button>
                    <button type="button" onclick="closeGradeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const gradeModal = document.getElementById('grade-modal');

    function openGradeModal(data) {
        document.getElementById('laporan-id').value = data.id;
        document.getElementById('grade-mahasiswa').textContent = data.nama_mahasiswa;
        document.getElementById('grade-modul').textContent = `${data.nama_praktikum} - ${data.nama_modul}`;
        document.getElementById('grade-nilai').value = data.nilai || '';
        document.getElementById('grade-feedback').value = data.feedback || '';
        document.getElementById('grade-file-link').href = `download.php?type=laporan&id=${data.id}`;
        gradeModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeGradeModal() {
        gradeModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
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