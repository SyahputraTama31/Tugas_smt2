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

// Auto-create foto_profil column if not exists
$col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'foto_profil'");
if ($col_check->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN foto_profil LONGTEXT NULL");
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Foto Profil
    if (isset($_POST['action']) && $_POST['action'] === 'update_foto') {
        $foto_data = $_POST['foto_base64'] ?? '';
        if (!empty($foto_data)) {
            $stmt = $conn->prepare("UPDATE users SET foto_profil=? WHERE id=?");
            $stmt->bind_param("si", $foto_data, $admin_id);
            if ($stmt->execute()) {
                $msg = 'Foto profil berhasil diperbarui!';
                $msg_type = 'success';
            } else {
                $msg = 'Gagal memperbarui foto profil.';
                $msg_type = 'error';
            }
            $stmt->close();
        }
    }

    // Hapus Foto Profil
    if (isset($_POST['action']) && $_POST['action'] === 'hapus_foto') {
        $empty = '';
        $stmt = $conn->prepare("UPDATE users SET foto_profil=? WHERE id=?");
        $stmt->bind_param("si", $empty, $admin_id);
        if ($stmt->execute()) {
            $msg = 'Foto profil berhasil dihapus!';
            $msg_type = 'success';
        } else {
            $msg = 'Gagal menghapus foto profil.';
            $msg_type = 'error';
        }
        $stmt->close();
    }

    // Update Akun Admin
    if (isset($_POST['action']) && $_POST['action'] === 'update_akun') {
        $nama = $_POST['nama'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET nama=?, username=?, password=? WHERE id=?");
            $stmt->bind_param("sssi", $nama, $username, $hashed, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama=?, username=? WHERE id=?");
            $stmt->bind_param("ssi", $nama, $username, $admin_id);
        }

        if ($stmt->execute()) {
            $_SESSION['nama'] = $nama;
            $admin_nama = $nama;
            $msg = 'Profil akun berhasil diperbarui!';
            $msg_type = 'success';
        } else {
            $msg = 'Gagal memperbarui profil.';
            $msg_type = 'error';
        }
        $stmt->close();
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ../lng/lgn.php');
        exit;
    }
}

// Fetch Admin Data
$admin_data = $conn->query("SELECT nama, username, foto_profil FROM users WHERE id = $admin_id")->fetch_assoc();
$foto_profil = $admin_data['foto_profil'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Absensi</title>
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
        .pl-11 { padding-left: 3.25rem !important; }
        .logo-gradient { background: linear-gradient(135deg, var(--color-rohis-500), var(--color-rohis-700)); color: white; }
        .btn-gradient { background: linear-gradient(to right, var(--color-rohis-600), var(--color-rohis-700)); color: white; }
        .btn-gradient:hover { background: linear-gradient(to right, var(--color-rohis-700), var(--color-rohis-800)); }

        /* Profile photo */
        .profile-avatar { position: relative; cursor: pointer; transition: transform 0.2s ease; }
        .profile-avatar:hover { transform: scale(1.05); }
        .profile-avatar .overlay {
            position: absolute; inset: 0; background: rgba(0,0,0,0.45); display: flex;
            align-items: center; justify-content: center; border-radius: 1rem;
            opacity: 0; transition: opacity 0.2s ease;
        }
        .profile-avatar:hover .overlay { opacity: 1; }

        /* Camera modal */
        #cameraVideo { transform: scaleX(-1); }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-blue-500 selection:text-white">
    <div class="flex min-h-screen">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col fixed h-full z-20 shadow-sm">
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 logo-gradient rounded-xl flex items-center justify-center shadow-md shadow-blue-500/20">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 tracking-tight leading-tight">Admin</h1>
                        <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Sistem Absensi</p>
                    </div>
                </div>
            </div>

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

            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <p class="px-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Menu Utama</p>
                <a href="Home.php" class="sidebar-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="map-pin" class="icon w-5 h-5 text-slate-400"></i>
                    Pengaturan Lokasi
                </a>
                <a href="absensi.php" class="sidebar-item w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="clipboard-list" class="icon w-5 h-5 text-slate-400"></i>
                    Edit Data Presensi
                </a>
                <a href="akun.php" class="sidebar-item active w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="user-cog" class="icon w-5 h-5 text-slate-400"></i>
                    Kelola Akun
                </a>
            </nav>

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

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <header class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Kelola Akun</h2>
                    <p class="text-sm text-slate-500 mt-1">Perbarui profil dan kata sandi Anda di sini.</p>
                </div>
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm">
                    <i data-lucide="clock" class="w-4 h-4 text-blue-500"></i>
                    <span class="text-sm font-medium text-slate-600"><?= date('d M Y') ?></span>
                </div>
            </header>

            <!-- Messages -->
            <?php if ($msg): ?>
            <div class="mb-6 p-4 rounded-2xl border flex items-start gap-3 <?= $msg_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> text-sm font-medium">
                <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <p><?= htmlspecialchars($msg) ?></p>
            </div>
            <?php endif; ?>

            <!-- Kelola Akun -->
            <div class="max-w-3xl">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden relative">
                    <div class="h-32 bg-rohis-600"></div>
                    <div class="p-8 pt-0 relative">

                        <!-- Profile Photo -->
                        <div class="flex items-end gap-5 -mt-12 mb-8">
                            <div class="profile-avatar rounded-2xl border-4 border-white shadow-lg overflow-hidden flex-shrink-0" style="width:110px;height:110px;min-width:110px;" onclick="showPhotoOptions()">
                                <?php if (!empty($foto_profil)): ?>
                                    <img id="avatarPreview" src="<?= htmlspecialchars($foto_profil) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div id="avatarPreview" class="w-full h-full flex items-center justify-center text-blue-600 text-4xl font-black" style="background: linear-gradient(135deg, #eff6ff, #dbeafe);">
                                        <?= strtoupper(substr($admin_data['nama'] ?? 'A', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="overlay">
                                    <i data-lucide="camera" class="w-7 h-7 text-white"></i>
                                </div>
                            </div>
                            <div class="pb-2">
                                <h3 class="text-xl font-extrabold text-slate-800"><?= htmlspecialchars($admin_data['nama'] ?? 'Admin') ?></h3>
                                <p class="text-sm text-slate-500 font-medium mt-1">Klik foto untuk mengubah</p>
                            </div>
                        </div>
                        
                        <!-- Form Data Akun -->
                        <form method="POST" class="space-y-5">
                            <input type="hidden" name="action" value="update_akun">
                            
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Lengkap</label>
                                <div class="relative">
                                    <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                    <input type="text" name="nama" value="<?= htmlspecialchars($admin_data['nama'] ?? '') ?>" required
                                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                </div>
                            </div>
                            
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Username</label>
                                <div class="relative">
                                    <i data-lucide="at-sign" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                    <input type="text" name="username" value="<?= htmlspecialchars($admin_data['username'] ?? '') ?>" required
                                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                </div>
                            </div>
                            
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Password Baru</label>
                                <div class="relative">
                                    <i data-lucide="lock" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah"
                                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                                </div>
                            </div>
                            
                            <div class="pt-4 mt-6 border-t border-slate-100 flex justify-end">
                                <button type="submit" class="px-6 py-3 btn-gradient rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center gap-2 font-bold text-sm">
                                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <footer class="mt-12 text-center text-xs font-medium text-slate-400">
                &copy; <?= date('Y') ?> Sistem Absensi. All rights reserved.
            </footer>
        </main>
    </div>

    <!-- Photo Options Modal -->
    <div id="photoOptionsModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(15,23,42,0.4);backdrop-filter:blur(4px);">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-slate-100" id="photoOptionsContent" style="transform:scale(0.95);transition:transform 0.2s ease;">
            <div class="flex justify-between items-center p-5 border-b border-slate-100">
                <div class="flex items-center gap-2 text-slate-800">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold">Ubah Foto Profil</h3>
                </div>
                <button type="button" onclick="closePhotoOptions()" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-5 space-y-3">
                <button type="button" onclick="pickFromGallery()" class="w-full flex items-center gap-4 p-4 rounded-2xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                        <i data-lucide="image" class="w-6 h-6"></i>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-sm text-slate-800">Pilih dari Galeri</p>
                        <p class="text-xs text-slate-500">Unggah foto dari perangkat Anda</p>
                    </div>
                </button>
                <button type="button" onclick="openCamera()" class="w-full flex items-center gap-4 p-4 rounded-2xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50/50 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-green-50 text-green-600 flex items-center justify-center group-hover:bg-green-100 transition-colors">
                        <i data-lucide="user" class="w-6 h-6"></i>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-sm text-slate-800">Ambil Selfie</p>
                        <p class="text-xs text-slate-500">Gunakan kamera untuk foto langsung</p>
                    </div>
                </button>
                <?php if (!empty($foto_profil)): ?>
                <button type="button" onclick="deletePhoto()" class="w-full flex items-center gap-4 p-4 rounded-2xl border border-red-200 hover:border-red-300 hover:bg-red-50/50 transition-all group">
                    <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center group-hover:bg-red-100 transition-colors">
                        <i data-lucide="trash-2" class="w-6 h-6"></i>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-sm text-red-600">Hapus Foto</p>
                        <p class="text-xs text-slate-500">Kembali ke avatar default</p>
                    </div>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Camera Modal -->
    <div id="cameraModal" class="fixed inset-0 z-[60] hidden items-center justify-center p-4" style="background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100">
            <div class="flex justify-between items-center p-5 border-b border-slate-100">
                <div class="flex items-center gap-2 text-slate-800">
                    <div class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                    </div>
                    <h3 class="text-base font-bold">Ambil Selfie</h3>
                </div>
                <button type="button" onclick="closeCamera()" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-5">
                <div class="rounded-2xl overflow-hidden bg-black mb-4 relative" style="aspect-ratio:1/1;">
                    <video id="cameraVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                    <canvas id="cameraCanvas" class="hidden"></canvas>
                </div>
                <button type="button" onclick="capturePhoto()" class="w-full py-3 btn-gradient rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-md shadow-blue-500/20">
                    <i data-lucide="camera" class="w-5 h-5"></i> Ambil Foto
                </button>
            </div>
        </div>
    </div>

    <input type="file" id="fileInput" accept="image/*" class="hidden" onchange="handleFileSelect(event)">
    <form id="photoForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="update_foto">
        <input type="hidden" name="foto_base64" id="fotoBase64">
    </form>
    <form id="deletePhotoForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="hapus_foto">
    </form>

    <script>
    lucide.createIcons();

    let cameraStream = null;

    function deletePhoto() {
        if (confirm('Apakah Anda yakin ingin menghapus foto profil?')) {
            closePhotoOptions();
            document.getElementById('deletePhotoForm').submit();
        }
    }

    function showPhotoOptions() {
        const modal = document.getElementById('photoOptionsModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        setTimeout(() => {
            document.getElementById('photoOptionsContent').style.transform = 'scale(1)';
        }, 10);
    }

    function closePhotoOptions() {
        document.getElementById('photoOptionsContent').style.transform = 'scale(0.95)';
        setTimeout(() => {
            const modal = document.getElementById('photoOptionsModal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }, 200);
    }

    function pickFromGallery() {
        closePhotoOptions();
        setTimeout(() => document.getElementById('fileInput').click(), 250);
    }

    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
            submitPhoto(ev.target.result);
        };
        reader.readAsDataURL(file);
    }

    function openCamera() {
        closePhotoOptions();
        setTimeout(async () => {
            const modal = document.getElementById('cameraModal');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 640 } },
                    audio: false
                });
                document.getElementById('cameraVideo').srcObject = cameraStream;
            } catch (err) {
                alert('Tidak dapat mengakses kamera. Pastikan izin kamera sudah diberikan.');
                closeCamera();
            }
        }, 250);
    }

    function capturePhoto() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const size = Math.min(video.videoWidth, video.videoHeight);
        canvas.width = 400;
        canvas.height = 400;
        const ctx = canvas.getContext('2d');
        ctx.translate(400, 0);
        ctx.scale(-1, 1);
        const sx = (video.videoWidth - size) / 2;
        const sy = (video.videoHeight - size) / 2;
        ctx.drawImage(video, sx, sy, size, size, 0, 0, 400, 400);
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        closeCamera();
        submitPhoto(dataUrl);
    }

    function closeCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(t => t.stop());
            cameraStream = null;
        }
        const modal = document.getElementById('cameraModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function submitPhoto(base64) {
        document.getElementById('fotoBase64').value = base64;
        document.getElementById('photoForm').submit();
    }
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
