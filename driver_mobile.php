<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Location Tracker</title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Driver Tracker">
    <meta name="mobile-web-app-capable" content="yes">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: white;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.2);
            padding: 15px 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: 600;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px;
            gap: 20px;
        }
        
        .status-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .driver-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .driver-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .driver-id {
            opacity: 0.8;
            font-size: 14px;
        }
        
        .status-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .status-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .status-btn.online {
            background: #28a745;
            color: white;
        }
        
        .status-btn.offline {
            background: #dc3545;
            color: white;
        }
        
        .status-btn.busy {
            background: #ffc107;
            color: black;
        }
        
        .status-btn.available {
            background: #17a2b8;
            color: white;
        }
        
        .status-btn.inactive {
            background: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.7);
        }
        
        .location-info {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .info-label {
            opacity: 0.8;
        }
        
        .info-value {
            font-weight: 600;
        }
        
        .location-controls {
            display: flex;
            gap: 10px;
        }
        
        .control-btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .start-tracking {
            background: #28a745;
            color: white;
        }
        
        .stop-tracking {
            background: #dc3545;
            color: white;
        }
        
        .refresh-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .control-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .tracking-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #28a745;
            animation: pulse 2s infinite;
            display: none;
            z-index: 101;
        }
        
        /* Background survival indicator */
        .survival-indicator {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ffc107;
            animation: pulse 3s infinite;
            display: none;
            z-index: 101;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            text-align: center;
            display: none;
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            text-align: center;
            display: none;
        }
        
        .battery-info {
            margin-top: 10px;
            text-align: center;
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Background survival tips */
        .survival-tips {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.5);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            font-size: 12px;
        }
        
        .survival-tips h4 {
            margin-bottom: 10px;
            color: #ffc107;
        }
        
        .wake-lock-status {
            font-size: 11px;
            opacity: 0.7;
            text-align: center;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="tracking-indicator" id="trackingIndicator"></div>
    <div class="survival-indicator" id="survivalIndicator"></div>
    
    <div class="header">
        <h1>🚚 Driver Location Tracker</h1>
    </div>
    
    <div class="main-content">
        <div class="status-card">
            <div class="driver-info">
                <div class="driver-name" id="driverName">Driver Name</div>
                <div class="driver-id">ID: <span id="driverId">Not Set</span></div>
            </div>
            
            <div class="status-toggle">
                <button class="status-btn available" onclick="setStatus('available')">Available</button>
                <button class="status-btn busy inactive" onclick="setStatus('busy')">Busy</button>
                <button class="status-btn offline inactive" onclick="setStatus('offline')">Offline</button>
            </div>
            
            <div class="location-info" id="locationInfo">
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value" id="currentStatus">Offline</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Update:</span>
                    <span class="info-value" id="lastUpdate">Never</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Accuracy:</span>
                    <span class="info-value" id="accuracy">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Speed:</span>
                    <span class="info-value" id="speed">-</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Background:</span>
                    <span class="info-value" id="backgroundStatus">Inactive</span>
                </div>
            </div>
            
            <div class="location-controls">
                <button class="control-btn start-tracking" id="trackingBtn" onclick="toggleTracking()">
                    📍 Start Tracking
                </button>
                <button class="control-btn refresh-btn" onclick="sendLocationNow()">
                    🔄 Update Now
                </button>
            </div>
            
            <div class="battery-info" id="batteryInfo">
                Battery: --
            </div>
            
            <div class="wake-lock-status" id="wakeLockStatus">
                Screen Wake: Inactive
            </div>
            
            <div class="error-message" id="errorMessage"></div>
            <div class="success-message" id="successMessage"></div>
        </div>
        
        <div class="status-card survival-tips">
            <h4>📱 Keep App Active Tips:</h4>
            <ul style="margin-left: 15px; font-size: 11px;">
                <li>Keep this tab open and active</li>
                <li>Don't close the browser</li>
                <li>Charge your device or keep it plugged in</li>
                <li>Disable "Background App Refresh" restrictions</li>
                <li>Add to home screen for better performance</li>
            </ul>
        </div>
    </div>

    <script>
    let isTracking = false;
    let trackingInterval;
    let heartbeatInterval;
    let wakeLockInterval;
    let watchId;
    let currentDriverId = null;
    let currentStatus = 'offline';
    let lastLocationData = null;
    let lastUpdateTime = 0;
    let wakeLock = null;
    let serviceWorkerRegistered = false;
    let backgroundSyncSupported = false;
    let queuedUpdates = [];
    
    const MIN_UPDATE_INTERVAL = 15000; // 15 seconds
    const MIN_DISTANCE_THRESHOLD = 10; // 10 meters
    const HEARTBEAT_INTERVAL = 30000; // 30 seconds
    const BACKGROUND_UPDATE_INTERVAL = 60000; // 1 minute when in background
    const FOREGROUND_UPDATE_INTERVAL = 30000; // 30 seconds when active

    // Initialize the app
    document.addEventListener('DOMContentLoaded', function() {
        initializeDriver();
        updateBatteryInfo();
        requestWakeLock();
        registerServiceWorker();
        setupBackgroundSurvival();
        setupVisibilityHandlers();
    });

    // Register Service Worker for background processing
    async function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('driver-sw.js');
                serviceWorkerRegistered = true;
                console.log('Service Worker registered:', registration);
                
                // Check for background sync support
                if ('sync' in window.ServiceWorkerRegistration.prototype) {
                    backgroundSyncSupported = true;
                    document.getElementById('backgroundStatus').textContent = 'SW Ready';
                }
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
    }

    // Request Screen Wake Lock to prevent screen from turning off
    async function requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
                document.getElementById('wakeLockStatus').textContent = 'Screen Wake: Active';
                
                wakeLock.addEventListener('release', () => {
                    document.getElementById('wakeLockStatus').textContent = 'Screen Wake: Released';
                });
            } catch (err) {
                console.error('Wake Lock request failed:', err);
                document.getElementById('wakeLockStatus').textContent = 'Screen Wake: Not Supported';
            }
        }
    }

    // Setup background survival mechanisms
    function setupBackgroundSurvival() {
        // Prevent page from being unloaded
        window.addEventListener('beforeunload', handleBeforeUnload);
        
        // Handle page focus/blur
        window.addEventListener('focus', handlePageFocus);
        window.addEventListener('blur', handlePageBlur);
        
        // Prevent right-click and text selection (optional)
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('selectstart', e => e.preventDefault());
        
        // Keep the page "alive" with periodic activity
        setInterval(() => {
            // Simulate user activity to prevent browser from sleeping the page
            document.dispatchEvent(new Event('mousemove'));
            
            // Re-request wake lock if released
            if (wakeLock && wakeLock.released && isTracking) {
                requestWakeLock();
            }
        }, 30000);
    }

    function setupVisibilityHandlers() {
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                handlePageBackground();
            } else {
                handlePageForeground();
            }
        });
    }

    function handlePageBackground() {
        console.log('App went to background');
        document.getElementById('survivalIndicator').style.display = 'block';
        document.getElementById('backgroundStatus').textContent = 'Background Mode';
        
        if (isTracking) {
            // Reduce update frequency to conserve battery
            if (trackingInterval) {
                clearInterval(trackingInterval);
                trackingInterval = setInterval(() => {
                    forceLocationUpdate();
                }, BACKGROUND_UPDATE_INTERVAL);
            }
            
            // Start heartbeat to keep connection alive
            startHeartbeat();
        }
    }

    function handlePageForeground() {
        console.log('App returned to foreground');
        document.getElementById('survivalIndicator').style.display = 'none';
        document.getElementById('backgroundStatus').textContent = 'Active Mode';
        
        if (isTracking) {
            // Resume normal update frequency
            if (trackingInterval) {
                clearInterval(trackingInterval);
                trackingInterval = setInterval(() => {
                    forceLocationUpdate();
                }, FOREGROUND_UPDATE_INTERVAL);
            }
            
            // Send any queued updates
            processQueuedUpdates();
            
            // Re-request wake lock
            requestWakeLock();
        }
        
        stopHeartbeat();
    }

    function handlePageFocus() {
        console.log('Page focused');
        if (isTracking) {
            forceLocationUpdate();
        }
    }

    function handlePageBlur() {
        console.log('Page blurred');
        // Page lost focus but may still be visible
    }

    function startHeartbeat() {
        if (heartbeatInterval) return;
        
        heartbeatInterval = setInterval(() => {
            // Send heartbeat to server to maintain connection
            fetch('track_location.php?action=heartbeat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    driver_id: currentDriverId,
                    timestamp: Date.now()
                })
            }).catch(err => console.log('Heartbeat failed:', err));
        }, HEARTBEAT_INTERVAL);
    }

    function stopHeartbeat() {
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
            heartbeatInterval = null;
        }
    }

    function handleBeforeUnload(event) {
        if (isTracking && currentDriverId) {
            // Send offline status before leaving
            const locationData = {
                driver_id: currentDriverId,
                latitude: lastLocationData ? lastLocationData.latitude : 0,
                longitude: lastLocationData ? lastLocationData.longitude : 0,
                status: 'offline',
                timestamp: Date.now()
            };
            
            // Use sendBeacon for better reliability during page unload
            navigator.sendBeacon('track_location.php?action=update_location', 
                JSON.stringify(locationData));
            
            event.preventDefault();
            event.returnValue = 'Are you sure you want to stop tracking?';
            return event.returnValue;
        }
    }

    function initializeDriver() {
        // Get driver info from URL parameters or localStorage
        const urlParams = new URLSearchParams(window.location.search);
        const driverIdFromUrl = urlParams.get('driver_id');
        const driverNameFromUrl = urlParams.get('driver_name');
        
        if (driverIdFromUrl) {
            currentDriverId = driverIdFromUrl;
            localStorage.setItem('driver_id', currentDriverId);
        } else {
            currentDriverId = localStorage.getItem('driver_id');
        }
        
        if (driverNameFromUrl) {
            localStorage.setItem('driver_name', driverNameFromUrl);
        }
        
        const driverName = localStorage.getItem('driver_name') || 'Unknown Driver';
        
        document.getElementById('driverId').textContent = currentDriverId || 'Not Set';
        document.getElementById('driverName').textContent = driverName;
        
        if (!currentDriverId) {
            showError('Driver ID not set. Please contact your dispatcher.');
            return;
        }
        
        // Check if geolocation is supported
        if (!navigator.geolocation) {
            showError('Geolocation is not supported by this browser.');
            return;
        }
        
        // Request permission and get initial location
        getCurrentLocation();
    }

    function setStatus(status) {
        const oldStatus = currentStatus;
        currentStatus = status;
        
        // Update button appearances
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.classList.add('inactive');
            btn.classList.remove('online', 'offline', 'busy', 'available');
        });
        
        const activeBtn = document.querySelector(`[onclick="setStatus('${status}')"]`);
        activeBtn.classList.remove('inactive');
        activeBtn.classList.add(status);
        
        document.getElementById('currentStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
        
        // Send status update immediately only if status actually changed
        if (oldStatus !== status && isTracking) {
            forceLocationUpdate();
        }
    }

    function toggleTracking() {
        if (isTracking) {
            stopTracking();
        } else {
            startTracking();
        }
    }

    function startTracking() {
        if (!currentDriverId) {
            showError('Driver ID not set. Cannot start tracking.');
            return;
        }
        
        const options = {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 30000
        };
        
        watchId = navigator.geolocation.watchPosition(
            onLocationSuccess,
            onLocationError,
            options
        );
        
        isTracking = true;
        document.getElementById('trackingIndicator').style.display = 'block';
        
        const btn = document.getElementById('trackingBtn');
        btn.textContent = '⏹️ Stop Tracking';
        btn.className = 'control-btn stop-tracking';
        
        // Send periodic updates
        trackingInterval = setInterval(() => {
            forceLocationUpdate();
        }, document.hidden ? BACKGROUND_UPDATE_INTERVAL : FOREGROUND_UPDATE_INTERVAL);
        
        // Request wake lock to keep screen on
        requestWakeLock();
        
        showSuccess('Location tracking started - Keep this page open!');
    }

    function stopTracking() {
        if (watchId) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        
        if (trackingInterval) {
            clearInterval(trackingInterval);
            trackingInterval = null;
        }
        
        stopHeartbeat();
        
        isTracking = false;
        document.getElementById('trackingIndicator').style.display = 'none';
        document.getElementById('survivalIndicator').style.display = 'none';
        
        const btn = document.getElementById('trackingBtn');
        btn.textContent = '📍 Start Tracking';
        btn.className = 'control-btn start-tracking';
        
        // Send offline status
        currentStatus = 'offline';
        setStatus('offline');
        
        // Release wake lock
        if (wakeLock) {
            wakeLock.release();
            wakeLock = null;
        }
        
        showSuccess('Location tracking stopped');
    }

    function getCurrentLocation() {
                // Update the options in your getCurrentLocation function
            const options = {
            enableHighAccuracy: true,
            timeout: 30000, // Increased timeout
            maximumAge: 0   // Force fresh location
        };
        navigator.geolocation.getCurrentPosition(
            onLocationSuccess,
            onLocationError,
            options
        );
    }

    function onLocationSuccess(position) {
    // Better speed handling
    let speed = position.coords.speed;
    
    // Handle null/undefined speed values
    if (speed === null || speed === undefined || speed < 0) {
        speed = 0;
    }
    
    // Round speed to 2 decimal places
    speed = Math.round(speed * 100) / 100;
    
    const locationData = {
        driver_id: currentDriverId,
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy,
        heading: position.coords.heading,
        speed: speed, // Now always a valid number
        status: currentStatus,
        battery_level: getBatteryLevel(),
        timestamp: Date.now()
    };
    
    updateLocationDisplay(locationData);
    
    // Check if we should send this update
    if (shouldSendLocationUpdate(locationData)) {
        sendLocationToServer(locationData);
        lastLocationData = locationData;
        lastUpdateTime = Date.now();
    } else if (document.hidden) {
        // Queue update for later if in background
        queuedUpdates.push(locationData);
    }
}



    // Also update the shouldSendLocationUpdate function:
