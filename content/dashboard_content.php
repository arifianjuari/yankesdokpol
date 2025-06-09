<?php

/**
 * Dashboard Content
 * 
 * Konten halaman dashboard, diinclude oleh layout admin
 */
?>


<!-- Kartu statistik dihapus sesuai permintaan -->

<style>
    /* Optimasi ukuran kartu grafik */
    .chart-container {
        position: relative;
        margin: auto;
        height: 250px;
        /* Tinggi dikurangi untuk kartu lebih kecil */
        width: 100%;
    }

    .card {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1rem;
    }

    .card-header {
        padding: 0.75rem 1rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, .125);
    }

    .card-header h6 {
        font-weight: 600;
        margin: 0;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .chart-container {
            height: 200px;
            /* Tinggi dikurangi juga untuk mobile */
        }
    }
</style>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row mb-3 align-items-center">
            <div class="col-md-auto mb-2 mb-md-0">
                <a href="form_peserta.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-left-circle"></i>ke Form Pendaftaran
                </a>
            </div>
            <div class="col-md-4">
                <label for="filter-acara" class="form-label">Pilih Acara</label>
                <select id="filter-acara" class="form-select">
                    <option value="all">Semua Acara</option>
                    <?php
                    $result = executeQuery("SELECT id, nama_acara, tanggal_mulai FROM acara ORDER BY tanggal_mulai DESC");
                    if ($result) while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_acara']) . ' (' . date('d-m-Y', strtotime($row['tanggal_mulai'])) . ')</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary" id="btn-filter">
                    <i class="bi bi-funnel me-1"></i> Terapkan Filter
                </button>
            </div>
        </div>
        <div id="visualisasi-debug" class="mt-2 text-danger small"></div>
    </div>
</div>

<!-- Visualisasi Data Section -->
<div class="row">
    <!-- Baris 1: Total Peserta per Acara dan Peserta per Jenis Layanan -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Total Peserta per Acara</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chart-total-peserta"></canvas>
                </div>
                <div class="text-center mt-2 text-muted small" id="no-data-total-peserta" style="display:none">Tidak ada data</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Peserta per Jenis Layanan</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chart-peserta-pemeriksaan"></canvas>
                </div>
                <div class="text-center mt-2 text-muted small" id="no-data-peserta-pemeriksaan" style="display:none">Tidak ada data</div>
            </div>
        </div>
    </div>

    <!-- Baris 2: Top 10 SATKER -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Top 10 SATKER Peserta</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chart-satker"></canvas>
                </div>
                <div class="text-center mt-2 text-muted small" id="no-data-satker" style="display:none">Tidak ada data SATKER</div>
            </div>
        </div>
    </div>

    <!-- Baris 3: Distribusi Usia Peserta -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Distribusi Usia Peserta</h6>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chart-usia"></canvas>
                </div>
                <div class="text-center mt-2 text-muted small" id="no-data-usia" style="display:none">Tidak ada data</div>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Load custom chart scripts -->
<script src="assets/js/dashboard-charts.js?v=<?php echo filemtime('assets/js/dashboard-charts.js'); ?>"></script>