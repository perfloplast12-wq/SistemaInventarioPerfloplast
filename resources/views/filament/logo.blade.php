<div class="flex items-center justify-center py-6 logo-container">
    <div class="p-4 rounded-3xl" style="background: rgba(255,255,255,0.02); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.05);">
        <img src="{{ asset('images/logo-perfloplast-premium.png') }}?v={{ file_exists(public_path('images/logo-perfloplast-premium.png')) ? filemtime(public_path('images/logo-perfloplast-premium.png')) : time() }}" 
             alt="Perflo-Plast Logo" 
             class="fi-logo-img"
             style="height: auto; max-height: 220px; width: auto; object-fit: contain; image-rendering: -webkit-optimize-contrast; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3));">
    </div>
</div>

<style>
    /* Ocultar el heading/brand name en el login */
    .fi-simple-header { display: none !important; }
</style>
