<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-[#0B1325]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0B1325">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'LotoBolão Lotofácil PWA' }}</title>

    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            bg: '#0B1325',
                            card: '#162032',
                            input: '#243044',
                            green: '#00C853',
                            hoverGreen: '#00E676',
                            purple: '#9333EA',
                            purpleLight: '#A855F7'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body class="h-full text-slate-100 bg-[#0B1325] flex flex-col justify-between select-none antialiased">
    
    @if($showHeader ?? false)
    <header class="sticky top-0 z-30 bg-[#162032]/80 backdrop-blur-md border-b border-slate-800/80 px-4 py-2 text-center shadow-lg">
        @auth
            @livewire('pwa.lotofacil.bolao-selector')
        @else
            <h1 class="text-xl font-black text-brand-green">
                Bolão Lotofácil
            </h1>
        @endauth
    </header>
    @endif

    <main class="flex-1 max-w-md mx-auto w-full px-4 pt-4 pb-24 flex flex-col">
        {{ $slot }}
    </main>

    @if($showNav ?? true)
    <nav class="fixed bottom-0 left-0 right-0 z-40 bg-[#162032]/95 backdrop-blur-xl border-t border-slate-800/80 px-2 py-2 max-w-md mx-auto">
        <div class="flex justify-around items-center">
            
            <!-- Dashboard Lotofácil -->
            <a href="/pwa/lotofacil/dashboard" class="flex flex-col items-center gap-1 transition {{ request()->is('pwa/lotofacil/dashboard') || request()->is('pwa/lotofacil') ? 'text-white font-bold' : 'text-slate-400 hover:text-slate-200' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <span class="text-[10px]">Dashboard</span>
            </a>

            <!-- Seleção (Volante Lotofácil) -->
            <a href="/pwa/lotofacil/selecao" class="flex flex-col items-center gap-1 transition {{ request()->is('pwa/lotofacil/selecao') ? 'text-white font-bold' : 'text-slate-400 hover:text-slate-200' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-[10px]">Seleção</span>
            </a>

            <!-- Apostas -->
            <a href="/pwa/lotofacil/apostas" class="flex flex-col items-center gap-1 transition {{ request()->is('pwa/lotofacil/apostas') ? 'text-white font-bold' : 'text-slate-400 hover:text-slate-200' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-[10px]">Apostas</span>
            </a>

            <!-- Resultado -->
            <a href="/pwa/lotofacil/resultado" class="flex flex-col items-center gap-1 transition {{ request()->is('pwa/lotofacil/resultado') ? 'text-white font-bold' : 'text-slate-400 hover:text-slate-200' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-[10px]">Resultado</span>
            </a>

            <!-- Sair -->
            <a href="/pwa/lotofacil/logout" class="flex flex-col items-center gap-1 text-slate-400 hover:text-rose-400 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="text-[10px]">Sair</span>
            </a>

        </div>
    </nav>
    @endif

    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('PWA ServiceWorker registrado:', reg.scope))
                    .catch(err => console.error('Erro ServiceWorker PWA:', err));
            });
        }
    </script>
</body>
</html>