function shouldSendLocationUpdate(newLocation) {
    const now = Date.now();
    
    // Always send if no previous location
    if (!lastLocationData) {
        return true;
    }
    
    // Always send if minimum time interval hasn't passed
    if (now - lastUpdateTime < MIN_UPDATE_INTERVAL) {
        return false;
    }
    
    // Always send if status changed
    if (newLocation.status !== lastLocationData.status) {
        return true;
    }
    
    // Check if moved significant distance
    const distance = calculateDistance(
        lastLocationData.latitude, 
        lastLocationData.longitude,
        newLocation.latitude, 
        newLocation.longitude
    );
    
    if (distance >= MIN_DISTANCE_THRESHOLD) {
        return true;
    }
    
    // Better speed change detection with null safety
    const oldSpeed = lastLocationData.speed || 0;
    const newSpeed = newLocation.speed || 0;
    const speedDiff = Math.abs(newSpeed - oldSpeed);
    
    // Send if speed changed by more than 5 km/h (1.4 m/s)
    if (speedDiff > 1.4) {
        return true;
    }
    
    return false;
}

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Earth's radius in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    function forceLocationUpdate() {
        // Force an update regardless of time/distance thresholds
        lastUpdateTime = 0;
        getCurrentLocation();
    }

    function processQueuedUpdates() {
        if (queuedUpdates.length > 0) {
            const latestUpdate = queuedUpdates[queuedUpdates.length - 1];
            sendLocationToServer(latestUpdate);
            queuedUpdates = [];
        }
    }

    function onLocationError(error) {
        let errorMessage = 'Location error: ';
        
        switch(error.code) {
            case error.PERMISSION_DENIED:
                errorMessage += 'Permission denied. Please allow location access.';
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage += 'Position unavailable. Please check your GPS.';
                break;
            case error.TIMEOUT:
                errorMessage += 'Location request timed out. Trying again...';
                setTimeout(getCurrentLocation, 5000);
                break;
            default:
                errorMessage += 'Unknown error occurred.';
                break;
        }
        
        showError(errorMessage);
    }

    // Update the display function for better speed formatting:
