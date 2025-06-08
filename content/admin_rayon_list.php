<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $pageTitle ?></h1>
        <a href="admin_rayon.php?action=add" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-plus"></i>
            </span>
            <span class="text">Tambah Rayon</span>
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php unset($_SESSION['success']) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php unset($_SESSION['error']) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-white">Daftar Rayon</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Rayon</th>
                            <th>Jumlah Satker</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rayons)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data rayon</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rayons as $index => $rayon): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($rayon['nama_rayon']) ?></td>
                                    <td class="text-center"><?= $rayon['total_satker'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $rayon['is_active'] ? 'success text-white' : 'secondary' ?>"> <?= $rayon['is_active'] ? 'Aktif' : 'Nonaktif' ?> </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="admin_rayon.php?action=edit&id=<?= $rayon['id'] ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="admin_rayon.php?action=toggle_status&id=<?= $rayon['id'] ?>" 
                                           class="btn btn-sm btn-<?= $rayon['is_active'] ? 'warning' : 'success' ?>" 
                                           title="<?= $rayon['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                           onclick="return confirm('Apakah Anda yakin ingin <?= $rayon['is_active'] ? 'menonaktifkan' : 'mengaktifkan' ?> rayon ini?')">
                                            <i class="fas fa-<?= $rayon['is_active'] ? 'ban' : 'check' ?>"></i>
                                        </a>
                                        <a href="admin_rayon.php?action=delete&id=<?= $rayon['id'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Hapus"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus rayon ini? Tindakan ini tidak dapat dibatalkan.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
