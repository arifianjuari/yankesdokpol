<?php
/**
 * PWA Install Button Component
 * 
 * This file contains the button to install the PWA
 * Include this file where you want the install button to appear
 * 
 * @package YankesDokpol
 * @version 1.0
 */
?>
<div class="pwa-install-container">
    <button id="pwa-install-button" class="btn btn-primary btn-sm position-fixed bottom-0 end-0 m-3 d-flex align-items-center" style="z-index: 1050; display: none;">
        <i class="bi bi-download me-2"></i> Instal Aplikasi
    </button>
</div>

<script>
// Variabel untuk menyimpan event prompt instalasi
let deferredPrompt;

// Fungsi untuk menampilkan tombol install
function showInstallButton() {
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'flex';
    }
}

// Fungsi untuk menyembunyikan tombol install
function hideInstallButton() {
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'none';
    }
}

// Cek apakah aplikasi sudah diinstal sebagai PWA
function isInstalledPWA() {
    return window.matchMedia('(display-mode: standalone)').matches || 
           window.navigator.standalone === true;
}

document.addEventListener('DOMContentLoaded', function() {
    const installButton = document.getElementById('pwa-install-button');
    
    // Sembunyikan tombol jika aplikasi sudah diinstal
    if (isInstalledPWA()) {
        hideInstallButton();
    }
    
    // Tambahkan event listener untuk tombol install
    if (installButton) {
        installButton.addEventListener('click', function() {
            if (deferredPrompt) {
                // Sembunyikan tombol saat prompt ditampilkan
                hideInstallButton();
                
                // Tampilkan prompt instalasi
                deferredPrompt.prompt();
                
                // Tunggu respons pengguna
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Aplikasi berhasil diinstal');
                        // Tombol tetap disembunyikan setelah instalasi
                    } else {
                        console.log('Instalasi ditolak');
                        // Tampilkan tombol lagi jika instalasi ditolak
                        showInstallButton();
                    }
                    // Reset variabel prompt
                    deferredPrompt = null;
                });
            }
        });
    }
});

// Tangkap event beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    // Cegah browser menampilkan prompt secara otomatis
    e.preventDefault();
    
    // Simpan event untuk digunakan nanti
    deferredPrompt = e;
    
    // Tampilkan tombol install
    showInstallButton();
});

// Tangkap event appinstalled
window.addEventListener('appinstalled', (evt) => {
    console.log('Aplikasi berhasil diinstal');
    // Sembunyikan tombol install setelah aplikasi diinstal
    hideInstallButton();
});
</script>
