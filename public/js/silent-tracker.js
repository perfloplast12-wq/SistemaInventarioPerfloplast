(function() {
    const STORAGE_KEY = 'gps_last_success';
    const LOCK_KEY = 'gps_is_locked';
    
    const getStorageTime = () => parseInt(localStorage.getItem(STORAGE_KEY)) || Date.now();
    const setStorageTime = (time) => localStorage.setItem(STORAGE_KEY, time);

    const initTracker = () => {
        if (window.trackerStarted) return;
        window.trackerStarted = true;

        const config = window.trackerConfig || {};
        let watchId = null;
        let lastSuccess = getStorageTime();
        
        const updateLockUI = (isLocked) => {
            const overlay = document.getElementById('gps-lock-overlay');
            if (overlay) {
                overlay.style.display = isLocked ? 'flex' : 'none';
            }
            localStorage.setItem(LOCK_KEY, isLocked ? '1' : '0');
        };

        const checkStatus = () => {
            const now = Date.now();
            const diff = (now - lastSuccess) / 1000;
            
            // Si pasan más de 30s sin señal, bloqueamos
            if (diff > 30) {
                updateLockUI(true);
            }
            
            // Si el permiso está denegado, bloqueo inmediato
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
                    lastSuccess = Date.now();
                    setStorageTime(lastSuccess);
                    updateLockUI(false);
                    sendLocation(pos);
                },
                (err) => {
                    updateLockUI(true);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        };

        // Bucle de control cada 3 segundos
        setInterval(checkStatus, 3000);
        
        // Iniciar rastreo
        startWatch();

        // Reiniciar al volver a la app
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                checkStatus();
                startWatch();
            }
        });
        
        // Exponer función de reintento
        window.retryGpsConnection = () => {
            updateLockUI(false); // Reset temporal para permitir el prompt
            startWatch();
            navigator.geolocation.getCurrentPosition(() => {}, () => {});
        };
    };

    // Auto-init si estamos en el entorno adecuado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTracker);
    } else {
        initTracker();
    }
})();
