<?php
/**
 * PWA Test Page
 * 
 * This page tests the PWA functionality of the YankesDokpol application
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Set page title
$pageTitle = 'YankesDokpol - PWA Test';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <?php include 'includes/pwa_head.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pwa-test-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .status-card {
            margin-bottom: 20px;
        }
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .test-button {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="pwa-test-container">
            <h1 class="mb-4">YankesDokpol PWA Test</h1>
            
            <div class="card shadow status-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Status PWA</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="feature-icon text-primary">
                                <i class="bi bi-phone"></i>
                            </div>
                            <h5>Service Worker</h5>
                            <div id="sw-status">Memeriksa...</div>
                            <button class="btn btn-sm btn-outline-primary test-button" id="test-sw">Test Service Worker</button>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="feature-icon text-primary">
                                <i class="bi bi-cloud-arrow-down"></i>
                            </div>
                            <h5>Installable</h5>
                            <div id="install-status">Memeriksa...</div>
                            <button class="btn btn-sm btn-outline-primary test-button" id="test-install">Test Installable</button>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="feature-icon text-primary">
                                <i class="bi bi-wifi-off"></i>
                            </div>
                            <h5>Offline Mode</h5>
                            <div id="offline-status">Memeriksa...</div>
                            <button class="btn btn-sm btn-outline-primary test-button" id="test-offline">Test Offline</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Panduan Penggunaan PWA</h5>
                </div>
                <div class="card-body">
                    <h5>Apa itu PWA?</h5>
                    <p>Progressive Web App (PWA) adalah teknologi yang memungkinkan aplikasi web berjalan seperti aplikasi native dengan kemampuan:</p>
                    <ul>
                        <li>Dapat diinstal di perangkat</li>
                        <li>Dapat diakses secara offline</li>
                        <li>Memiliki ikon di layar utama perangkat</li>
                        <li>Berjalan dalam mode layar penuh tanpa URL browser</li>
                    </ul>
                    
                    <h5>Cara Menginstal YankesDokpol PWA:</h5>
                    <ol>
                        <li>Buka YankesDokpol di browser Chrome atau Safari</li>
                        <li>Di Chrome: Klik menu tiga titik > Instal Aplikasi</li>
                        <li>Di Safari (iOS): Klik tombol bagikan > Tambahkan ke Layar Utama</li>
                    </ol>
                    
                    <h5>Fitur Offline:</h5>
                    <p>YankesDokpol PWA dapat diakses bahkan ketika tidak ada koneksi internet. Beberapa fitur yang tersedia dalam mode offline:</p>
                    <ul>
                        <li>Melihat halaman yang sudah pernah dikunjungi</li>
                        <li>Mengisi formulir pendaftaran (akan disinkronkan saat online)</li>
                    </ul>
                </div>
            </div>
            
            <?php include 'includes/pwa_install_button.php'; ?>
            
            <footer class="mt-4 text-center">
                <p>&copy; <?php echo date('Y'); ?> RS Bhayangkara Batu - Sistem Pencatatan Peserta Kegiatan</p>
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/pwa.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const swStatus = document.getElementById('sw-status');
            const installStatus = document.getElementById('install-status');
            const offlineStatus = document.getElementById('offline-status');
            
            // Check Service Worker
            if ('serviceWorker' in navigator) {
                swStatus.innerHTML = '<span class="text-success">Didukung</span>';
                
                // Check if service worker is registered
                navigator.serviceWorker.getRegistration()
                    .then(registration => {
                        if (registration) {
                            swStatus.innerHTML = '<span class="text-success">Aktif</span>';
                        } else {
                            swStatus.innerHTML = '<span class="text-warning">Tidak Aktif</span>';
                        }
                    })
                    .catch(error => {
                        swStatus.innerHTML = '<span class="text-danger">Error: ' + error.message + '</span>';
                    });
            } else {
                swStatus.innerHTML = '<span class="text-danger">Tidak Didukung</span>';
            }
            
            // Check Installable
            if (window.matchMedia('(display-mode: standalone)').matches) {
                installStatus.innerHTML = '<span class="text-success">Sudah Terinstal</span>';
            } else if ('BeforeInstallPromptEvent' in window) {
                installStatus.innerHTML = '<span class="text-success">Siap Diinstal</span>';
            } else {
                installStatus.innerHTML = '<span class="text-warning">Tidak Dapat Diinstal</span>';
            }
            
            // Check Offline Capability
            if ('caches' in window) {
                caches.has('yankesdokpol-cache-v1')
                    .then(hasCache => {
                        if (hasCache) {
                            offlineStatus.innerHTML = '<span class="text-success">Siap</span>';
                        } else {
                            offlineStatus.innerHTML = '<span class="text-warning">Cache Belum Siap</span>';
                        }
                    })
                    .catch(error => {
                        offlineStatus.innerHTML = '<span class="text-danger">Error: ' + error.message + '</span>';
                    });
            } else {
                offlineStatus.innerHTML = '<span class="text-danger">Tidak Didukung</span>';
            }
            
            // Test buttons
            document.getElementById('test-sw').addEventListener('click', function() {
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.register('./sw.js')
                        .then(registration => {
                            alert('Service Worker berhasil didaftarkan dengan scope: ' + registration.scope);
                            swStatus.innerHTML = '<span class="text-success">Aktif</span>';
                        })
                        .catch(error => {
                            alert('Pendaftaran Service Worker gagal: ' + error);
                        });
                } else {
                    alert('Service Worker tidak didukung di browser ini');
                }
            });
            
            document.getElementById('test-install').addEventListener('click', function() {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then(choiceResult => {
                        if (choiceResult.outcome === 'accepted') {
                            alert('Aplikasi berhasil diinstal!');
                            installStatus.innerHTML = '<span class="text-success">Terinstal</span>';
                        } else {
                            alert('Instalasi dibatalkan oleh pengguna');
                        }
                        deferredPrompt = null;
                    });
                } else {
                    alert('Aplikasi tidak dapat diinstal saat ini. Pastikan Anda menggunakan browser yang mendukung dan belum menginstal aplikasi ini sebelumnya.');
                }
            });
            
            document.getElementById('test-offline').addEventListener('click', function() {
                if ('caches' in window) {
                    caches.open('yankesdokpol-cache-v1')
                        .then(cache => {
                            return cache.addAll([
                                '/',
                                '/index.php',
                                '/offline.html',
                                '/assets/css/style.css'
                            ]);
                        })
                        .then(() => {
                            alert('Cache berhasil dibuat! Anda dapat mencoba mode offline sekarang.');
                            offlineStatus.innerHTML = '<span class="text-success">Siap</span>';
                        })
                        .catch(error => {
                            alert('Gagal membuat cache: ' + error);
                        });
                } else {
                    alert('Cache API tidak didukung di browser ini');
                }
            });
        });
    </script>
</body>
</html>
