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
    // 1. Update Lokasi
    if (isset($_POST['action']) && $_POST['action'] === 'update_lokasi') {
        $lat = floatval($_POST['lat_admin'] ?? 0);
        $lng = floatval($_POST['lng_admin'] ?? 0);
        $radius = floatval($_POST['radius_km'] ?? 0.5);
        $jam_mulai = $_POST['jam_mulai'] ?? '07:00:00';
        $jam_selesai = $_POST['jam_selesai'] ?? '09:00:00';
        $stmt = $conn->prepare("UPDATE pengaturan_absen SET lat_admin=?, lng_admin=?, radius_km=?, jam_mulai=?, jam_selesai=? WHERE id=1");
        $stmt->bind_param("dddss", $lat, $lng, $radius, $jam_mulai, $jam_selesai);
        if ($stmt->execute()) { $msg = 'Pengaturan absensi berhasil diperbarui!'; $msg_type = 'success'; }
        else { $msg = 'Gagal memperbarui pengaturan.'; $msg_type = 'error'; }
        $stmt->close();
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ../lng/lgn.php');
        exit;
    }
}

// Fetch data
$pengaturan = $conn->query("SELECT * FROM pengaturan_absen WHERE id=1")->fetch_assoc();
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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        #map { height: 400px; z-index: 1; border-radius: 0.75rem; }
        .sidebar-item { transition: all 0.2s ease; border-right: 4px solid transparent; }
        .sidebar-item.active { background-color: var(--color-rohis-50) !important; color: var(--color-rohis-700) !important; font-weight: 700; border-right-color: var(--color-rohis-600); }
        .sidebar-item.active .icon { color: var(--color-rohis-700) !important; }
        .sidebar-item:not(.active):hover { background-color: #f1f5f9; color: #0f172a; }
        
        /* Fallback for missing Tailwind classes */
        .left-3 { left: 1rem !important; }
        .left-4 { left: 1.25rem !important; }
        .pl-9 { padding-left: 2.75rem !important; }
        .pl-11 { padding-left: 3.25rem !important; }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-blue-500 selection:text-white">
    <div class="flex min-h-screen">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col fixed h-full z-20 shadow-sm">
            <!-- Logo Area -->
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-rohis-600 text-white rounded-md flex items-center justify-center shadow-sm">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 tracking-tight leading-tight">Admin</h1>
                        <p class="text-xs font-medium text-slate-500">Sistem Presensi</p>
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
                <p class="px-2 text-sm font-medium text-slate-500 mb-3">Menu Utama</p>
                
                <a href="Home.php" class="sidebar-item active w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="map-pin" class="icon w-5 h-5 text-slate-400"></i>
                    Pengaturan Lokasi
                </a>
                
                <a href="absensi.php" class="sidebar-item w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="clipboard-list" class="icon w-5 h-5 text-slate-400"></i>
                    Edit Data Presensi
                </a>
                
                <a href="akun.php" class="sidebar-item w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="user-cog" class="icon w-5 h-5 text-slate-400"></i>
                    Kelola Akun
                </a>
            </nav>

            <!-- Bottom Area / Logout -->
            <div class="p-4 border-t border-slate-100">
                <form method="POST" class="logout-form">
                    <input type="hidden" name="logout" value="1">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-md text-sm font-bold transition-colors">
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
                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Pengaturan Lokasi</h2>
                    <p class="text-sm text-slate-500 mt-1">Konfigurasi pusat lokasi dan radius toleransi absensi siswa.</p>
                </div>
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-md border border-slate-200 shadow-sm">
                    <i data-lucide="clock" class="w-4 h-4 text-blue-500"></i>
                    <span class="text-sm font-medium text-slate-600"><?= date('d M Y') ?></span>
                </div>
            </header>

            <!-- Messages -->
            <?php if ($msg): ?>
            <div class="mb-6 p-4 rounded-xl border flex items-start gap-3 <?= $msg_type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?> text-sm font-medium animate-in fade-in slide-in-from-top-4 duration-300">
                <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5"></i>
                <p><?= htmlspecialchars($msg) ?></p>
            </div>
            <?php endif; ?>

            <!-- Section: Pengaturan Lokasi -->
            <div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 md:p-8">
                        <div id="map" class="mb-8 border border-slate-200 shadow-inner"></div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="update_lokasi">
                            
                            <h3 class="text-sm font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Koordinat & Radius</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-2">Latitude</label>
                                    <div class="relative">
                                        <i data-lucide="map" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                        <input type="text" name="lat_admin" id="lat_admin" value="<?= $pengaturan['lat_admin'] ?? 0 ?>" class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-md text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-2">Longitude</label>
                                    <div class="relative">
                                        <i data-lucide="map" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                        <input type="text" name="lng_admin" id="lng_admin" value="<?= $pengaturan['lng_admin'] ?? 0 ?>" class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-md text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-2">Radius (KM)</label>
                                    <div class="relative">
                                        <i data-lucide="radar" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                        <input type="number" step="0.01" name="radius_km" id="radius_km" value="<?= $pengaturan['radius_km'] ?? 0.5 ?>" class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-md text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" required>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="text-sm font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Waktu Operasional</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-2">Jam Mulai Absensi</label>
                                    <div class="relative">
                                        <i data-lucide="clock-4" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                        <input type="text" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" placeholder="Contoh: 07:00" title="Format 24 jam (misal 07:00)" name="jam_mulai" value="<?= isset($pengaturan['jam_mulai']) ? date('H:i', strtotime($pengaturan['jam_mulai'])) : '07:00' ?>" class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-md text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 mb-2">Jam Selesai Absensi</label>
                                    <div class="relative">
                                        <i data-lucide="clock-8" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                        <input type="text" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" placeholder="Contoh: 15:00" title="Format 24 jam (misal 15:00)" name="jam_selesai" value="<?= isset($pengaturan['jam_selesai']) ? date('H:i', strtotime($pengaturan['jam_selesai'])) : '09:00' ?>" class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-md text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end pt-4 border-t border-slate-100">
                                <button type="submit" class="px-6 py-3 bg-rohis-600 hover:bg-rohis-700 text-white rounded-md text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Pengaturan
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

    <script>
    lucide.createIcons();

    // Leaflet Map Logic
    const initLat = <?= $pengaturan['lat_admin'] ?? 0 ?>;
    const initLng = <?= $pengaturan['lng_admin'] ?? 0 ?>;
    const initRadius = <?= $pengaturan['radius_km'] ?? 0.5 ?>;
    const startLat = initLat === 0 && initLng === 0 ? -6.2 : initLat;
    const startLng = initLat === 0 && initLng === 0 ? 106.8 : initLng;

    const map = L.map('map').setView([startLat, startLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    let marker = null;
    let circle = null;

    function setMarker(lat, lng) {
        if (marker) map.removeLayer(marker);
        if (circle) map.removeLayer(circle);
        
        // Custom icon
        const pinIcon = L.divIcon({
            html: '<div style="width: 24px; height: 24px; background-color: #2563eb; border: 3px solid white; border-radius: 50%; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"></div>',
            className: '',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        marker = L.marker([lat, lng], { draggable: true, icon: pinIcon }).addTo(map);
        const radiusM = parseFloat(document.getElementById('radius_km').value) * 1000;
        circle = L.circle([lat, lng], { 
            radius: radiusM, 
            color: '#3b82f6', 
            weight: 2,
            fillColor: '#60a5fa', 
            fillOpacity: 0.2 
        }).addTo(map);
        
        document.getElementById('lat_admin').value = lat.toFixed(6);
        document.getElementById('lng_admin').value = lng.toFixed(6);
        
        marker.on('dragend', function(e) {
            const p = e.target.getLatLng();
            setMarker(p.lat, p.lng);
        });
    }

    if (initLat !== 0 || initLng !== 0) setMarker(initLat, initLng);

    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
    });

    document.getElementById('radius_km').addEventListener('input', function() {
        if (marker) {
            const ll = marker.getLatLng();
            setMarker(ll.lat, ll.lng);
        }
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