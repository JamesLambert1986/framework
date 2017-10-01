// The SW will be shutdown when not in use to save memory,
// be aware that any global state is likely to disappear
console.log("SW startup");

var cacheName = 'v1:static';



self.addEventListener('install', function(event) {
	console.log("SW installed");
	event.waitUntil(
        caches.open(cacheName).then(function(cache) {
            return cache.addAll([
                './offline.html'
            ]).then(function() {
                self.skipWaiting();
            });
        })
    );
});

self.addEventListener('activate', function(event) {
  console.log("SW activated");
});

self.addEventListener('fetch', function(event) {
	console.log("Caught a fetch!");
	var request = event.request;
  
	event.respondWith(fetch(request).then(function(response) {
        
		
		caches.open(cacheName).then(function(cache) {
			return cache.addAll([
				'./offline.html'
			]).then(function() {
				self.skipWaiting();
			});
		})
		


		
        return response;
      }).catch(function(error) {

          return caches.match('./offline.html');
    }));
});