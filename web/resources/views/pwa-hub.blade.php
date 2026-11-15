<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-[#0B1325]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0B1325">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>LotoLab - Bolões Mobile</title>
    <link rel="manifest" href="/manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full text-slate-100 bg-[#0B1325] flex flex-col justify-between p-4 antialiased select-none">

    <div class="max-w-sm mx-auto w-full my-auto flex flex-col items-center">
        <!-- Logo e Título -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-500 to-indigo-600 text-white font-black text-2xl shadow-xl shadow-emerald-950/60 mb-3">
                LL
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">
                LotoLab Mobile
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Selecione a loteria para acessar seu bolão
            </p>
        </div>

        <!-- Cards de Seleção -->
        <div class="w-full space-y-3.5">
            <!-- Mega-Sena -->
            <a 
                href="/pwa/megasena/login" 
                class="block p-5 rounded-2xl bg-gradient-to-br from-[#162032] to-[#1a2e3b] border border-emerald-500/40 hover:border-emerald-400 shadow-xl shadow-black/40 transition active:scale-[0.98] group"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-black text-lg border border-emerald-500/40 group-hover:scale-105 transition">
                            MS
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white group-hover:text-emerald-400 transition">
                                Mega-Sena
                            </h2>
                            <p class="text-xs text-slate-400">
                                6 dezenas por jogo · 60 números
                            </p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-emerald-400 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>

            <!-- Lotofácil -->
            <a 
                href="/pwa/lotofacil/login" 
                class="block p-5 rounded-2xl bg-gradient-to-br from-[#162032] to-[#241a38] border border-purple-500/40 hover:border-purple-400 shadow-xl shadow-black/40 transition active:scale-[0.98] group"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-black text-lg border border-purple-500/40 group-hover:scale-105 transition">
                            LF
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white group-hover:text-purple-400 transition">
                                Lotofácil
                            </h2>
                            <p class="text-xs text-slate-400">
                                15 dezenas por jogo · 25 números
                            </p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-purple-400 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </a>
        </div>

        <!-- Banner de Instalação PWA -->
        <div id="pwa-install-banner" class="hidden mt-6 w-full p-4 rounded-xl bg-slate-900/90 border border-slate-800 text-center">
            <p class="text-xs text-slate-300 font-medium mb-2">Instale o app na sua tela de início para acesso rápido:</p>
            <button 
                id="btn-install-pwa" 
                class="w-full bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold py-2.5 px-4 rounded-lg border border-slate-700 flex items-center justify-center gap-2"
            >
                <span>📲 Instalar Aplicativo</span>
            </button>
        </div>

        <div class="mt-8 text-center">
            <a href="/app/login" class="text-xs text-slate-500 hover:text-slate-400 font-medium">
                Área Administrativa &rarr;
            </a>
        </div>
    </div>

    <script>
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const banner = document.getElementById('pwa-install-banner');
            if (banner) banner.classList.remove('hidden');
        });

        document.getElementById('btn-install-pwa')?.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    document.getElementById('pwa-install-banner').classList.add('hidden');
                }
                deferredPrompt = null;
            }
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js');
            });
        }
    </script>
</body>
</html>