function updateLocationDisplay(data) {
    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
    document.getElementById('accuracy').textContent = data.accuracy ? `${Math.round(data.accuracy)}m` : '-';
    
    // Better speed display
    const speedKmh = (data.speed || 0) * 3.6;
    document.getElementById('speed').textContent = `${Math.round(speedKmh)} km/h`;
}

    function sendLocationToServer(locationData) {
        fetch('track_location.php?action=update_location', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(locationData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Location updated successfully');
            } else {
                throw new Error(data.error || 'Failed to update location');
            }
        })
        .catch(error => {
            console.error('Error sending location:', error);
            
            // Queue for background sync if supported
            if (backgroundSyncSupported && 'serviceWorker' in navigator) {
                navigator.serviceWorker.ready.then(registration => {
                    return registration.sync.register('location-sync');
                }).catch(err => console.log('Background sync registration failed:', err));
            }
            
            showError('Failed to send location update');
        });
    }

    function sendLocationNow() {
        if (currentDriverId) {
            forceLocationUpdate();
        } else {
            showError('Driver ID not set');
        }
    }

    function updateBatteryInfo() {
        if ('getBattery' in navigator) {
            navigator.getBattery().then(function(battery) {
                const level = Math.round(battery.level * 100);
                document.getElementById('batteryInfo').textContent = `Battery: ${level}%`;
                
                // Update battery info periodically
                battery.addEventListener('levelchange', () => {
                    const newLevel = Math.round(battery.level * 100);
                    document.getElementById('batteryInfo').textContent = `Battery: ${newLevel}%`;
                });
            });
        } else {
            document.getElementById('batteryInfo').textContent = 'Battery: Unknown';
        }
    }

    function getBatteryLevel() {
        // This would need proper battery API implementation
        return null;
    }

    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    function showSuccess(message) {
        const successDiv = document.getElementById('successMessage');
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        
        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 3000);
    }
    </script>
</body>
</html>