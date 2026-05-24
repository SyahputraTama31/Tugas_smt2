    <!DOCTYPE html>
    <?php session_start(); ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="p-3 bg-red-50 border border-red-200 rounded-xl mb-4">
                    <p class="text-sm text-red-700"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="p-3 bg-green-50 border border-green-200 rounded-xl mb-4">
                    <p class="text-sm text-green-700"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                </div>
            <?php endif; ?>

    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ROHIS - Login</title>
        <link rel="stylesheet" href="../src/output.css?v=<?= filemtime('../src/output.css') ?>">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="shortcut icon" href="../img/lg.jpg" type="image/x-icon">
    </head>
    <body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex items-center justify-center px-4 relative overflow-hidden selection:bg-rohis-500 selection:text-white" style="font-family: 'Plus Jakarta Sans', sans-serif;">
        <!-- Background Decoration -->
        <div class="absolute top-0 inset-x-0 h-96 bg-gradient-to-b from-rohis-100/50 to-transparent -z-10"></div>
        <div class="absolute top-0 right-0 w-1/2 h-96 bg-rohis-100/30 rounded-bl-full blur-3xl -z-10"></div>
        <div class="absolute top-40 left-0 w-64 h-64 bg-blue-100/40 rounded-tr-full blur-3xl -z-10"></div>

        <div class="bg-white border border-sky-100 rounded-3xl shadow-xl shadow-sky-100 w-full max-w-sm px-8 py-10">

            <!-- Icon -->
            <div class="flex justify-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rohis-500 to-rohis-700 flex items-center justify-center shadow-lg shadow-rohis-500/30">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>

            <!-- Heading -->
            <h1 class="text-2xl font-bold text-center text-slate-800 tracking-tight">Selamat Datang</h1>
            <p class="text-center text-sm text-rohis-500 font-medium mt-1 mb-7">ROHIS — Masuk ke akun kamu</p>

    <!-- Messages -->

            <!-- Divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-sky-200 to-transparent mb-7"></div>

            <!-- Form -->
            <form action="pros.php" method="post" class="flex flex-col gap-5">

                <!-- Username -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Username</label>
                    <input
                        type="text"
                        name="username"
                        placeholder="username anda"
                        required
                        class="w-full px-4 py-3 rounded-xl border border-sky-200 bg-sky-50 text-sm text-slate-700 placeholder-slate-300 focus:outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                    >
                </div>

                <!-- Password with show/hide -->
                <div class="flex flex-col gap-1.5 relative">
                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            name="Password"
                            id="login-password"
                            placeholder="••••••••"
                            required
                            class="w-full px-4 py-3 rounded-xl border border-sky-200 bg-sky-50 text-sm text-slate-700 placeholder-slate-300 focus:outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100 pr-10"
                        >
                        <button
                            type="button"
                            onclick="togglePassword('login-password')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="mt-1 w-full py-3 rounded-xl bg-gradient-to-r from-rohis-500 to-rohis-600 hover:from-rohis-600 hover:to-rohis-700 text-white font-semibold text-sm tracking-wide shadow-md shadow-rohis-500/30"
                >
                    Masuk
                </button>

            </form>

            <!-- Register -->
            <p class="text-center text-xs text-slate-400 mt-6">
                Belum punya akun?
                <a href="register.php" class="text-rohis-600 font-semibold hover:text-rohis-800">Registrasi</a>
            </p>

        </div>

        <script>
            function togglePassword(id) {
                const passwordField = document.getElementById(id);
                const icon = passwordField.parentElement.querySelector('button svg');
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
                } else {
                    passwordField.type = 'password';
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
                }
            }
        </script>

    </body>
    </html>
