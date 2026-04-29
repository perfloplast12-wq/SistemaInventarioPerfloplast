<div class="flex items-center justify-center py-4">
    <div class="login-logo-container">
        <img src="{{ asset('images/logo-perfloplast-premium.png') }}" 
             alt="Perflo-Plast Logo" 
             width="280"
             height="110"
             fetchpriority="high"
             loading="eager"
             decoding="async"
             class="login-logo-img">
    </div>
</div>

<style>
    /* ===== HIDE DEFAULT FILAMENT HEADER ON LOGIN ===== */
    .fi-simple-header { display: none !important; }

    /* ===== LOGIN PAGE BACKGROUND ===== */
    .fi-simple-layout {
        min-height: 100vh !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: relative !important;
        overflow: hidden !important;
        background: 
            radial-gradient(ellipse at 20% 50%, rgba(0, 210, 190, 0.15) 0%, transparent 50%),
            radial-gradient(ellipse at 80% 20%, rgba(0, 150, 255, 0.1) 0%, transparent 40%),
            radial-gradient(ellipse at 60% 80%, rgba(0, 180, 216, 0.08) 0%, transparent 45%),
            linear-gradient(160deg, 
                #020617 0%, 
                #0a1628 25%, 
                #0d2137 50%, 
                #041825 75%, 
                #020617 100%
            ) !important;
    }

    /* Animated mesh gradient background - Dark Mode */
    .fi-simple-layout::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 0;
        background: 
            radial-gradient(ellipse at 10% 20%, rgba(0, 210, 190, 0.12) 0%, transparent 50%),
            radial-gradient(ellipse at 90% 80%, rgba(0, 180, 216, 0.08) 0%, transparent 50%),
            radial-gradient(ellipse at 50% 50%, rgba(0, 150, 136, 0.05) 0%, transparent 70%);
        animation: loginMeshFloat 12s ease-in-out infinite alternate;
    }

    /* Subtle grid overlay */
    .fi-simple-layout::after {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 0;
        background-image: 
            linear-gradient(rgba(0, 210, 190, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 210, 190, 0.03) 1px, transparent 1px);
        background-size: 60px 60px;
        pointer-events: none;
    }

    @keyframes loginMeshFloat {
        0% { transform: scale(1) translate(0, 0); }
        100% { transform: scale(1.1) translate(-2%, -1%); }
    }

    /* ===== LIGHT MODE BACKGROUND ===== */
    html:not(.dark) .fi-simple-layout {
        background: 
            radial-gradient(circle at top left, rgba(0, 180, 180, 0.15), transparent 40%),
            radial-gradient(ellipse at 80% 20%, rgba(0, 150, 200, 0.08) 0%, transparent 50%),
            linear-gradient(160deg,
                #f8fafc 0%,
                #e2e8f0 30%,
                #b0c4d8 55%,
                #1e3a5f 80%,
                #020617 100%
            ) !important;
    }

    html:not(.dark) .fi-simple-layout::before {
        background: 
            radial-gradient(ellipse at 10% 20%, rgba(0, 180, 180, 0.06) 0%, transparent 50%),
            radial-gradient(ellipse at 90% 10%, rgba(0, 150, 200, 0.04) 0%, transparent 50%),
            radial-gradient(ellipse at 50% 90%, rgba(0, 200, 180, 0.03) 0%, transparent 60%);
    }

    html:not(.dark) .fi-simple-layout::after {
        background-image: 
            linear-gradient(rgba(0, 80, 100, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0, 80, 100, 0.03) 1px, transparent 1px);
    }

    /* ===== ACCENT LINES (top-right / bottom-left) ===== */
    .fi-simple-main-ctn {
        position: relative !important;
        z-index: 1 !important;
    }

    /* ===== CARD CONTAINER (the form box) ===== */
    .fi-simple-main {
        position: relative !important;
        z-index: 1 !important;
        border-radius: 1.25rem !important;
        border: 1px solid rgba(0, 210, 190, 0.15) !important;
        background: rgba(10, 15, 26, 0.85) !important;
        backdrop-filter: blur(20px) saturate(1.3) !important;
        -webkit-backdrop-filter: blur(20px) saturate(1.3) !important;
        box-shadow: 
            0 0 0 1px rgba(0, 210, 190, 0.05),
            0 25px 50px -12px rgba(0, 0, 0, 0.5),
            0 0 80px -20px rgba(0, 210, 190, 0.1) !important;
        padding: 2rem !important;
        max-width: 420px !important;
        width: 100% !important;
        overflow: visible !important;
    }

    /* Light mode card */
    html:not(.dark) .fi-simple-main {
        background: rgba(255, 255, 255, 0.92) !important;
        border: 1px solid rgba(0, 180, 180, 0.12) !important;
        box-shadow: 
            0 0 0 1px rgba(0, 180, 180, 0.05),
            0 25px 50px -12px rgba(0, 0, 0, 0.08),
            0 0 60px -15px rgba(0, 180, 180, 0.06) !important;
    }

    /* Top accent glow bar */
    .fi-simple-main::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60%;
        height: 3px;
        background: linear-gradient(90deg, transparent, #00d2be, #00b4d8, transparent);
        border-radius: 0 0 4px 4px;
    }

    /* ===== LOGO CONTAINER ===== */
    .login-logo-container {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 0.5rem;
    }

    .login-logo-img {
        height: auto !important;
        max-height: 140px !important;
        width: auto !important;
        max-width: 100% !important;
        object-fit: contain;
        image-rendering: auto;
        -webkit-font-smoothing: antialiased;
        transform: translateZ(0);
    }

    /* Dark mode: invert logo for dark backgrounds */
    html.dark .login-logo-img {
        mix-blend-mode: screen;
        filter: invert(1) hue-rotate(180deg) brightness(1.3) contrast(1.1);
    }

    /* Light mode: show logo naturally */
    html:not(.dark) .login-logo-img {
        mix-blend-mode: normal;
        filter: none;
    }

    /* ===== FORM FIELDS STYLING ===== */
    /* Input fields */
    .fi-simple-main .fi-input-wrp {
        border-radius: 0.75rem !important;
        border: 1px solid rgba(0, 210, 190, 0.2) !important;
        background: rgba(0, 210, 190, 0.04) !important;
        transition: all 0.3s ease !important;
        overflow: hidden !important;
    }

    .fi-simple-main .fi-input-wrp:focus-within {
        border-color: #00d2be !important;
        box-shadow: 0 0 0 3px rgba(0, 210, 190, 0.12), 0 0 20px -5px rgba(0, 210, 190, 0.15) !important;
        background: rgba(0, 210, 190, 0.06) !important;
    }

    html:not(.dark) .fi-simple-main .fi-input-wrp {
        border: 1px solid rgba(0, 180, 180, 0.2) !important;
        background: rgba(0, 180, 180, 0.03) !important;
    }

    html:not(.dark) .fi-simple-main .fi-input-wrp:focus-within {
        border-color: #00b4a0 !important;
        box-shadow: 0 0 0 3px rgba(0, 180, 160, 0.1), 0 0 15px -5px rgba(0, 180, 160, 0.1) !important;
    }

    .fi-simple-main .fi-input {
        background: transparent !important;
        color: #e6edf3 !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 0.9rem !important;
    }

    html:not(.dark) .fi-simple-main .fi-input {
        color: #1e293b !important;
    }

    .fi-simple-main .fi-input::placeholder {
        color: rgba(255, 255, 255, 0.35) !important;
        font-style: italic !important;
    }

    html:not(.dark) .fi-simple-main .fi-input::placeholder {
        color: rgba(0, 0, 0, 0.35) !important;
    }

    /* Labels */
    .fi-simple-main .fi-fo-field-wrp-label label {
        color: rgba(0, 210, 190, 0.9) !important;
        font-weight: 700 !important;
        font-size: 0.82rem !important;
        letter-spacing: 0.02em !important;
        font-family: 'Outfit', sans-serif !important;
    }

    html:not(.dark) .fi-simple-main .fi-fo-field-wrp-label label {
        color: #0d7377 !important;
    }

    /* Required asterisk */
    .fi-simple-main .fi-fo-field-wrp-label sup {
        color: #ef4444 !important;
    }

    /* ===== SUBMIT BUTTON ===== */
    .fi-simple-main .fi-btn {
        width: 100% !important;
        border-radius: 0.75rem !important;
        background: linear-gradient(135deg, #00d2be, #00b4d8) !important;
        border: none !important;
        font-family: 'Outfit', sans-serif !important;
        font-weight: 800 !important;
        font-size: 0.95rem !important;
        letter-spacing: 0.04em !important;
        text-transform: uppercase !important;
        padding: 0.85rem 1.5rem !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 15px -3px rgba(0, 210, 190, 0.4) !important;
        color: #ffffff !important;
    }

    .fi-simple-main .fi-btn:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 8px 25px -5px rgba(0, 210, 190, 0.5) !important;
        background: linear-gradient(135deg, #00e8d0, #00c8e8) !important;
    }

    .fi-simple-main .fi-btn:active {
        transform: translateY(0) scale(0.98) !important;
    }

    /* ===== CHECKBOX (Remember me) ===== */
    .fi-simple-main .fi-checkbox-input:checked {
        background-color: #00d2be !important;
        border-color: #00d2be !important;
    }

    .fi-simple-main .fi-fo-field-wrp label span {
        color: rgba(255, 255, 255, 0.6) !important;
        font-size: 0.82rem !important;
    }

    html:not(.dark) .fi-simple-main .fi-fo-field-wrp label span {
        color: rgba(0, 0, 0, 0.5) !important;
    }

    /* ===== INPUT ICON PREFIX ===== */
    .fi-simple-main .fi-input-wrp .fi-input-wrp-icon {
        color: rgba(0, 210, 190, 0.5) !important;
    }

    /* ===== SECURITY BADGE BELOW BUTTON ===== */
    .fi-simple-main .fi-form-actions + div,
    .fi-simple-main > form > div:last-child {
        text-align: center;
    }

    /* ===== CORNER DECORATIVE LINES ===== */
    .fi-simple-main::after {
        content: '';
        position: absolute;
        bottom: -30px;
        right: -30px;
        width: 120px;
        height: 120px;
        border: 2px solid rgba(0, 210, 190, 0.06);
        border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        transform: rotate(15deg);
        pointer-events: none;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 640px) {
        .fi-simple-main {
            margin: 1rem !important;
            padding: 1.5rem !important;
            border-radius: 1rem !important;
        }

        .login-logo-img {
            max-height: 110px !important;
        }
    }

    /* ===== SCROLLBAR HIDE ON LOGIN ===== */
    .fi-simple-layout::-webkit-scrollbar {
        display: none;
    }
    .fi-simple-layout {
        scrollbar-width: none;
    }
</style>
