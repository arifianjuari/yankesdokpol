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
    <button id="pwa-install-button" class="btn btn-primary btn-sm position-fixed bottom-0 end-0 m-3 d-flex align-items-center" style="z-index: 1050; display: none !important;">
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
        // Gunakan !important untuk memastikan tombol muncul
        installButton.style.cssText = 'display: flex !important; z-index: 1050;';
        isInstallButtonVisible = true;
        console.log('Tombol instal ditampilkan');
    }
}

// Fungsi untuk menyembunyikan tombol install
function hideInstallButton() {
    const installButton = document.getElementById('pwa-install-button');
    if (installButton) {
        installButton.style.cssText = 'display: none !important;';
        isInstallButtonVisible = false;
        console.log('Tombol instal disembunyikan');
    }
}

// Cek apakah aplikasi sudah diinstal sebagai PWA
function isInstalledPWA() {
    // Cek apakah aplikasi dijalankan dalam mode standalone
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('Aplikasi dijalankan dalam mode standalone');
        return true;
    }
    
    // Cek untuk iOS
    if (window.navigator.standalone === true) {
        console.log('Aplikasi dijalankan dalam mode standalone di iOS');
        return true;
    }
    
    // Cek untuk Android TWA
    if (document.referrer.includes('android-app://')) {
        console.log('Aplikasi dijalankan sebagai TWA di Android');
        return true;
    }
    
    return false;
}

// Inisialisasi tombol install
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM dimuat, memeriksa status instalasi...');
    
    const installButton = document.getElementById('pwa-install-button');
    if (!installButton) {
        console.error('Tombol install PWA tidak ditemukan di DOM');
        return;
    }
    
    // Sembunyikan tombol jika aplikasi sudah diinstal
    if (isInstalledPWA()) {
        hideInstallButton();
        return;
    }
    
    // Tambahkan event listener untuk tombol install
    installButton.addEventListener('click', async () => {
        console.log('Tombol instal diklik');
        
        if (!deferredPrompt) {
            console.warn('Prompt instalasi tidak tersedia');
            alert('Instalasi tidak tersedia saat ini. Silakan coba lagi nanti.');
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
            alert('Terjadi kesalahan saat proses instalasi. Silakan coba lagi nanti.');
        } finally {
            // Reset variabel prompt
            deferredPrompt = null;
        }
    });
});

// Tangkap event beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('beforeinstallprompt event terdeteksi');
    
    // Cegah browser menampilkan prompt secara otomatis
    e.preventDefault();
    
    // Simpan event untuk digunakan nanti
    deferredPrompt = e;
    
    // Tampilkan tombol install
    setTimeout(() => {
        showInstallButton();
        console.log('Tombol instal seharusnya ditampilkan sekarang');
    }, 1000); // Delay sedikit untuk memastikan DOM sudah siap
});

// Tangkap event appinstalled
window.addEventListener('appinstalled', (evt) => {
    console.log('Aplikasi berhasil diinstal');
    // Sembunyikan tombol install setelah aplikasi diinstal
    hideInstallButton();
});

// Periksa status instalasi saat halaman dimuat (tanpa reload)
// Tidak perlu memeriksa deferredPrompt di sini karena event beforeinstallprompt
// akan menangani itu jika tersedia
window.addEventListener('load', () => {
    console.log('Halaman dimuat, memeriksa status instalasi...');
    if (isInstalledPWA()) {
        hideInstallButton();
    }
    // Tidak perlu showInstallButton() di sini, akan ditangani oleh event beforeinstallprompt
});

// Tambahkan listener untuk perubahan display-mode
window.matchMedia('(display-mode: standalone)').addEventListener('change', (evt) => {
    if (evt.matches) {
        console.log('Aplikasi sekarang berjalan dalam mode standalone');
        hideInstallButton();
    }
});
</script>
