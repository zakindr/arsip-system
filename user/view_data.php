<?php
require_once '../includes/auth.php';
require_once '../includes/config.php';
require_once '../includes/database.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

// Pagination
$limit = (int)($_GET['limit'] ?? 25);
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

// Get total count
$totalRecords = $db->fetchOne("SELECT COUNT(*) as count FROM mytable")['count'] ?? 0;
$totalPages = ceil($totalRecords / $limit);

// Get paginated results
$results = $db->fetchAll("SELECT * FROM mytable ORDER BY `NO` ASC LIMIT ? OFFSET ?", [$limit, $offset]);

$pageTitle = 'Data Arsip - Sistem Arsip Unmul';
include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Data Arsip</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Data Arsip</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Arsip Universitas Mulawarman</h3>
                    <div class="card-tools">
                        <?php if ($auth->isAdmin()): ?>
                        <a href="../admin/manage_data.php?action=add" class="btn btn-primary btn-sm mr-2">
                            <i class="fas fa-plus"></i> Tambah Data
                        </a>
                        <?php endif; ?>
                        
                        <!-- Items per page selector -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                <?= $limit ?> per halaman
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item <?= $limit == 10 ? 'active' : '' ?>" href="?limit=10&page=1">10 per halaman</a>
                                <a class="dropdown-item <?= $limit == 25 ? 'active' : '' ?>" href="?limit=25&page=1">25 per halaman</a>
                                <a class="dropdown-item <?= $limit == 50 ? 'active' : '' ?>" href="?limit=50&page=1">50 per halaman</a>
                                <a class="dropdown-item <?= $limit == 100 ? 'active' : '' ?>" href="?limit=100&page=1">100 per halaman</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Info bar -->
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <small class="text-muted">
                                Menampilkan <?= count($results) ?> dari <?= $totalRecords ?> total data
                                (Halaman <?= $page ?> dari <?= $totalPages ?>)
                            </small>
                        </div>
                    </div>
                    
                    <?php if (count($results) > 0): ?>
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-bordered table-striped">
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
                                            <?php if ($row['FILE'] && strpos($row['FILE'], '<a') !== false): ?>
                                                <?= $row['FILE'] ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars($row['FILE'] ?? '') ?>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($auth->isAdmin()): ?>
                                        <td>
                                            <div class="btn-group">
                                                <a href="../admin/manage_data.php?action=edit&id=<?= $row['NO'] ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../admin/manage_data.php?action=delete&id=<?= $row['NO'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Hapus data ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <!-- Previous -->
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?limit=<?= $limit ?>&page=<?= $page - 1 ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <!-- Page numbers -->
                                        <?php 
                                        $start = max(1, $page - 2);
                                        $end = min($totalPages, $page + 2);
                                        
                                        for ($i = $start; $i <= $end; $i++): 
                                        ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?limit=<?= $limit ?>&page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <!-- Next -->
                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?limit=<?= $limit ?>&page=<?= $page + 1 ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Tidak ada data arsip yang tersedia.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>