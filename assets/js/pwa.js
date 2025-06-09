// Register service worker for PWA functionality
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' })
      .then(registration => {
        console.log('Service Worker registered with scope:', registration.scope);
        // Force the page to be reloaded to ensure service worker takes control
        if (navigator.serviceWorker.controller) {
          window.location.reload();
        }
      })
      .catch(error => {
        console.error('Service Worker registration failed:', error);
      });
  });
}
