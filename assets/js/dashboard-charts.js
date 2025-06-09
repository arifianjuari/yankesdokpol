document.addEventListener('DOMContentLoaded', function () {
    // Register chartjs-plugin-datalabels
    Chart.register(ChartDataLabels);

    // Inisialisasi variabel chart global
    let charts = {
        layanan: null,
        totalPeserta: null,
        pesertaPemeriksaan: null,
        usia: null,
        satker: null
    };

    const colors = [
        'rgba(54,162,235,0.7)', 'rgba(255,99,132,0.7)', 'rgba(75,192,192,0.7)',
        'rgba(255,206,86,0.7)', 'rgba(153,102,255,0.7)', 'rgba(255,159,64,0.7)',
        'rgba(199,199,199,0.7)', 'rgba(83,102,255,0.7)'
    ];

    // Fungsi untuk menghancurkan semua chart
    function destroyAllCharts() {
        Object.keys(charts).forEach(key => {
            if (charts[key]) {
                charts[key].destroy();
                charts[key] = null;
            }
        });
    }

    // Fungsi untuk memperbarui pie chart
    function updatePieChart(canvasId, labels, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const chartStatus = Chart.getChart(canvasId);
        if (chartStatus != undefined) {
            chartStatus.destroy();
        }

        const baseColors = [
            '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8',
            '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', '#6610f2'
        ];

        function generateColors(count) {
            let colors = [];
            for (let i = 0; i < count; i++) {
                if (i < baseColors.length) {
                    colors.push(baseColors[i]);
                } else {
                    const r = Math.floor(Math.random() * 255);
                    const g = Math.floor(Math.random() * 255);
                    const b = Math.floor(Math.random() * 255);
                    colors.push(`rgb(${r},${g},${b})`);
                }
            }
            return colors;
        }

        const backgroundColors = generateColors(labels.length);

        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(color => color.replace(')', ', 0.7)').replace('rgb', 'rgba')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed;
                                }
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(2) + '%' : '0%';
                                label += ` (${percentage})`;
                                return label;
                            }
                        }
                    },
                    datalabels: { // Konfigurasi datalabels
                        formatter: (value, context) => {
                            let sum = 0;
                            let dataArr = context.chart.data.datasets[0].data;
                            dataArr.map(data => {
                                sum += data;
                            });
                            let percentage = sum > 0 ? (value * 100 / sum).toFixed(1) + "%" : '';
                            // Hanya tampilkan persentase jika lebih dari 5% untuk menghindari tumpang tindih
                            return parseFloat(percentage) > 5 ? value + '\n(' + percentage + ')' : ''; 
                        },
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        textAlign: 'center',
                        textStrokeColor: 'black',
                        textStrokeWidth: 0.5
                    }
                }
            }
        });
    }

    // Fungsi untuk memperbarui bar chart
    function updateBarChart(canvasId, labels, data, chartLabel = 'Jumlah', yAxisLabel = 'Jumlah') {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const chartStatus = Chart.getChart(canvasId);
        if (chartStatus != undefined) {
            chartStatus.destroy();
        }

        const baseColors = [
            'rgba(0, 123, 255, 0.7)', 'rgba(40, 167, 69, 0.7)', 'rgba(220, 53, 69, 0.7)', 
            'rgba(255, 193, 7, 0.7)', 'rgba(23, 162, 184, 0.7)', 'rgba(111, 66, 193, 0.7)',
            'rgba(232, 62, 140, 0.7)', 'rgba(253, 126, 20, 0.7)', 'rgba(32, 201, 151, 0.7)',
            'rgba(102, 16, 242, 0.7)'
        ];

        function generateBarColors(count) {
            let colors = [];
            for (let i = 0; i < count; i++) {
                if (i < baseColors.length) {
                    colors.push(baseColors[i]);
                } else {
                    const r = Math.floor(Math.random() * 255);
                    const g = Math.floor(Math.random() * 255);
                    const b = Math.floor(Math.random() * 255);
                    colors.push(`rgba(${r},${g},${b}, 0.7)`);
                }
            }
            return colors;
        }
        const backgroundColors = generateBarColors(labels.length);
        const borderColors = backgroundColors.map(color => color.replace('0.7', '1'));

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: chartLabel,
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: yAxisLabel
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        bodyFont: {
                            size: 13
                        },
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        formatter: (value) => {
                            return value > 0 ? value : '';
                        },
                        color: '#333',
                        font: {
                            weight: 'bold',
                            size: 10
                        }
                    }
                }
            }
        });
    }


    // Fungsi untuk memperbarui semua chart
    function updateCharts() {
        // Hancurkan semua chart terlebih dahulu
        destroyAllCharts();

        const acaraSelect = document.getElementById('filter-acara');
        const acaraId = acaraSelect ? acaraSelect.value : 'all';

        // Tampilkan indikator loading
        const visualisasiDebug = document.getElementById('visualisasi-debug');
        if (visualisasiDebug) {
            visualisasiDebug.textContent = 'Memuat data...';
            visualisasiDebug.classList.remove('text-danger');
            visualisasiDebug.classList.add('text-info');
        }

        // Debug: tampilkan ID acara yang dipilih
        console.log('Filter acara dengan ID:', acaraId);

        fetch(`api/visualisasi.php?acara_id=${acaraId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Debug: tampilkan data yang diterima
                console.log('Data dari API:', data);

                // Bersihkan debug info jika berhasil
                if (visualisasiDebug) {
                    visualisasiDebug.textContent = '';
                }

                // Update chart layanan
                if (data.layanan?.length) {
                    const labels = data.layanan.map(i => i.nama);
                    const counts = data.layanan.map(i => parseInt(i.jumlah) || 0);
                    charts.layanan = updatePieChart('chart-layanan', labels, counts);
                }

                // Update chart total peserta
                if (data.totalPeserta?.length) {
                    const labels = data.totalPeserta.map(i => i.nama);
                    const counts = data.totalPeserta.map(i => parseInt(i.jumlah) || 0);
                    charts.totalPeserta = updateBarChart('chart-total-peserta', labels, counts);
                }

                // Update chart peserta per pemeriksaan
                if (data.pesertaPemeriksaan?.length) {
                    const labels = data.pesertaPemeriksaan.map(i => i.nama);
                    const counts = data.pesertaPemeriksaan.map(i => parseInt(i.jumlah) || 0);
                    charts.pesertaPemeriksaan = updateBarChart('chart-peserta-pemeriksaan', labels, counts);
                }

                // Update chart distribusi usia
                if (data.distribusiUsia?.length) {
                    const labels = data.distribusiUsia.map(i => i.kelompok);
                    const counts = data.distribusiUsia.map(i => parseInt(i.jumlah) || 0);
                    charts.usia = updatePieChart('chart-usia', labels, counts);
                } else {
                    document.getElementById('no-data-usia').style.display = 'block';
                    document.getElementById('chart-usia').style.display = 'none';
                }

                // Update chart SATKER (as bar chart)
                if (data.satker?.length) {
                    const labels = data.satker.map(i => i.nama);
                    const counts = data.satker.map(i => parseInt(i.jumlah) || 0);
                    charts.satker = updateBarChart('chart-satker', labels, counts, 'Jumlah Peserta', 'Jumlah Peserta');
                    
                    // Sembunyikan pesan tidak ada data
                    document.getElementById('no-data-satker').style.display = 'none';
                    document.getElementById('chart-satker').style.display = 'block';
                } else {
                    document.getElementById('no-data-satker').style.display = 'block';
                    document.getElementById('chart-satker').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (visualisasiDebug) {
                    visualisasiDebug.textContent = 'Terjadi kesalahan saat memuat data: ' + error.message;
                    visualisasiDebug.classList.remove('text-info');
                    visualisasiDebug.classList.add('text-danger');
                }
            });
    }

    // Inisialisasi event listener untuk tombol filter
    const filterButton = document.getElementById('btn-filter');
    if (filterButton) {
        filterButton.addEventListener('click', function (e) {
            e.preventDefault(); // Hindari default action
            updateCharts(); // Panggil fungsi update charts
        });
    } else {
        console.error('Tombol filter tidak ditemukan!');
    }

    // Auto-update saat dropdown berubah (tanpa konfirmasi)
    const filterSelect = document.getElementById('filter-acara');
    if (filterSelect) {
        filterSelect.addEventListener('change', function () {
            updateCharts();
        });
    } else {
        console.error('Dropdown filter acara tidak ditemukan!');
    }

    // Jalankan pertama kali saat halaman dimuat
    updateCharts();
});
