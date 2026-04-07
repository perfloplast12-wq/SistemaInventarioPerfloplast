<div class="flex items-center justify-center py-6">
    <!-- El contenedor asume EXACTAMENTE el color de fondo nativo del logo (#f3f4f6) 
         Creando una placa/pill perfecta sin necesidad de filtros destructivos ni recortes -->
    <div class="logo-brand-plaque">
        <img src="{{ asset('images/logo-perfloplast-premium.png') }}?v={{ time() }}" 
             alt="Perflo-Plast Logo" 
             class="fi-logo-img transition-all duration-300">
    </div>
</div>

<style>
    .fi-simple-header { display: none !important; }

    /* ESTILO ADAPTATIVO: MODO CLARO (La Placa) */
    .logo-brand-plaque {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        background-color: #f3f4f6; /* Color exacto del fondo del PNG original */
        border-radius: 1.5rem; /* Bordes super redondeados tipo pill/placa */
        padding: 1rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), inset 0 2px 4px 0 rgb(255 255 255 / 0.8);
        border: 1px solid #e5e7eb;
    }

    .fi-logo-img {
        height: auto !important;
        max-height: 220px !important;
        width: auto !important;
        object-fit: contain;
        
        /* Renderizado nativo: 100% visibilidad real del diseñador sin hacks */
        mix-blend-mode: normal; 
        filter: none;
        
        image-rendering: auto;
        -webkit-font-smoothing: antialiased;
        transform: translateZ(0); 
    }

    /* ESTILO ADAPTATIVO: MODO OSCURO */
    html.dark .logo-brand-plaque {
        /* En oscuro, quitamos la placa, porque la imagen invertida funciona perfecta por sí sola */
        background-color: transparent;
        padding: 0;
        box-shadow: none;
        border: none;
    }

    html.dark .fi-logo-img {
        /* MODO OSCURO: Fondo gris se convierte en negro puro (invisble en screen).
           El texto oscuro se vuelve claro/neón (perfecto para modo oscuro). */
        mix-blend-mode: screen;
        filter: invert(1) hue-rotate(180deg) brightness(1.2) contrast(1.1);
    }



</style>
