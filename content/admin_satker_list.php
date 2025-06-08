<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $pageTitle ?></h1>
        <a href="admin_satker.php?action=add" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-plus"></i>
            </span>
            <span class="text">Tambah Satker</span>
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
            <h6 class="m-0 font-weight-bold text-white">Daftar Satker</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Satker</th>
                            <th>Rayon</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($satkers)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data satker</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($satkers as $index => $satker): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($satker['nama_satker']) ?></td>
                                    <td><?= htmlspecialchars($satker['nama_rayon']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $satker['is_active'] ? 'success text-white' : 'secondary' ?>"> <?= $satker['is_active'] ? 'Aktif' : 'Nonaktif' ?> </span>
                                    </td>
                                    <td><?= htmlspecialchars($satker['created_at']) ?></td>
                                    <td class="text-center">
                                        <a href="admin_satker.php?action=edit&id=<?= $satker['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="admin_satker.php?action=toggle_status&id=<?= $satker['id'] ?>" class="btn btn-sm btn-<?= $satker['is_active'] ? 'warning' : 'success' ?>" title="<?= $satker['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>" onclick="return confirm('Apakah Anda yakin ingin <?= $satker['is_active'] ? 'menonaktifkan' : 'mengaktifkan' ?> satker ini?')">
                                            <i class="fas fa-<?= $satker['is_active'] ? 'ban' : 'check' ?>"></i>
                                        </a>
                                        <a href="admin_satker.php?action=delete&id=<?= $satker['id'] ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus satker ini? Tindakan ini tidak dapat dibatalkan.')">
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
