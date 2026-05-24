<?php
session_start();
include '../lng/koneksi.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: ../lng/lgn.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../lng/lgn.php");
    exit();
}

$bulan_ini = date('Y-m');
$hari_ini = date('Y-m-d');

// Total absensi bulan ini
$total = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM absensi WHERE user_id = $user_id AND tanggal LIKE '$bulan_ini%'");
if ($row = $result->fetch_assoc()) {
    $total = $row['total'];
}

// % Kehadiran
$total_hari_bulan = (int)date('t');
$hadir_pct = $total_hari_bulan > 0 ? round(($total / $total_hari_bulan) * 100, 1) : 0;

// Status hari ini
$status_hari_ini = 'Belum absen';
$color_status = 'gray';
$today_result = $conn->query("SELECT status, waktu FROM absensi WHERE user_id = $user_id AND tanggal = '$hari_ini'");
if ($today_row = $today_result->fetch_assoc()) {
    $status_hari_ini = $today_row['status'] === 'hadir' ? 'Hadir' : ucfirst($today_row['status']);
    $color_status = $today_row['status'] === 'hadir' ? 'green' : 'yellow';
}

// Riwayat 10 terakhir
$riwayat = [];
$riwayat_result = $conn->query("SELECT tanggal, waktu, status FROM absensi WHERE user_id = $user_id ORDER BY tanggal DESC, waktu DESC LIMIT 10");
while ($row = $riwayat_result->fetch_assoc()) {
    $riwayat[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ROHIS Portal & Absensi</title>
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
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        @keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

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
        
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col flex-shrink-0 z-20 shadow-sm">
            <!-- Logo -->
            <div class="h-20 flex items-center px-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 logo-gradient rounded-xl flex items-center justify-center shadow-md shadow-blue-500/20">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 tracking-tight leading-tight">ROHIS</h1>
                        <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Portal Presensi</p>
                    </div>
                </div>
            </div>

            <!-- User Info Area -->
            <div class="px-6 py-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full bg-blue-100 border-2 border-white shadow-sm flex items-center justify-center text-blue-600 font-bold text-sm">
                        <?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></p>
                        <p class="text-xs text-slate-500 font-medium truncate">Siswa</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <p class="px-2 text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Menu Utama</p>
                
                <a href="home.php" class="sidebar-item active w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 text-left">
                    <i data-lucide="layout-dashboard" class="icon w-5 h-5 text-slate-400"></i>
                    Dashboard
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
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 h-screen overflow-y-auto bg-slate-50 relative">
            
            <!-- Background Decoration -->
            <div class="absolute top-0 left-0 right-0 h-64 bg-rohis-600 rounded-b-[3rem] shadow-sm -z-10"></div>
            
            <div class="p-8 max-w-5xl mx-auto">
                <!-- Header -->
                <header class="mb-8 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 fade-in">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-white font-medium text-xs mb-3 border border-white/20">
                            <i data-lucide="sparkles" class="w-3 h-3"></i>
                            Selamat Datang Kembali
                        </div>
                        <h2 class="text-3xl font-extrabold tracking-tight">Halo, <?= htmlspecialchars(explode(' ', trim($_SESSION['nama'] ?? 'User'))[0]) ?>!</h2>
                        <p class="text-white/70 text-sm mt-1"><?= date('l, d F Y') ?></p>
                    </div>
                    <a href="absen.php" class="bg-white hover:bg-slate-50 text-rohis-600 px-6 py-3 rounded-full font-bold shadow-lg shadow-black/10 transition-all flex items-center justify-center gap-2 shrink-0">
                        <i data-lucide="scan-face" class="w-4 h-4"></i>
                        Presensi Sekarang
                    </a>
                </header>

                <!-- Dashboard Stats -->
                <section class="mb-10 fade-in delay-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Stat 1 -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                            <div class="absolute right-0 top-0 w-24 h-24 bg-rohis-50 rounded-bl-full -z-0 transition-transform group-hover:scale-110"></div>
                            <div class="flex items-center justify-between mb-4 relative z-10">
                                <div class="w-12 h-12 rounded-2xl bg-rohis-500 text-white flex items-center justify-center shadow-md shadow-rohis-500/20">
                                    <i data-lucide="calendar-check" class="w-6 h-6"></i>
                                </div>
                                <span class="text-[10px] font-bold text-rohis-600 bg-rohis-100 px-2.5 py-1 rounded-full uppercase tracking-wider">Bulan Ini</span>
                            </div>
                            <div class="relative z-10">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Kehadiran</p>
                                <p class="text-3xl font-black text-slate-800 mt-1"><?= $total ?> <span class="text-sm font-medium text-slate-500">hari</span></p>
                            </div>
                        </div>
                        
                        <!-- Stat 2 -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                            <div class="absolute right-0 top-0 w-24 h-24 bg-rohis-50 rounded-bl-full -z-0 transition-transform group-hover:scale-110"></div>
                            <div class="flex items-center justify-between mb-4 relative z-10">
                                <div class="w-12 h-12 rounded-2xl bg-rohis-400 text-white flex items-center justify-center shadow-md shadow-rohis-400/20">
                                    <i data-lucide="pie-chart" class="w-6 h-6"></i>
                                </div>
                                <span class="text-[10px] font-bold text-rohis-600 bg-rohis-100 px-2.5 py-1 rounded-full uppercase tracking-wider">Estimasi</span>
                            </div>
                            <div class="relative z-10">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Persentase</p>
                                <p class="text-3xl font-black <?= $hadir_pct >= 80 ? 'text-green-600' : ($hadir_pct >= 50 ? 'text-yellow-600' : 'text-red-600') ?> mt-1"><?= $hadir_pct ?>%</p>
                            </div>
                        </div>
                        
                        <!-- Stat 3 -->
                        <?php
                            if ($status_hari_ini === 'Hadir') {
                                $stat3_bg_decor = 'bg-green-50';
                                $stat3_icon_bg = 'bg-green-500 shadow-green-500/20';
                                $stat3_badge = 'text-green-600 bg-green-100';
                                $stat3_icon = 'check-circle';
                                $stat3_text = 'text-green-600';
                            } elseif ($status_hari_ini === 'Belum absen') {
                                $stat3_bg_decor = 'bg-slate-50';
                                $stat3_icon_bg = 'bg-slate-500 shadow-slate-500/20';
                                $stat3_badge = 'text-slate-600 bg-slate-100';
                                $stat3_icon = 'clock';
                                $stat3_text = 'text-slate-600';
                            } else {
                                $stat3_bg_decor = 'bg-yellow-50';
                                $stat3_icon_bg = 'bg-yellow-500 shadow-yellow-500/20';
                                $stat3_badge = 'text-yellow-600 bg-yellow-100';
                                $stat3_icon = 'alert-circle';
                                $stat3_text = 'text-yellow-600';
                            }
                        ?>
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                            <div class="absolute right-0 top-0 w-24 h-24 <?= $stat3_bg_decor ?> rounded-bl-full -z-0 transition-transform group-hover:scale-110"></div>
                            <div class="flex items-center justify-between mb-4 relative z-10">
                                <div class="w-12 h-12 rounded-2xl <?= $stat3_icon_bg ?> text-white flex items-center justify-center shadow-md">
                                    <i data-lucide="<?= $stat3_icon ?>" class="w-6 h-6"></i>
                                </div>
                                <span class="text-[10px] font-bold <?= $stat3_badge ?> px-2.5 py-1 rounded-full uppercase tracking-wider">Hari Ini</span>
                            </div>
                            <div class="relative z-10">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status Presensi</p>
                                <p class="text-2xl font-black <?= $stat3_text ?> mt-1 tracking-tight"><?= $status_hari_ini ?></p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- RIWAYAT ABSENSI -->
                <section class="fade-in delay-200 mb-8">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 md:p-8">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                    <i data-lucide="history" class="w-5 h-5 text-blue-500"></i>
                                    Riwayat Kehadiran Terakhir
                                </h3>
                                <p class="text-xs text-slate-500 mt-1">10 catatan presensi terakhir Anda</p>
                            </div>
                        </div>

                        <?php if (empty($riwayat)): ?>
                            <div class="text-center py-12">
                                <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                </div>
                                <p class="text-slate-500 font-medium text-sm">Belum ada riwayat absensi.</p>
                                <a href="absen.php" class="inline-block mt-3 text-blue-600 hover:text-blue-700 font-bold text-sm underline decoration-2 underline-offset-4">Mulai absen pertama Anda</a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                                <?php foreach ($riwayat as $row): 
                                    $status_text = $row['status'] === 'hadir' ? 'Hadir' : ucfirst($row['status']);
                                    $is_hadir = $row['status'] === 'hadir';
                                ?>
                                    <div class="flex justify-between items-center p-4 rounded-2xl border border-slate-100 bg-slate-50 hover:bg-white hover:shadow-md transition-all group">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl <?= $is_hadir ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600' ?> flex items-center justify-center shrink-0">
                                                <i data-lucide="<?= $is_hadir ? 'check' : 'alert-circle' ?>" class="w-5 h-5"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800"><?= date('d F Y', strtotime($row['tanggal'])) ?></p>
                                                <p class="text-xs text-slate-500 mt-0.5">Status: <span class="<?= $is_hadir ? 'text-green-600' : 'text-yellow-600' ?> font-bold"><?= $status_text ?></span></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 font-bold font-mono text-sm shadow-sm">
                                                <?= $row['waktu'] ? date('H:i', strtotime($row['waktu'])) : '-' ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Footer within main area -->
                <footer class="py-4 text-center text-xs font-medium text-slate-400">
                    © <?= date('Y') ?> Portal Absensi ROHIS. All rights reserved.
                </footer>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        lucide.createIcons();
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
