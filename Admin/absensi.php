<?php
session_start();
include '../lng/koneksi.php';

// Check admin session
$role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($role, ['admin', 'superadmin'])) {
    header('Location: ../lng/lgn.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
$admin_nama = $_SESSION['nama'] ?? 'Admin';
$msg = '';
$msg_type = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Status Absensi
    if (isset($_POST['action']) && $_POST['action'] === 'update_absensi') {
        $absen_id = intval($_POST['absen_id']);
        $new_status = $_POST['new_status'];
        $allowed = ['hadir','izin','sakit','alpha'];
        if (in_array($new_status, $allowed)) {
            $stmt = $conn->prepare("UPDATE absensi SET status=? WHERE id=?");
            $stmt->bind_param("si", $new_status, $absen_id);
            if ($stmt->execute()) { $msg = 'Status absensi berhasil diubah!'; $msg_type = 'success'; }
            else { $msg = 'Gagal mengubah status.'; $msg_type = 'error'; }
            $stmt->close();
        }
    }

    // Hapus Absensi
    if (isset($_POST['action']) && $_POST['action'] === 'delete_absensi') {
        $absen_id = intval($_POST['absen_id']);
        if ($absen_id > 0) {
            $stmt = $conn->prepare("DELETE FROM absensi WHERE id=?");
            $stmt->bind_param("i", $absen_id);
            if ($stmt->execute()) { $msg = 'Data absensi berhasil dihapus!'; $msg_type = 'success'; }
            else { $msg = 'Gagal menghapus data.'; $msg_type = 'error'; }
            $stmt->close();
        }
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ../lng/lgn.php');
        exit;
    }
}

// Fetch absensi with filters
$filter_tanggal = $_GET['tanggal'] ?? '';
$filter_nama = $_GET['nama'] ?? '';
$where = "1=1";
if ($filter_tanggal) $where .= " AND a.tanggal = '" . $conn->real_escape_string($filter_tanggal) . "'";
if ($filter_nama) $where .= " AND u.nama LIKE '%" . $conn->real_escape_string($filter_nama) . "%'";
$absensi_list = [];
$q = $conn->query("SELECT a.*, u.nama as user_nama FROM absensi a JOIN users u ON a.user_id = u.id WHERE $where ORDER BY a.tanggal DESC, a.waktu DESC LIMIT 50");
if ($q) { while ($r = $q->fetch_assoc()) $absensi_list[] = $r; }
$foto_profil = '';
$fp = $conn->query("SELECT foto_profil FROM users WHERE id = $admin_id");
if ($fp && $r = $fp->fetch_assoc()) $foto_profil = $r['foto_profil'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Presensi</title>
    <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-item { transition: all 0.2s ease; border-right: 4px solid transparent; }
        .sidebar-item.active { background-color: var(--color-rohis-50) !important; color: var(--color-rohis-700) !important; font-weight: 700; border-right-color: var(--color-rohis-600); }
        .sidebar-item.active .icon { color: var(--color-rohis-700) !important; }
        .sidebar-item:not(.active):hover { background-color: #f1f5f9; color: #0f172a; }

        /* Fallback for missing Tailwind classes */
        .left-3 { left: 1rem !important; }
        .left-4 { left: 1.25rem !important; }
        .pl-9 { padding-left: 2.75rem !important; }
        .pl-11 { padding-left: 3.25rem !important; }
        .logo-gradient { background: linear-gradient(135deg, var(--color-rohis-500), var(--color-rohis-700)); color: white; }
        .btn-gradient { background: linear-gradient(to right, var(--color-rohis-600), var(--color-rohis-700)); color: white; }
        .btn-gradient:hover { background: linear-gradient(to right, var(--color-rohis-700), var(--color-rohis-800)); }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-blue-500 selection:text-white">
    <div class="flex min-h-screen">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col fixed h-full z-20 shadow-sm">
            <!-- Logo Area -->
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 logo-gradient rounded-xl flex items-center justify-center shadow-md shadow-blue-500/20">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 tracking-tight leading-tight">Admin</h1>
                        <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Sistem Presensi</p>
                    </div>
                </div>
            </div>

            <!-- User Info Area -->
            <div class="px-6 py-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <?php if (!empty($foto_profil)): ?>
                        <img src="<?= htmlspecialchars($foto_profil) ?>" class="rounded-full border-2 border-white shadow-sm object-cover" style="width:44px;height:44px;min-width:44px;">
                    <?php else: ?>
                        <div class="rounded-full bg-blue-100 border-2 border-white shadow-sm flex items-center justify-center text-blue-600 font-bold text-sm" style="width:44px;height:44px;min-width:44px;">
                            <?= strtoupper(substr($admin_nama, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0 overflow-hidden">
                        <p class="text-sm font-bold text-slate-800 truncate" title="<?= htmlspecialchars($admin_nama) ?>"><?= htmlspecialchars($admin_nama) ?></p>
                        <p class="text-xs text-blue-600 font-medium capitalize truncate"><?= htmlspecialchars($role) ?></p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <p class="px-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Menu Utama</p>
                
                <a href="Home.php" class="sidebar-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="map-pin" class="icon w-5 h-5 text-slate-400"></i>
                    Pengaturan Lokasi
                </a>
                
                <a href="absensi.php" class="sidebar-item active w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="clipboard-list" class="icon w-5 h-5 text-slate-400"></i>
                    Edit Data Presensi
                </a>
                
                <a href="akun.php" class="sidebar-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="user-cog" class="icon w-5 h-5 text-slate-400"></i>
                    Kelola Akun
                </a>
            </nav>

            <!-- Bottom Area / Logout -->
            <div class="p-4 border-t border-slate-100">
                <form method="POST" class="logout-form">
                    <input type="hidden" name="logout" value="1">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-sm font-bold transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Keluar Sistem
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <main class="flex-1 ml-64 p-8">
            
            <!-- Header section -->
            <header class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Edit Data Presensi</h2>
                    <p class="text-sm text-slate-500 mt-1">Kelola status presensi dan lihat foto bukti kehadiran siswa.</p>
                </div>
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm">
                    <i data-lucide="clock" class="w-4 h-4 text-blue-500"></i>
                    <span class="text-sm font-medium text-slate-600"><?= date('d M Y') ?></span>
                </div>
            </header>

            <!-- Messages -->
            <?php if ($msg): ?>
            <div class="mb-6 p-4 rounded-2xl border flex items-start gap-3 <?= $msg_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> text-sm font-medium animate-in fade-in slide-in-from-top-4 duration-300">
                <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <p><?= htmlspecialchars($msg) ?></p>
            </div>
            <?php endif; ?>

            <!-- Section: Edit Data Presensi -->
            <div>
                <!-- Filter -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 mb-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-bl-full -z-0"></div>
                    <form method="GET" class="relative z-10 grid grid-cols-1 sm:grid-cols-12 gap-5 items-end">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tanggal Presensi</label>
                            <div class="relative">
                                <i data-lucide="calendar" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-500"></i>
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($filter_tanggal) ?>" class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-slate-700">
                            </div>
                        </div>
                        <div class="sm:col-span-5">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Pencarian Siswa</label>
                            <div class="relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-500"></i>
                                <input type="text" name="nama" value="<?= htmlspecialchars($filter_nama) ?>" placeholder="Ketik nama siswa..." class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-slate-700">
                            </div>
                        </div>
                        <div class="sm:col-span-4 flex gap-3">
                            <button type="submit" class="flex-1 px-6 py-3 btn-gradient text-white rounded-xl text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="search" class="w-4 h-4"></i> Cari
                            </button>
                            <a href="absensi.php" class="flex-1 px-6 py-3 bg-slate-50 border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-xl text-sm font-bold transition-all text-center flex items-center justify-center gap-2">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 text-xs uppercase tracking-wider font-bold">
                                <tr>
                                    <th class="px-6 py-4">Nama Siswa</th>
                                    <th class="px-6 py-4">Waktu</th>
                                    <th class="px-6 py-4">Bukti Foto</th>
                                    <th class="px-6 py-4">Status &amp; Tindakan</th>
                                    <th class="px-6 py-4 text-center">Hapus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($absensi_list)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-400">
                                            <i data-lucide="inbox" class="w-10 h-10 mb-3 text-slate-300"></i>
                                            <p class="font-medium">Tidak ada data absensi ditemukan.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: foreach ($absensi_list as $a):
                                    $sc = [
                                        'hadir'=>'bg-green-100 text-green-700 border-green-200',
                                        'izin'=>'bg-yellow-100 text-yellow-700 border-yellow-200',
                                        'sakit'=>'bg-blue-100 text-blue-700 border-blue-200',
                                        'alpha'=>'bg-red-100 text-red-700 border-red-200'
                                    ];
                                    $badge = $sc[$a['status']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-800"><?= htmlspecialchars($a['user_nama']) ?></p>
                                        <p class="text-[11px] text-slate-400 mt-0.5 uppercase tracking-wider">ID: #<?= $a['id'] ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-700"><?= $a['waktu'] ? date('H:i', strtotime($a['waktu'])) : '-' ?></p>
                                        <p class="text-xs text-slate-500 mt-0.5"><?= date('d M Y', strtotime($a['tanggal'])) ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($a['foto_base64'])): ?>
                                            <button type="button" onclick="openPhotoModal('foto_<?= $a['id'] ?>')" class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-600 rounded-lg text-xs font-bold transition-colors">
                                                <i data-lucide="image" class="w-4 h-4"></i> Lihat Foto
                                            </button>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-200 text-slate-400 rounded-lg text-xs font-medium">
                                                <i data-lucide="image-off" class="w-4 h-4"></i> Tanpa Foto
                                            </span>
                                        <?php endif; ?>
                                        <textarea id="foto_<?= $a['id'] ?>" class="hidden"><?= htmlspecialchars($a['foto_base64'] ?? '') ?></textarea>
                                    </td>
                                    <td class="px-6 py-4">
                                        <form method="POST" class="flex items-center gap-2 m-0">
                                            <input type="hidden" name="action" value="update_absensi">
                                            <input type="hidden" name="absen_id" value="<?= $a['id'] ?>">
                                            <select name="new_status" class="px-2 py-1.5 rounded-lg text-xs font-bold border focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all cursor-pointer <?= $badge ?>">
                                                <option value="hadir" <?= $a['status']==='hadir'?'selected':'' ?>>Hadir</option>
                                                <option value="izin" <?= $a['status']==='izin'?'selected':'' ?>>Izin</option>
                                                <option value="sakit" <?= $a['status']==='sakit'?'selected':'' ?>>Sakit</option>
                                                <option value="alpha" <?= $a['status']==='alpha'?'selected':'' ?>>Alpha</option>
                                            </select>
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-600 rounded-lg text-xs font-bold transition-colors shadow-sm" title="Simpan Status">
                                                <i data-lucide="save" class="w-4 h-4"></i> Simpan
                                            </button>
                                        </form>
                                    </td>
                                    <!-- Kolom Hapus -->
                                    <td class="px-4 py-4 text-center">
                                        <form method="POST" class="m-0" onsubmit="return confirmHapus(event, '<?= htmlspecialchars($a['user_nama']) ?>', '<?= date('d M Y', strtotime($a['tanggal'])) ?>')">
                                            <input type="hidden" name="action" value="delete_absensi">
                                            <input type="hidden" name="absen_id" value="<?= $a['id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 rounded-lg text-xs font-bold transition-colors shadow-sm" title="Hapus Absensi">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <footer class="mt-12 text-center text-xs font-medium text-slate-400">
                &copy; <?= date('Y') ?> Sistem Presensi. All rights reserved.
            </footer>
        </main>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div id="confirmHapusModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4" style="background:rgba(15,23,42,0.5);backdrop-filter:blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm border border-slate-100 overflow-hidden">
            <div class="p-6 text-center">
                <div class="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="trash-2" class="w-7 h-7 text-red-500"></i>
                </div>
                <h3 class="text-lg font-extrabold text-slate-800 mb-1">Hapus Presensi?</h3>
                <p class="text-sm text-slate-500 mb-1">Data presensi berikut akan dihapus permanen:</p>
                <div class="mt-3 px-4 py-3 bg-red-50 rounded-xl border border-red-100 text-left">
                    <p class="text-sm font-bold text-slate-700"><i data-lucide="user" class="w-3.5 h-3.5 inline mr-1 text-slate-400"></i><span id="hapusNama"></span></p>
                    <p class="text-xs text-slate-500 mt-1"><i data-lucide="calendar" class="w-3.5 h-3.5 inline mr-1 text-slate-400"></i><span id="hapusTanggal"></span></p>
                </div>
                <p class="text-xs text-red-500 font-medium mt-3">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="flex border-t border-slate-100">
                <button onclick="batalHapus()" class="flex-1 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                    Batal
                </button>
                <div class="w-px bg-slate-100"></div>
                <button onclick="lanjutHapus()" class="flex-1 py-3 text-sm font-bold text-red-600 hover:bg-red-50 transition-colors">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Foto (Fullscreen Lightbox) -->
    <div id="photoModal" class="fixed inset-0 z-50 hidden flex flex-col" style="background:#f8fafc;">
        <!-- Top Bar -->
        <div class="flex items-center justify-between px-5 py-3 shrink-0 bg-white border-b border-slate-200 shadow-sm">
            <div class="flex items-center gap-2 text-slate-800">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="camera" class="w-4 h-4"></i>
                </div>
                <h3 class="text-sm font-bold">Bukti Kehadiran</h3>
            </div>
            <button type="button" onclick="closePhotoModal()"
                class="w-9 h-9 flex items-center justify-center rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all"
                title="Tutup">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Photo Area — fills remaining space -->
        <div id="noPhotoMsg" class="hidden flex-1 flex-col items-center justify-center text-slate-400 text-sm">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                <i data-lucide="image-off" class="w-8 h-8 text-slate-300"></i>
            </div>
            <span class="font-medium">Bukti foto tidak dilampirkan.</span>
        </div>

        <div id="zoomWrapper" class="hidden flex-1 select-none overflow-hidden" style="cursor:zoom-in;min-height:0;background:#f1f5f9;">
            <img id="modalImage" src="" alt="Foto Absen"
                style="width:100%;height:100%;object-fit:contain;transform-origin:center center;transform:scale(1);transition:transform 0.15s ease;will-change:transform;">
        </div>

        <!-- Bottom Zoom Controls -->
        <div id="zoomIndicator" class="hidden shrink-0 py-3 flex items-center justify-center gap-3 bg-white border-t border-slate-200">
            <button onclick="zoomOut()" title="Zoom out"
                class="w-9 h-9 rounded-full flex items-center justify-center text-slate-600 font-bold text-lg hover:bg-slate-100 transition-all leading-none border border-slate-200">&#8722;</button>
            <span id="zoomLabel" class="text-xs font-bold text-slate-500 w-12 text-center">100%</span>
            <button onclick="zoomIn()" title="Zoom in"
                class="w-9 h-9 rounded-full flex items-center justify-center text-slate-600 font-bold text-lg hover:bg-slate-100 transition-all leading-none border border-slate-200">&#43;</button>
        </div>
    </div>

    <script>
    lucide.createIcons();

    // ---- Konfirmasi Hapus ----
    var _hapusForm = null;
    function confirmHapus(e, nama, tanggal) {
        e.preventDefault();
        _hapusForm = e.target;
        document.getElementById('hapusNama').textContent = nama;
        document.getElementById('hapusTanggal').textContent = tanggal;
        document.getElementById('confirmHapusModal').classList.remove('hidden');
        return false;
    }
    function batalHapus() {
        document.getElementById('confirmHapusModal').classList.add('hidden');
        _hapusForm = null;
    }
    function lanjutHapus() {
        document.getElementById('confirmHapusModal').classList.add('hidden');
        if (_hapusForm) _hapusForm.submit();
    }

    // ---- Zoom State ----
    let _zoomScale = 1;
    const ZOOM_MIN = 1, ZOOM_MAX = 4, ZOOM_STEP = 0.35;

    function _applyZoom(scale, smooth) {
        if (smooth === undefined) smooth = true;
        _zoomScale = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, scale));
        const img  = document.getElementById('modalImage');
        const wrap = document.getElementById('zoomWrapper');
        img.style.transition = smooth ? 'transform 0.15s ease' : 'none';
        img.style.transform  = 'scale(' + _zoomScale + ')';
        var cur = _zoomScale >= ZOOM_MAX ? 'zoom-out' : 'zoom-in';
        img.style.cursor  = cur;
        wrap.style.cursor = cur;
        document.getElementById('zoomLabel').textContent = Math.round(_zoomScale * 100) + '%';
    }

    function zoomIn()  { _applyZoom(_zoomScale + ZOOM_STEP); }
    function zoomOut() { _applyZoom(_zoomScale - ZOOM_STEP); }

    // ---- Modal Open/Close ----
    function openPhotoModal(id) {
        var base64Data = document.getElementById(id).value;
        var imgEl      = document.getElementById('modalImage');
        var noPhotoEl  = document.getElementById('noPhotoMsg');
        var wrapper    = document.getElementById('zoomWrapper');
        var indicator  = document.getElementById('zoomIndicator');
        var modal      = document.getElementById('photoModal');

        _applyZoom(1, false);

        if (base64Data && base64Data.trim() !== '') {
            imgEl.src = base64Data;
            wrapper.classList.remove('hidden');
            wrapper.classList.add('flex');
            noPhotoEl.classList.add('hidden');
            noPhotoEl.classList.remove('flex');
            indicator.classList.remove('hidden');
            indicator.classList.add('flex');
        } else {
            wrapper.classList.add('hidden');
            wrapper.classList.remove('flex');
            indicator.classList.add('hidden');
            indicator.classList.remove('flex');
            noPhotoEl.classList.remove('hidden');
            noPhotoEl.classList.add('flex');
        }

        modal.style.opacity = '0';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(function() { modal.style.transition = 'opacity 0.2s ease'; modal.style.opacity = '1'; }, 10);
    }

    function closePhotoModal() {
        var modal = document.getElementById('photoModal');
        modal.style.opacity = '0';
        setTimeout(function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.style.transition = '';
            _applyZoom(1, false);
        }, 200);
    }

    // ---- Scroll Wheel Zoom ----
    document.getElementById('zoomWrapper').addEventListener('wheel', function(e) {
        e.preventDefault();
        _applyZoom(_zoomScale + (e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP));
    }, { passive: false });

    // ---- Double-click Toggle Zoom ----
    document.getElementById('zoomWrapper').addEventListener('dblclick', function() {
        _applyZoom(_zoomScale > 1 ? 1 : 2);
    });

    // ---- Pinch-to-Zoom (Touch) ----
    var _lastPinchDist = null;
    document.getElementById('zoomWrapper').addEventListener('touchstart', function(e) {
        if (e.touches.length === 2) {
            _lastPinchDist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
        }
    }, { passive: true });

    document.getElementById('zoomWrapper').addEventListener('touchmove', function(e) {
        if (e.touches.length === 2 && _lastPinchDist !== null) {
            e.preventDefault();
            var dist = Math.hypot(
                e.touches[0].clientX - e.touches[1].clientX,
                e.touches[0].clientY - e.touches[1].clientY
            );
            _applyZoom(_zoomScale * (dist / _lastPinchDist), false);
            _lastPinchDist = dist;
        }
    }, { passive: false });

    document.getElementById('zoomWrapper').addEventListener('touchend', function() {
        _lastPinchDist = null;
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.logout-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi Keluar',
                    text: "Apakah Anda yakin ingin keluar dari sistem?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Keluar!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>
