<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Live Driver Tracking</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    body, html { 
        height: 100%; 
        margin: 0; 
        padding: 0; 
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    #map { 
        height: 100%; 
        width: 100%; 
    }
    
    .driver-panel { 
        position: absolute; 
        top: 10px; 
        left: 10px; 
        z-index: 1000; 
        background: white; 
        padding: 15px; 
        border-radius: 8px; 
        max-height: 80%; 
        overflow-y: auto; 
        width: 280px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        border: 1px solid #e0e0e0;
    }
    
    .panel-header {
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    
    .driver-card { 
        border-left: 4px solid #28a745; 
        padding: 12px; 
        margin-bottom: 8px; 
        cursor: pointer; 
        border-radius: 5px; 
        background: #f8f9fa;
        transition: all 0.2s ease;
    }
    
    .driver-card:hover {
        background: #e9ecef;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .driver-card.offline { 
        border-left-color: #dc3545; 
        opacity: 0.7; 
    }
    
    .driver-card.busy { 
        border-left-color: #ffc107; 
    }
    
    .driver-card.available {
        border-left-color: #17a2b8;
    }
    
    .driver-name { 
        font-weight: 600; 
        font-size: 14px;
        color: #333;
    }
    
    .driver-status { 
        font-size: 11px; 
        font-weight: bold; 
        padding: 3px 8px; 
        border-radius: 12px; 
        color: white; 
        margin-left: 8px; 
        text-transform: uppercase;
    }
    
    .status-online { background: #28a745; }
    .status-available { background: #17a2b8; }
    .status-offline { background: #dc3545; }
    .status-busy { background: #ffc107; color: #000; }
    
    .driver-phone {
        font-size: 12px;
        color: #666;
        margin-top: 4px;
    }
    
    .last-update {
        font-size: 11px;
        color: #999;
        margin-top: 2px;
    }
    
    .controls {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 1000;
        background: white;
        padding: 10px;
        border-radius: 5px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    
    .refresh-btn {
        background: #007bff;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .refresh-btn:hover {
        background: #0056b3;
    }
    
    .stats {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    
    /* Custom marker styles */
    .custom-marker {
        background: transparent;
        border: none;
    }
    
    .marker-pin {
        position: relative;
        width: 30px;
        height: 30px;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        border: 2px solid white;
    }
    
    .marker-text {
        transform: rotate(45deg);
        font-weight: bold;
        font-size: 12px;
        color: white;
        text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
    }
    
    .marker-available {
        background: #28a745;
    }
    
    .marker-busy {
        background: #ffc107;
    }
    
    .marker-offline {
        background: #dc3545;
        opacity: 0.7;
    }
    
    .leaflet-tooltip {
        background: rgba(255, 255, 255, 0.95) !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
        padding: 4px 8px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        color: #333 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2) !important;
    }
</style>
</head>
<body>

<div class="driver-panel">
    <div class="panel-header">
        <h4><i class="fas fa-users"></i> Live Drivers <span id="driverCount" class="badge badge-primary">0</span></h4>
        <div class="stats">
            <div>Online: <span id="onlineCount">0</span></div>
            <div>Available: <span id="availableCount">0</span></div>
        </div>
    </div>
    <div id="driversList">Loading drivers...</div>
</div>

<div class="controls">
    <button class="refresh-btn" onclick="loadDrivers()">
        <i class="fas fa-sync-alt"></i> Refresh
    </button>
    <div class="stats">
        Last update: <span id="lastRefresh">--:--</span>
    </div>
</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Initialize map
let map = L.map('map').setView([33.8938, 35.5018], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
    attribution: '© OpenStreetMap contributors' 
}).addTo(map);

let markers = {};

// Custom icon creation function
function createCustomIcon(driver) {
    const status = driver.status || 'offline';
    const initial = driver.driver_name ? driver.driver_name.charAt(0).toUpperCase() : '?';
    
    let colorClass = 'marker-offline';
    switch(status) {
        case 'available':
        case 'online':
            colorClass = 'marker-available';
            break;
        case 'busy':
            colorClass = 'marker-busy';
            break;
        case 'offline':
        default:
            colorClass = 'marker-offline';
            break;
    }
    
    return L.divIcon({
        className: 'custom-marker',
        html: `<div class="marker-pin ${colorClass}">
                   <div class="marker-text">${initial}</div>
               </div>`,
        iconSize: [30, 40],
        iconAnchor: [15, 40],
        popupAnchor: [0, -40]
    });
}

async function loadDrivers() {
    console.log('Loading drivers...');
    
    try {
        const response = await fetch('track_location.php?action=get_all_locations');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Data received:', data);

        if (!data || !Array.isArray(data.drivers)) {
            throw new Error('Invalid data format received');
        }

        const driversList = document.getElementById('driversList');
        driversList.innerHTML = '';
        
        let totalCount = 0;
        let onlineCount = 0;
        let availableCount = 0;
        
        // Track current driver IDs
        const currentDriverIds = new Set();

        if (data.drivers.length === 0) {
            driversList.innerHTML = '<div style="color: #666; font-style: italic;">No drivers found</div>';
        } else {
            data.drivers.forEach(driver => {
                console.log('Processing driver:', driver);
                
                // Skip if no coordinates
                if (!driver.latitude || !driver.longitude) {
                    console.log('Skipping driver - no coordinates:', driver.driver_name);
                    return;
                }

                totalCount++;
                currentDriverIds.add(parseInt(driver.driver_id));
                
                const id = driver.driver_id;
                const lat = parseFloat(driver.latitude);
                const lng = parseFloat(driver.longitude);
                const status = driver.status || 'offline';
                
                console.log(`Driver ${driver.driver_name}: [${lat}, ${lng}] Status: ${status}`);
                
                // Count by status
                if (status === 'online' || status === 'available') {
                    onlineCount++;
                    if (status === 'available') {
                        availableCount++;
                    }
                }

                // Create marker
                const customIcon = createCustomIcon(driver);
                const tooltip = driver.driver_name;
                const popup = `
                    <div style="text-align: center;">
                        <strong>${driver.driver_name}</strong><br>
                        <small>Phone: ${driver.driver_phone}</small><br>
                        <span class="driver-status status-${status}">${status.toUpperCase()}</span><br>
                        <small>Last update: ${formatTime(driver.last_update)}</small>
                        ${driver.current_delivery ? `<br><small>Delivery: ${driver.current_delivery}</small>` : ''}
                    </div>
                `;

                if (markers[id]) {
                    // Update existing marker
                    markers[id].setLatLng([lat, lng])
                             .setIcon(customIcon)
                             .setPopupContent(popup)
                             .setTooltipContent(tooltip);
                } else {
                    // Create new marker
                    markers[id] = L.marker([lat, lng], {icon: customIcon})
                                  .addTo(map)
                                  .bindPopup(popup)
                                  .bindTooltip(tooltip, {
                                      permanent: true,
                                      direction: 'top',
                                      offset: [0, -45],
                                      className: 'driver-tooltip'
                                  });
                }

                // Add to sidebar
                const card = document.createElement('div');
                card.className = `driver-card ${status}`;
                card.innerHTML = `
                    <div class="driver-name">${driver.driver_name}
                        <span class="driver-status status-${status}">${status}</span>
                    </div>
                    <div class="driver-phone">${driver.driver_phone}</div>
                    <div class="last-update">Updated: ${formatTime(driver.last_update)}</div>
                    ${driver.current_delivery ? `<div class="last-update">Delivery: ${driver.current_delivery}</div>` : ''}
                `;
                
                card.addEventListener('click', function() { 
                    map.setView([lat, lng], 16); 
                    markers[id].openPopup(); 
                });
                
                driversList.appendChild(card);
            });
        }

        // Remove old markers
        Object.keys(markers).forEach(driverId => {
            if (!currentDriverIds.has(parseInt(driverId))) {
                console.log('Removing old marker for driver:', driverId);
                map.removeLayer(markers[driverId]);
                delete markers[driverId];
            }
        });

        // Update counts
        document.getElementById('driverCount').textContent = totalCount;
        document.getElementById('onlineCount').textContent = onlineCount;
        document.getElementById('availableCount').textContent = availableCount;
        document.getElementById('lastRefresh').textContent = new Date().toLocaleTimeString();
        
        console.log(`Loaded ${totalCount} drivers (${onlineCount} online, ${availableCount} available)`);
        
    } catch (error) {
        console.error('Error loading drivers:', error);
        document.getElementById('driversList').innerHTML = `
            <div style="color: red; padding: 10px; border: 1px solid red; border-radius: 4px; background: #ffe6e6;">
                <strong>Error:</strong> ${error.message}<br>
                <small>Check console for details</small><br>
                <button onclick="loadDrivers()" style="margin-top: 5px; padding: 2px 8px;">Retry</button>
            </div>
        `;
    }
}

function formatTime(dateString) {
    if (!dateString) return 'Never';
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffMinutes = Math.floor((now - date) / 1000 / 60);
        
        if (diffMinutes < 1) return 'Just now';
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        if (diffMinutes < 1440) return `${Math.floor(diffMinutes/60)}h ago`;
        return date.toLocaleDateString();
    } catch (e) {
        return dateString;
    }
}

// Auto-refresh every 10 seconds
loadDrivers();
const refreshInterval = setInterval(loadDrivers, 10000);

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

</body>
</html>