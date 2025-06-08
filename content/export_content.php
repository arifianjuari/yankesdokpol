<?php
/**
 * Export Content
 * 
 * This file contains the export form for the admin panel
 */

// Get list of layanan for the dropdown
$layananList = [];
$layananQuery = "SELECT id, nama_layanan FROM layanan ORDER BY nama_layanan";
$layananResult = $conn->query($layananQuery);
if ($layananResult) {
    while ($row = $layananResult->fetch_assoc()) {
        $layananList[] = $row;
    }
}
?>

<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white">
        <h2 class="h5 mb-0">Export Data Peserta</h2>
    </div>
    <div class="card-body">
        <form method="get" action="export.php" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="startDate">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="startDate" name="start_date" 
                               value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="endDate">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="endDate" name="end_date" 
                               value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="layananId">Layanan</label>
                        <select class="form-select" id="layananId" name="layanan_id">
                            <option value="0">Semua Layanan</option>
                            <?php foreach ($layananList as $layanan): ?>
                                <option value="<?php echo $layanan['id']; ?>"
                                    <?php echo (isset($_GET['layanan_id']) && $_GET['layanan_id'] == $layanan['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($layanan['nama_layanan']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Export Data
                    </button>
                    <a href="export.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info">
    <h5><i class="bi bi-info-circle-fill me-2"></i>Petunjuk Ekspor</h5>
    <ul class="mb-0">
        <li>Biarkan semua field kosong untuk mengekspor semua data</li>
        <li>Pilih rentang tanggal untuk memfilter data berdasarkan tanggal pendaftaran</li>
        <li>Pilih layanan tertentu untuk mengekspor data peserta layanan tersebut saja</li>
    </ul>
</div>
