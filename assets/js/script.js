/**
 * YankesDokpol Main JavaScript
 * 
 * This file contains client-side functionality for the YankesDokpol application.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // File input preview for KTP
    const ktpInput = document.getElementById('fileKTP');
    const ktpPreview = document.getElementById('ktpPreview');
    const ktpImage = document.getElementById('ktpImage');
    const processOCRBtn = document.getElementById('processOCR');

    // Show preview when KTP file is selected
    if (ktpInput) {
        ktpInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    ktpImage.src = e.target.result;
                    ktpPreview.classList.remove('d-none');
                };

                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // Process OCR button click handler
    if (processOCRBtn) {
        processOCRBtn.addEventListener('click', function () {
            if (!ktpInput.files || !ktpInput.files[0]) {
                alert('Silakan pilih file KTP terlebih dahulu');
                return;
            }

            // Show loading indicator
            processOCRBtn.disabled = true;
            processOCRBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';

            // Create FormData object
            const formData = new FormData();
            formData.append('ktp_image', ktpInput.files[0]);

            // Tambahkan NIK jika sudah diisi
            const nikInput = document.getElementById('nik');
            if (nikInput && nikInput.value) {
                formData.append('nik', nikInput.value);
            }

            // Send AJAX request to process OCR
            fetch('api/ocr.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('OCR response:', data);

                    if (data.status === 'success' || data.status === 'warning') {
                        // Fill form fields with OCR results
                        document.getElementById('nik').value = data.data.nik || '';
                        document.getElementById('nama').value = data.data.nama || '';
                        document.getElementById('alamat').value = data.data.alamat || '';
                        document.getElementById('tanggalLahir').value = data.data.tanggal_lahir || '';

                        // Show success message
                        alert('Data KTP berhasil diproses. Silakan periksa dan koreksi jika diperlukan.');
                    } else {
                        // Show error message
                        alert('Gagal memproses data KTP: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memproses data KTP');
                })
                .finally(() => {
                    // Reset button state
                    processOCRBtn.disabled = false;
                    processOCRBtn.innerHTML = 'Proses OCR';
                });
        });
    }

    // Form validation
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function (event) {
            // Basic client-side validation
            const nik = document.getElementById('nik').value;
            const nama = document.getElementById('nama').value;
            const alamat = document.getElementById('alamat').value;
            const tanggalLahir = document.getElementById('tanggalLahir').value;

            // Validate NIK (16 digits)
            // if (!/^\d{16}$/.test(nik)) {
            //     alert('NIK harus terdiri dari 16 digit angka');
            //     event.preventDefault();
            //     return;
            // }

            // Validate required fields
            if (!nama || !alamat || !tanggalLahir) {
                alert('Nama, alamat, dan tanggal lahir harus diisi');
                event.preventDefault();
                return;
            }

            // Validate at least one service is selected
            const layananChecked = document.querySelectorAll('input[name="layanan[]"]:checked');
            if (layananChecked.length === 0) {
                alert('Pilih minimal satu layanan yang diikuti');
                event.preventDefault();
                return;
            }

            // Additional validation can be added here
        });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
