// driver-sw.js - Service Worker for background operations
const CACHE_NAME = 'driver-app-v1';
const urlsToCache = [
    '/',
    '/driver_mobile.php',
    '/track_location.php'
];

// Install event - cache resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                return response || fetch(event.request);
            }
        )
    );
});

// Background Sync for location updates
self.addEventListener('sync', event => {
    if (event.tag === 'location-sync') {
        event.waitUntil(doLocationSync());
    }
});

async function doLocationSync() {
    try {
        // Get queued location data from IndexedDB
        const queuedData = await getQueuedLocationData();
        
        for (const locationData of queuedData) {
            try {
                const response = await fetch('track_location.php?action=update_location', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(locationData)
                });
                
                if (response.ok) {
                    // Remove from queue after successful send
                    await removeFromQueue(locationData.id);
                }
            } catch (error) {
                console.error('Failed to sync location:', error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// IndexedDB helpers for queueing
async function getQueuedLocationData() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('DriverLocationDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['locationQueue'], 'readonly');
            const store = transaction.objectStore('locationQueue');
            const getRequest = store.getAll();
            
            getRequest.onsuccess = () => resolve(getRequest.result || []);
            getRequest.onerror = () => reject(getRequest.error);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('locationQueue')) {
                db.createObjectStore('locationQueue', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

async function removeFromQueue(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('DriverLocationDB', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['locationQueue'], 'readwrite');
            const store = transaction.objectStore('locationQueue');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

// Push notifications for dispatcher messages
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'New message from dispatcher',
        icon: 'icon-192x192.png',
        badge: 'badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View',
                icon: 'images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: 'images/xmark.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('Driver App', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'explore') {
        // Open the app
        event.waitUntil(
            clients.openWindow('driver_mobile.php')
        );
    }
});

// Keep service worker alive with periodic tasks
let keepAliveInterval;

self.addEventListener('message', event => {
    if (event.data && event.data.type === 'KEEP_ALIVE') {
        // Respond to keep-alive ping from main thread
        event.ports[0].postMessage({ type: 'ALIVE' });
        
        // Start periodic background location tracking
        if (event.data.startTracking && !keepAliveInterval) {
            startBackgroundTracking();
        } else if (!event.data.startTracking && keepAliveInterval) {
            stopBackgroundTracking();
        }
    }
});

function startBackgroundTracking() {
    keepAliveInterval = setInterval(() => {
        // Try to get location even in background
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                position => {
                    const locationData = {
                        driver_id: null, // Will be set by main thread
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: Date.now(),
                        source: 'service-worker'
                    };
                    
                    // Queue for sync
                    queueLocationUpdate(locationData);
                },
                error => {
                    console.log('Background location failed:', error);
                }
            );
        }
    }, 60000); // Every minute
}

function stopBackgroundTracking() {
    if (keepAliveInterval) {
        clearInterval(keepAliveInterval);
        keepAliveInterval = null;
    }
}

async function queueLocationUpdate(locationData) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('DriverLocationDB', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['locationQueue'], 'readwrite');
            const store = transaction.objectStore('locationQueue');
            const addRequest = store.add(locationData);
            
            addRequest.onsuccess = () => resolve();
            addRequest.onerror = () => reject(addRequest.error);
        };
    });
}