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

        // Para pilotos (conductores): consultar periódicamente el despacho activo
        // y actualizar config.dispatchId sin recargar la página
        const syncActiveDispatch = async () => {
            try {
                const resp = await fetch('/api/my-active-dispatch', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!resp.ok) return;
                const data = await resp.json();
                if (data.dispatch_id && data.dispatch_id !== config.dispatchId) {
                    console.log('[Tracker] Despacho activo actualizado:', data.dispatch_id);
                    config.dispatchId = data.dispatch_id;
                    config.shouldTrack = true;
                }
                return data.dispatch_id || null;
            } catch (e) {}
            return null;
        };
        
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
            
            // Si nunca ha habido éxito o han pasado más de 60s (generoso para desktop)
            if (lastSuccess === 0 || diff > 60) {
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
            if (!config.shouldTrack) return false;
            try {
                const { latitude, longitude, speed, heading, accuracy } = pos.coords;
                const resp = await fetch('/api/tracking', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
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
                return resp.ok;
            } catch (e) {}
            return false;
        };

        const requestCurrentPosition = (highAccuracy = true) => {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation not supported'));
                    return;
                }

                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: highAccuracy,
                    timeout: 15000,
                    maximumAge: highAccuracy ? 0 : 60000
                });
            });
        };

        const requestPositionWithFallback = async () => {
            try {
                return await requestCurrentPosition(true);
            } catch (e) {
                console.log('[Tracker] High accuracy failed, trying low accuracy...');
                return await requestCurrentPosition(false);
            }
        };

        const startWatch = () => {
            if (watchId) navigator.geolocation.clearWatch(watchId);
            
            // Intentar primero con high accuracy, si falla reintentar sin ella
            const tryWatch = (highAccuracy) => {
                if (watchId) navigator.geolocation.clearWatch(watchId);
                watchId = navigator.geolocation.watchPosition(
                    (pos) => {
                        setStorageTime(Date.now());
                        updateLockUI(false);
                        sendLocation(pos);
                    },
                    (err) => {
                        console.error('GPS Watch Error:', err);
                        if (highAccuracy) {
                            console.log('[Tracker] Watch high accuracy failed, retrying with low accuracy...');
                            setTimeout(() => tryWatch(false), 1000);
                        } else {
                            // Incluso con low accuracy falló — mostrar lock pero reintentar
                            updateLockUI(true);
                            setTimeout(() => tryWatch(false), 10000);
                        }
                    },
                    { enableHighAccuracy: highAccuracy, timeout: 15000, maximumAge: highAccuracy ? 0 : 60000 }
                );
            };
            tryWatch(true);
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

        // Sincronizar despacho activo para pilotos: al inicio y cada 30s
        syncActiveDispatch();
        setInterval(syncActiveDispatch, 30000);

        // Bucle rápido (cada 2s)
        setInterval(checkStatus, 2000);
        
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                checkStatus();
                startWatch();
                syncActiveDispatch(); // También al volver a la pestaña
            }
        });
        
        window.retryGpsConnection = async (e) => {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            config.shouldTrack = true;
            await syncActiveDispatch();

            try {
                // Intentar con high accuracy, fallback a low accuracy (funciona en desktop)
                const pos = await requestPositionWithFallback();
                const saved = await sendLocation(pos);
                setStorageTime(Date.now());
                updateLockUI(!saved && !!config.dispatchId);
                startWatch();
            } catch (err) {
                console.error('GPS retry error:', err);
                // Incluso con fallback, si falló, mostrar mensaje más amigable
                updateLockUI(true);
                // Reintentar watch automáticamente con low accuracy
                setTimeout(() => startWatch(), 2000);
            }

            return false;
        };

        window.dismissGpsLock = (e) => {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            config.shouldTrack = false;
            if (watchId) navigator.geolocation.clearWatch(watchId);
            updateLockUI(false);
            setStorageTime(Date.now() + 86400000); // 1 día en el futuro para que no vuelva a molestar
        };
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTracker);
    } else {
        initTracker();
    }
})();
