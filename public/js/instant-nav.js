/**
 * Perfloplast — Instant Navigation System
 * Emula el comportamiento de Next.js: barra de progreso + prefetch en hover
 */
(function () {
    'use strict';

    // ── 1. CREAR BARRA DE PROGRESO ──
    const bar = document.createElement('div');
    bar.id = 'perfloplast-progress-bar';
    document.body.prepend(bar);

    function startProgress() {
        bar.classList.remove('done');
        bar.style.width = '0%';
        bar.style.opacity = '0';
        // Force reflow so animation restarts
        void bar.offsetWidth;
        bar.classList.add('active');

        // Atenuar el contenido principal para dar feedback instantáneo
        const main = document.querySelector('.fi-main');
        if (main) main.classList.add('navigating');
    }

    function endProgress() {
        bar.classList.remove('active');
        bar.classList.add('done');

        const main = document.querySelector('.fi-main');
        if (main) main.classList.remove('navigating');

        setTimeout(() => {
            bar.classList.remove('done');
            bar.style.width = '0%';
            bar.style.opacity = '0';
        }, 400);
    }

    // ── 2. INTERCEPTAR NAVEGACIÓN LIVEWIRE/SPA ──
    // Livewire 3 emite eventos cuando navega
    document.addEventListener('livewire:navigate', startProgress);
    document.addEventListener('livewire:navigated', endProgress);

    // Fallback: interceptar clics en enlaces del sidebar
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('http')) return;

        // Solo activar para navegación interna del panel
        if (href.startsWith('/admin') || link.hasAttribute('wire:navigate')) {
            startProgress();
            // Safety: si en 6s no terminó, forzar cierre
            setTimeout(endProgress, 6000);
        }
    }, true);

    // ── 3. PREFETCH EN HOVER (como Next.js) ──
    const prefetchedUrls = new Set();

    function prefetchUrl(url) {
        if (prefetchedUrls.has(url)) return;
        prefetchedUrls.add(url);

        // Usar <link rel="prefetch"> para que el navegador descargue en segundo plano
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        link.as = 'document';
        document.head.appendChild(link);
    }

    // Detectar hover en los links del sidebar
    document.addEventListener('mouseover', function (e) {
        const link = e.target.closest('.fi-sidebar-item a[href], .fi-sidebar-item-button');
        if (!link) return;

        const anchor = link.tagName === 'A' ? link : link.closest('a[href]');
        if (!anchor) return;

        const href = anchor.getAttribute('href');
        if (href && href.startsWith('/admin') && !href.includes('#')) {
            prefetchUrl(href);
        }
    }, { passive: true });

    // ── 4. DETECTAR PAGE LOAD COMPLETO ──
    // Si la página cargó por primera vez (no SPA), finalizar la barra
    window.addEventListener('load', endProgress);

    // Si Livewire hace request AJAX (formularios, acciones), mostrar progreso
    document.addEventListener('livewire:loading', startProgress);
    document.addEventListener('livewire:load', endProgress);

})();
