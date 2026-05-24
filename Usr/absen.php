<?php
session_start();
include '../lng/koneksi.php';

$user_id = $_SESSION['user_id'] ?? 0;
$message = '';
$message_type = '';

if (!$user_id) {
    header("Location: ../lng/lgn.php");
    exit();
}

if ($_POST) {
    $tanggal = date('Y-m-d');
    $jam = date('H:i:s');
    $lat = floatval($_POST['lat'] ?? 0);
    $lng = floatval($_POST['lng'] ?? 0);
    $foto_base64 = $_POST['foto_base64'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    
    // Check double absen hari ini
    $check_stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
    $check_stmt->bind_param("is", $user_id, $tanggal);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $message = 'Anda sudah melakukan absensi hari ini.';
        $message_type = 'error';
    } else {
        // Get pengaturan
        $pengaturan = $conn->query("SELECT * FROM pengaturan_absen WHERE id = 1")->fetch_assoc();
        
        // Cek jam absensi
        $jam_mulai = $pengaturan['jam_mulai'];
        $jam_selesai = $pengaturan['jam_selesai'];
        $jam_sekarang = date('H:i:s');
        
        $is_valid_time = false;
        if ($jam_mulai <= $jam_selesai) {
            $is_valid_time = ($jam_sekarang >= $jam_mulai && $jam_sekarang <= $jam_selesai);
        } else {
            // Jika jam buka melewati tengah malam (misal 21:00 - 12:00)
            $is_valid_time = ($jam_sekarang >= $jam_mulai || $jam_sekarang <= $jam_selesai);
        }
        
        if (!$is_valid_time) {
            $message = 'Absensi di luar jam operasional (Dibuka pukul ' . date('H:i', strtotime($jam_mulai)) . ' - ' . date('H:i', strtotime($jam_selesai)) . ')';
            $message_type = 'error';
        } else {
            // Calculate distance
            $lat_admin = $pengaturan['lat_admin'];
            $lng_admin = $pengaturan['lng_admin'];
            $radius_km = $pengaturan['radius_km'];
            
            $earth_radius = 6371;
            $dlat = deg2rad($lat - $lat_admin);
            $dlng = deg2rad($lng - $lng_admin);
            $a = sin($dlat/2) * sin($dlat/2) + cos(deg2rad($lat_admin)) * cos(deg2rad($lat)) * sin($dlng/2) * sin($dlng/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance = $earth_radius * $c;
            
            if ($distance > $radius_km) {
                $message = 'Anda berada di luar jangkauan lokasi absensi (' . round($distance, 2) . ' km dari sekolah, maks ' . $radius_km . ' km)!';
                $message_type = 'error';
            } else {
                // Insert absensi
                $stmt = $conn->prepare("INSERT INTO absensi (user_id, tanggal, waktu, lat, lng, foto_base64, pesan, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'hadir')");
                $stmt->bind_param("issddss", $user_id, $tanggal, $jam, $lat, $lng, $foto_base64, $pesan);
                if ($stmt->execute()) {
                    $message = 'Absensi berhasil tersimpan! Semangat harimu!';
                    $message_type = 'success';
                } else {
                    $message = 'Terjadi kesalahan sistem: ' . $conn->error;
                    $message_type = 'error';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kamera Absensi - ROHIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-item { transition: all 0.2s ease; border-right: 4px solid transparent; }
        .sidebar-item.active { background-color: var(--color-rohis-50) !important; color: var(--color-rohis-700) !important; font-weight: 700; border-right-color: var(--color-rohis-600); }
        .sidebar-item.active .icon { color: var(--color-rohis-700) !important; }
        .sidebar-item:not(.active):hover { background-color: #f1f5f9; color: #0f172a; }
        .fade-in { animation: fadeIn 0.5s ease forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
        /* Fix video mirroring */
        #videoPreview { transform: scaleX(-1); }

        /* Fallback for missing Tailwind classes */
        .left-3 { left: 1rem !important; }
        .left-4 { left: 1.25rem !important; }
        .pl-9 { padding-left: 2.75rem !important; }
        .pl-11 { padding-left: 3.25rem !important; }
        .logo-gradient { background: linear-gradient(135deg, var(--color-rohis-500), var(--color-rohis-700)); color: white; }
    </style>
</head>
<body class="text-slate-800 antialiased selection:bg-blue-500 selection:text-white overflow-hidden">
    <div class="flex h-screen w-full">
        


        <!-- Main Content -->
        <main class="flex-1 h-screen overflow-y-auto bg-slate-50 relative">
            
            <!-- Background Decoration -->
            <div class="absolute top-0 left-0 right-0 h-48 bg-rohis-600 rounded-b-[3rem] shadow-sm -z-10"></div>
            
            <!-- Tombol Kembali Kanan Atas Absolute -->
            <a href="home.php" style="top: 2rem; right: 2rem;" class="absolute bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-full text-sm font-bold transition-all flex items-center gap-2 z-10 shadow-sm fade-in">
                <i data-lucide="arrow-left" class="w-4 h-4 text-slate-400"></i>
                Kembali
            </a>

            <div class="p-8 max-w-3xl mx-auto">
                
                <header class="mb-8 text-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 fade-in">
                    <div>
                        <h2 class="text-3xl font-extrabold tracking-tight">Kamera Presensi</h2>
                    </div>
                </header>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-2xl flex gap-3 items-start <?= $message_type == 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?> fade-in shadow-sm">
                        <i data-lucide="<?= $message_type == 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5 shrink-0 mt-0.5 <?= $message_type == 'success' ? 'text-green-600' : 'text-red-600' ?>"></i>
                        <div>
                            <p class="font-bold text-sm"><?= $message_type == 'success' ? 'Berhasil!' : 'Gagal!' ?></p>
                            <p class="text-xs mt-1 opacity-90"><?= htmlspecialchars($message) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 fade-in">
                    <form method="POST" id="absenForm" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="lat" id="lat">
                        <input type="hidden" name="lng" id="lng">
                        <input type="hidden" name="foto_base64" id="foto_base64">

                        <!-- Placeholder Area before Camera Starts -->
                        <div id="cameraPlaceholder" class="w-full h-72 bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center text-slate-400">
                            <div class="w-16 h-16 bg-white rounded-full shadow-sm border border-slate-100 flex items-center justify-center mb-4">
                                <i data-lucide="camera-off" class="w-8 h-8 text-slate-300"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-600">Kamera belum aktif</p>
                            <p class="text-xs text-slate-400 mt-1">Klik tombol di bawah untuk memulai</p>
                        </div>

                        <!-- Video Stream Area -->
                        <div id="videoContainer" class="hidden relative rounded-2xl overflow-hidden shadow-inner bg-slate-900 border border-slate-200">
                            <video id="videoPreview" class="w-full h-72 object-cover" autoplay playsinline></video>

                        </div>

                        <!-- Capture Preview Area -->
                        <div id="resultContainer" class="hidden relative rounded-2xl overflow-hidden shadow-sm border border-slate-200">
                            <img id="fotoPreview" class="w-full h-72 object-cover" alt="Preview">
                            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent p-5">
                                <div class="flex items-center gap-2 text-white/90 text-xs font-bold">
                                    <div class="w-6 h-6 rounded-full bg-blue-500/20 border border-blue-400/50 flex items-center justify-center">
                                        <i data-lucide="map-pin" class="w-3 h-3 text-blue-400"></i>
                                    </div>
                                    <span id="coords" class="truncate drop-shadow-md">Koordinat tercatat</span>
                                </div>
                            </div>
                        </div>

                        <!-- Motivation Textarea -->
                        <div id="pesanContainer" class="hidden space-y-2 pt-6 border-t border-slate-100">
                            <label for="pesan" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Pesan / Kesan Hari Ini <span class="normal-case font-medium text-slate-400 tracking-normal">(Opsional)</span></label>
                            <textarea name="pesan" id="pesan" rows="3" placeholder="Apa motivasi harimu ini?" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all resize-none"></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4">
                            <button type="button" id="startBtn" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-4 px-6 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                                <i data-lucide="power" class="w-5 h-5"></i> Aktifkan Kamera & Lokasi
                            </button>
                            
                            <button type="button" id="captureBtn" class="hidden w-full bg-rohis-600 hover:bg-rohis-700 text-white font-bold py-4 px-6 rounded-xl shadow-md shadow-rohis-500/20 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="aperture" class="w-5 h-5"></i> Ambil Foto Wajah
                            </button>

                            <div id="submitGroup" class="hidden grid grid-cols-2 gap-3">
                                <button type="button" id="retakeBtn" class="bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-700 font-bold py-3.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-sm">
                                    <i data-lucide="rotate-ccw" class="w-4 h-4 text-slate-400"></i> Ulangi
                                </button>
                                <button type="submit" id="submitBtn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-md shadow-green-600/20 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="send" class="w-4 h-4"></i> Kirim Absensi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Footer within main area -->
                <footer class="py-6 text-center text-xs font-medium text-slate-400">
                    © <?= date('Y') ?> Presensi Rohis. All rights reserved.
                </footer>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        let stream = null;
        let fotoData = null;
        const video = document.getElementById('videoPreview');
        const startBtn = document.getElementById('startBtn');
        const captureBtn = document.getElementById('captureBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const submitGroup = document.getElementById('submitGroup');
        const cameraPlaceholder = document.getElementById('cameraPlaceholder');
        const videoContainer = document.getElementById('videoContainer');
        const resultContainer = document.getElementById('resultContainer');
        const pesanContainer = document.getElementById('pesanContainer');
        
        startBtn.onclick = async function() {
            try {
                startBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> Meminta Izin...';
                startBtn.disabled = true;
                lucide.createIcons();
                
                // Get location
                const pos = await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }));
                document.getElementById('lat').value = pos.coords.latitude;
                document.getElementById('lng').value = pos.coords.longitude;
                document.getElementById('coords').textContent = `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`;
                
                // Camera
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: "user" } 
                });
                video.srcObject = stream;
                
                cameraPlaceholder.classList.add('hidden');
                videoContainer.classList.remove('hidden');
                
                startBtn.classList.add('hidden');
                captureBtn.classList.remove('hidden');
                
            } catch (err) {
                alert('Gagal! Pastikan Anda mengizinkan akses Lokasi dan Kamera. Error: ' + err.message);
                startBtn.innerHTML = '<i data-lucide="power" class="w-5 h-5"></i> Aktifkan Kamera & Lokasi';
                startBtn.disabled = false;
                lucide.createIcons();
            }
        };

        captureBtn.onclick = function() {
            const canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            
            const ctx = canvas.getContext('2d');
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            fotoData = canvas.toDataURL('image/jpeg', 0.8);
            
            document.getElementById('foto_base64').value = fotoData;
            document.getElementById('fotoPreview').src = fotoData;
            
            videoContainer.classList.add('hidden');
            resultContainer.classList.remove('hidden');
            pesanContainer.classList.remove('hidden');
            
            captureBtn.classList.add('hidden');
            submitGroup.classList.remove('hidden');
            
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        };

        retakeBtn.onclick = function() {
            // Reset to camera view
            resultContainer.classList.add('hidden');
            pesanContainer.classList.add('hidden');
            submitGroup.classList.add('hidden');
            captureBtn.classList.remove('hidden');
            
            // Restart camera
            startBtn.onclick();
        };
        
        // Cleanup on page hide
        window.onunload = () => {
            if (stream) stream.getTracks().forEach(track => track.stop());
        };
    </script>
</body>
</html>
