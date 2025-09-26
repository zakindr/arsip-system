<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/database.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

$searchQuery = $_GET['q'] ?? '';
$results = [];

if ($searchQuery) {
    $sql = "SELECT * FROM mytable WHERE 
            `NOMOR ARSIP` LIKE ? OR 
            `KODE KLASIFIKASI` LIKE ? OR 
            `PERIHAL` LIKE ? OR 
            `URAIAN` LIKE ? OR 
            `TAHUN` LIKE ?";
    
    $searchTerm = "%$searchQuery%";
    $results = $db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$pageTitle = 'Pencarian Arsip - Sistem Arsip Unmul';
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Pencarian Arsip</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Pencarian</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Search Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Form Pencarian</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="input-group input-group-lg">
                            <input type="text" name="q" class="form-control" 
                                   placeholder="Cari berdasarkan nomor arsip, kode klasifikasi, perihal, uraian, atau tahun..." 
                                   value="<?= htmlspecialchars($searchQuery) ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Search Results -->
            <?php if ($searchQuery): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Hasil Pencarian untuk "<?= htmlspecialchars($searchQuery) ?>" 
                        <span class="badge badge-info"><?= count($results) ?> hasil</span>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (count($results) > 0): ?>
                        <div class="table-responsive">
                            <table id="searchTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nomor Arsip</th>
                                        <th>Kode Klasifikasi</th>
                                        <th>Perihal</th>
                                        <th>Bentuk Redaksi</th>
                                        <th>Tingkat Perkembangan</th>
                                        <th>Uraian</th>
                                        <th>Tahun</th>
                                        <th>File</th>
                                        <?php if ($auth->isAdmin()): ?>
                                        <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $row): ?>
                                    <tr>
                                        <td><?= $row['NO'] ?></td>
                                        <td><?= htmlspecialchars($row['NOMOR ARSIP'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['KODE KLASIFIKASI'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['PERIHAL'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['BENTUK REDAKSI'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['TINGKAT PERKEMBANGAN'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(substr($row['URAIAN'] ?? '', 0, 100)) ?>...</td>
                                        <td><?= htmlspecialchars($row['TAHUN'] ?? '') ?></td>
                                        <td>
                                            <?php if (strpos($row['FILE'], '<a') !== false): ?>
                                                <?= $row['FILE'] ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['FILE'] ?? '') ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($auth->isAdmin()): ?>
                                        <td>
                                            <a href="admin/manage_data.php?action=edit&id=<?= $row['NO'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="admin/manage_data.php?action=delete&id=<?= $row['NO'] ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Delete this record?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Tidak ada data yang ditemukan untuk pencarian "<?= htmlspecialchars($searchQuery) ?>"
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#searchTable').DataTable({
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "pageLength": 25,
        "language": {
            "search": "Filter hasil:",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "zeroRecords": "Data tidak ditemukan",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada data tersedia",
            "infoFiltered": "(difilter dari _MAX_ total data)",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir", 
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>