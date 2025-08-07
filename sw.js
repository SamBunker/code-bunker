/**
 * Service Worker for Web Application Modernization Tracker
 * Provides offline functionality and caching for performance
 */

const CACHE_NAME = 'webapp-tracker-v1';
const STATIC_CACHE = 'static-v1';
const DYNAMIC_CACHE = 'dynamic-v1';

// Assets to cache immediately
const STATIC_ASSETS = [
    '/juniata/',
    '/juniata/assets/css/style.css',
    '/juniata/assets/js/main.min.js',
    '/juniata/pages/dashboard.php',
    '/juniata/pages/projects.php',
    '/juniata/pages/tasks.php',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                return cache.addAll(STATIC_ASSETS);
            })
            .catch(err => console.log('Cache install failed:', err))
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => {
                return Promise.all(keys
                    .filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => caches.delete(key))
                );
            })
    );
    self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    const { request } = event;
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip external requests (not same origin)
    if (!request.url.startsWith(self.location.origin) && 
        !request.url.startsWith('https://cdn.jsdelivr.net')) {
        return;
    }
    
    event.respondWith(
        caches.match(request)
            .then(response => {
                // Return cached version if available
                if (response) {
                    return response;
                }
                
                // Otherwise fetch from network and cache
                return fetch(request)
                    .then(fetchResponse => {
                        // Check if valid response
                        if (!fetchResponse || fetchResponse.status !== 200 || fetchResponse.type !== 'basic') {
                            return fetchResponse;
                        }
                        
                        // Clone response (can only be used once)
                        const responseToCache = fetchResponse.clone();
                        
                        // Determine cache type
                        const cacheName = STATIC_ASSETS.includes(request.url) ? STATIC_CACHE : DYNAMIC_CACHE;
                        
                        caches.open(cacheName)
                            .then(cache => {
                                // Only cache GET requests
                                if (request.method === 'GET') {
                                    cache.put(request, responseToCache);
                                }
                            });
                        
                        return fetchResponse;
                    });
            })
            .catch(() => {
                // Offline fallback
                if (request.destination === 'document') {
                    return caches.match('/juniata/pages/dashboard.php');
                }
            })
    );
});

// Background sync for offline actions (optional)
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(
            // Handle offline actions when connection is restored
            console.log('Background sync triggered')
        );
    }
});

// Push notifications (optional)
self.addEventListener('push', event => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/juniata/assets/icon-192x192.png',
            badge: '/juniata/assets/badge-72x72.png',
            vibrate: [100, 50, 100],
            data: {
                dateOfArrival: Date.now(),
                primaryKey: data.primaryKey
            },
            actions: [
                {
                    action: 'explore', 
                    title: 'View Details',
                    icon: '/juniata/assets/checkmark.png'
                },
                {
                    action: 'close', 
                    title: 'Close',
                    icon: '/juniata/assets/xmark.png'
                }
            ]
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click handler
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/juniata/pages/dashboard.php')
        );
    }
});