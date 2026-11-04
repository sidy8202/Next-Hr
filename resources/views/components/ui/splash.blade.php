@props(['subtitle' => 'Chargement de votre espace'])

{{-- Aide partagée : exécute une animation une fois l'écran de chargement terminé (ou tout de suite s'il n'y en a pas). --}}
<script>
    window.nexthrReady = window.nexthrReady || function (cb) {
        if (window.__splashActive) { document.addEventListener('splash:done', function () { cb(); }, { once: true }); }
        else { requestAnimationFrame(function () { cb(); }); }
    };
</script>

{{-- Verrou d'interface pendant un traitement : tant qu'une requête Livewire est en cours (plus de 0,25 s), un calque transparent
     bloque tous les clics, le curseur passe en « attente » et une barre de progression apparaît en haut de l'écran.
     Installé une seule fois, il survit à la navigation entre pages. --}}
<script>
    (function () {
        if (window.__nhBusyInstalled) { return; }
        window.__nhBusyInstalled = true;

        var pending = 0, timer = null, shown = false, failsafe = null;

        function layer() {
            var el = document.getElementById('nh-busy');

            if (! el || ! el.isConnected) {
                if (! document.getElementById('nh-busy-style')) {
                    var st = document.createElement('style');
                    st.id = 'nh-busy-style';
                    st.textContent = '@keyframes nh-busy-bar{0%{margin-left:-40%}100%{margin-left:100%}} html.nh-busy, html.nh-busy *{cursor:wait !important}';
                    document.head.appendChild(st);
                }

                el = document.createElement('div');
                el.id = 'nh-busy';
                el.setAttribute('aria-hidden', 'true');
                el.style.cssText = 'position:fixed;top:0;right:0;bottom:0;left:0;z-index:2147483000;display:none;background:transparent';
                el.innerHTML = '<div style="position:fixed;top:0;left:0;right:0;height:3px;overflow:hidden;background:rgba(99,102,241,.18)"><div style="height:100%;width:40%;background:#6366f1;animation:nh-busy-bar 1s ease-in-out infinite"></div></div>';
                document.body.appendChild(el);
            }

            return el;
        }

        function show() { shown = true; layer().style.display = 'block'; document.documentElement.classList.add('nh-busy'); }
        function hide() { shown = false; var el = document.getElementById('nh-busy'); if (el) { el.style.display = 'none'; } document.documentElement.classList.remove('nh-busy'); }

        function start() {
            pending++;
            if (! timer && ! shown) { timer = setTimeout(function () { timer = null; if (pending > 0) { show(); } }, 250); }
            clearTimeout(failsafe);
            failsafe = setTimeout(function () { pending = 0; hide(); }, 45000);   // sécurité : jamais bloqué plus de 45 s
        }

        function done() {
            pending = Math.max(0, pending - 1);

            if (pending === 0) {
                if (timer) { clearTimeout(timer); timer = null; }
                clearTimeout(failsafe);
                hide();
            }
        }

        var nativeFetch = window.fetch;

        window.fetch = function (input, init) {
            var url = typeof input === 'string' ? input : ((input && input.url) || '');
            var method = String((init && init.method) || (input && input.method) || 'GET').toUpperCase();

            if (method !== 'POST' || ! /livewire/i.test(url)) { return nativeFetch.apply(this, arguments); }

            start();

            return nativeFetch.apply(this, arguments).then(function (r) { done(); return r; }, function (e) { done(); throw e; });
        };

        document.addEventListener('livewire-upload-start', start);
        ['livewire-upload-finish', 'livewire-upload-error', 'livewire-upload-cancel'].forEach(function (ev) { document.addEventListener(ev, done); });
    })();
</script>

{{-- Écran de chargement affiché UNE fois, juste après la connexion (drapeau de session « show_splash »). --}}
@if (session()->pull('show_splash', false))
    <style>
        @keyframes nh-draw { to { stroke-dashoffset: 0; } }
        @keyframes nh-float { 0%, 100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-18px) scale(1.06); } }
        @keyframes nh-pulse { 0%, 100% { transform: scale(1); opacity: .35; } 50% { transform: scale(1.25); opacity: 0; } }
        .nh-draw { stroke-dasharray: 1; stroke-dashoffset: 1; animation: nh-draw 1.1s .15s ease forwards; }
        @media (prefers-reduced-motion: reduce) { .nh-draw { animation: none; stroke-dashoffset: 0; } .nh-anim { animation: none !important; } }
    </style>
    <div id="nexthr-splash"
         x-data="{ open: true, p: 0, step: 0, msgs: ['Connexion sécurisée…', 'Chargement des indicateurs…', 'Préparation de votre espace…'],
            init() {
                window.__splashActive = true;
                document.documentElement.classList.add('overflow-hidden');
                const t = setInterval(() => {
                    this.p = Math.min(100, this.p + 4 + Math.random() * 9);
                    this.step = this.p < 34 ? 0 : (this.p < 70 ? 1 : 2);
                    if (this.p >= 100) {
                        clearInterval(t);
                        setTimeout(() => {
                            this.open = false;
                            window.__splashActive = false;
                            document.documentElement.classList.remove('overflow-hidden');
                            document.dispatchEvent(new CustomEvent('splash:done'));
                        }, 450);
                    }
                }, 120);
            } }"
         x-show="open" x-transition:leave="transition duration-700 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-110"
         class="fixed inset-0 z-[100] flex flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-indigo-800 via-indigo-600 to-violet-700 text-white">
        <div class="nh-anim absolute -left-24 -top-24 h-80 w-80 rounded-full bg-white/10 blur-3xl" style="animation: nh-float 7s ease-in-out infinite"></div>
        <div class="nh-anim absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-fuchsia-400/20 blur-3xl" style="animation: nh-float 9s ease-in-out infinite reverse"></div>

        <div class="relative flex h-28 w-28 items-center justify-center">
            <span class="nh-anim absolute inset-0 rounded-3xl bg-white/30" style="animation: nh-pulse 2s ease-in-out infinite"></span>
            <svg viewBox="0 0 64 64" class="relative h-24 w-24" fill="none" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="6" y="6" width="52" height="52" rx="16" pathLength="1" class="nh-draw" />
                <path d="M21 44V20l22 24V20" pathLength="1" class="nh-draw" style="animation-delay: .5s" />
            </svg>
        </div>

        <h2 class="mt-6 text-3xl font-bold tracking-tight">Next HR</h2>
        <p class="mt-1 text-sm text-white/70">{{ $subtitle }}</p>

        <div class="mt-8 w-64">
            <div class="h-1.5 overflow-hidden rounded-full bg-white/20"><div class="h-full rounded-full bg-white transition-all duration-150" :style="{ width: p + '%' }"></div></div>
            <p class="mt-3 text-center text-xs text-white/70" x-text="msgs[step]">Connexion sécurisée…</p>
        </div>
    </div>
@endif
