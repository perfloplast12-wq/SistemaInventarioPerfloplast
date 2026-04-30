(function() {
    const STORAGE_KEY = 'gps_last_success';
    const LOCK_KEY = 'gps_is_locked';
    
    const getStorageTime = () => parseInt(localStorage.getItem(STORAGE_KEY)) || 0;
    const setStorageTime = (time) => localStorage.setItem(STORAGE_KEY, time);

    const initTracker = () => {
        if (window.trackerStarted) return;
        window.trackerStarted = true;

        const config = window.trackerConfig || {};
        let watchId = null;
        
        const updateLockUI = (isLocked) => {
            const overlay = document.getElementById('gps-lock-overlay');
            if (overlay) {
                overlay.style.setProperty('display', isLocked ? 'flex' : 'none', 'important');
                if (isLocked) {
                    document.body.style.overflow = 'hidden';
                    // Ocultar el resto de la app para que no sea interactuable ni visible
                    const mainContent = document.querySelector('.fi-layout');
                    if (mainContent) mainContent.style.filter = 'blur(10px) brightness(0.2)';
                } else {
                    document.body.style.overflow = '';
                    const mainContent = document.querySelector('.fi-layout');
                    if (mainContent) mainContent.style.filter = '';
                }
            }
            localStorage.setItem(LOCK_KEY, isLocked ? '1' : '0');
        };

        const checkStatus = () => {
            const lastSuccess = getStorageTime();
            const now = Date.now();
            const diff = (now - lastSuccess) / 1000;
            
            // Si nunca ha habido éxito o han pasado más de 20s (bajamos a 20s para ser más estrictos)
            if (lastSuccess === 0 || diff > 20) {
                updateLockUI(true);
            } else {
                updateLockUI(false);
            }
            
            // Permisos
            if (navigator.permissions && navigator.permissions.query) {
                navigator.permissions.query({ name: 'geolocation' }).then(res => {
                    if (res.state === 'denied') updateLockUI(true);
                });
            }
        };

        const sendLocation = async (pos) => {
            if (!config.shouldTrack) return;
            try {
                const { latitude, longitude, speed, heading, accuracy } = pos.coords;
                await fetch('/api/tracking', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        dispatch_id: config.dispatchId,
                        lat: latitude,
                        lng: longitude,
                        speed: speed,
                        heading: heading,
                        accuracy: accuracy,
                        status: 'online'
                    })
                });
            } catch (e) {}
        };

        const startWatch = () => {
            if (watchId) navigator.geolocation.clearWatch(watchId);
            
            watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    setStorageTime(Date.now());
                    updateLockUI(false);
                    sendLocation(pos);
                },
                (err) => {
                    console.error('GPS Watch Error:', err);
                    updateLockUI(true);
                },
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        };

        // Inyectar CSS preventivo para que no se vea nada antes de que JS decida
        const style = document.createElement('style');
        style.innerHTML = `
            #gps-lock-overlay { z-index: 2147483647 !important; }
            body.is-gps-locked .fi-layout { display: none !important; }
        `;
        document.head.appendChild(style);

        // EJECUCIÓN INMEDIATA
        checkStatus();
        startWatch();

        // Bucle rápido (cada 2s)
        setInterval(checkStatus, 2000);
        
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                checkStatus();
                startWatch();
            }
        });
        
        window.retryGpsConnection = (e) => {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            console.log('Retry requested...');
            startWatch();
            // Intentar forzar el prompt
            navigator.geolocation.getCurrentPosition(() => {
                setStorageTime(Date.now());
                updateLockUI(false);
            }, (err) => {
                updateLockUI(true);
            }, { enableHighAccuracy: true, timeout: 5000 });
            return false;
        };
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTracker);
    } else {
        initTracker();
    }
})();
