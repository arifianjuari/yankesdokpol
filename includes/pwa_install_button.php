<?php
/**
 * PWA Install Button Component
 * 
 * This file contains the button to install the PWA
 * Include this file where you want the install button to appear
 * 
 * @package HUTBhayangkara79
 * @version 1.1
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
let isInstallButtonVisible = false;

// Fungsi untuk menampilkan tombol install
function showInstallButton() {
    if (isInstallButtonVisible) return;
    
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'flex';
        isInstallButtonVisible = true;
        console.log('Tombol instal ditampilkan');
    }
}

// Fungsi untuk menyembunyikan tombol install
function hideInstallButton() {
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.display = 'none';
        isInstallButtonVisible = false;
        console.log('Tombol instal disembunyikan');
    }
}

// Cek apakah aplikasi sudah diinstal sebagai PWA
function isInstalledPWA() {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                        window.navigator.standalone === true ||
                        document.referrer.includes('android-app://');
    
    if (isStandalone) {
        console.log('Aplikasi sudah diinstal sebagai PWA');
        return true;
    }
    
    // Cek apakah aplikasi diakses dari layar utama
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('Aplikasi diakses dari layar utama');
        return true;
    }
    
    return false;
}

// Inisialisasi tombol install
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM dimuat, memeriksa status instalasi...');
    
    const installButton = document.getElementById('pwa-install-button');
    
    // Sembunyikan tombol jika aplikasi sudah diinstal
    if (isInstalledPWA()) {
        hideInstallButton();
        return;
    }
    
    // Tambahkan event listener untuk tombol install
    if (installButton) {
        installButton.addEventListener('click', async () => {
            console.log('Tombol instal diklik');
            
            if (!deferredPrompt) {
                console.log('Prompt instalasi tidak tersedia');
                return;
            }
            
            try {
                // Tampilkan prompt instalasi
                console.log('Menampilkan prompt instalasi...');
                deferredPrompt.prompt();
                
                // Tunggu respons pengguna
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`Pengguna ${outcome} instalasi`);
                
                if (outcome === 'accepted') {
                    console.log('Aplikasi akan diinstal');
                    hideInstallButton();
                } else {
                    console.log('Pengguna menolak instalasi');
                }
                
            } catch (error) {
                console.error('Terjadi kesalahan saat menampilkan prompt:', error);
            } finally {
                // Reset variabel prompt
                deferredPrompt = null;
            }
        });
    }
});

// Tangkap event beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('beforeinstallprompt event terdeteksi');
    
    // Cegah browser menampilkan prompt secara otomatis
    e.preventDefault();
    
    // Simpan event untuk digunakan nanti
    deferredPrompt = e;
    
    // Tampilkan tombol install
    showInstallButton();
    
    // Debug: Log untuk memastikan event tertangkap
    console.log('Tombol instal seharusnya ditampilkan sekarang');
});

// Tangkap event appinstalled
window.addEventListener('appinstalled', (evt) => {
    console.log('Aplikasi berhasil diinstal');
    // Sembunyikan tombol install setelah aplikasi diinstal
    hideInstallButton();    
    // Redirect ke halaman utama
    window.location.href = '/';
});

// Periksa ulang status instalasi saat halaman dimuat ulang
window.addEventListener('load', () => {
    if (isInstalledPWA()) {
        hideInstallButton();
    } else if (deferredPrompt) {
        showInstallButton();
    }
});
</script>
