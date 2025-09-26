<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/database.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

// Get statistics
$totalArsip = $db->fetchOne("SELECT COUNT(*) as count FROM mytable")['count'] ?? 0;
$totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
$recentArsip = $db->fetchOne("SELECT COUNT(*) as count FROM mytable WHERE YEAR(`TAHUN`) = YEAR(CURDATE())")['count'] ?? 0;

$pageTitle = 'Dashboard - Sistem Arsip Unmul';
include 'templates/header.php';
include 'templates/sidebar.php';
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <div class="row">
          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?= $totalArsip ?></h3>
                <p>Total Arsip</p>
              </div>
              <div class="icon">
                <i class="fas fa-archive"></i>
              </div>
              <a href="user/view_data.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          
          <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
              <div class="inner">
                <h3><?= $recentArsip ?></h3>
                <p>Arsip Tahun Ini</p>
              </div>
              <div class="icon">
                <i class="fas fa-calendar"></i>
              </div>
              <a href="search.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          
          <?php if ($auth->isAdmin()): ?>
          <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
              <div class="inner">
                <h3><?= $totalUsers ?></h3>
                <p>Total Users</p>
              </div>
              <div class="icon">
                <i class="fas fa-users"></i>
              </div>
              <a href="admin/manage_users.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          
          <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
              <div class="inner">
                <h3>Admin</h3>
                <p>Panel</p>
              </div>
              <div class="icon">
                <i class="fas fa-cog"></i>
              </div>
              <a href="admin/manage_data.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <?php endif; ?>
        </div>
        
        <!-- Welcome Card -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Selamat Datang, <?= $_SESSION['username'] ?>!</h3>
              </div>
              <div class="card-body">
                <p>Anda login sebagai <strong><?= ucfirst($_SESSION['role']) ?></strong></p>
                <p>Sistem Arsip Digital Universitas Mulawarman - Platform untuk mengelola dan mengakses dokumen arsip universitas secara digital.</p>
                
                <div class="row">
                  <div class="col-md-6">
                    <h5>Fitur yang Tersedia:</h5>
                    <ul>
                      <li>Pencarian arsip berdasarkan berbagai kriteria</li>
                      <li>Akses dokumen digital</li>
                      <?php if ($auth->isAdmin()): ?>
                      <li>Manajemen data arsip (CRUD)</li>
                      <li>Manajemen pengguna</li>
                      <li>Laporan dan statistik</li>
                      <?php endif; ?>
                    </ul>
                  </div>
                  <div class="col-md-6">
                    <h5>Quick Actions:</h5>
                    <a href="user/view_data.php" class="btn btn-primary mr-2">Lihat Data Arsip</a>
                    <a href="search.php" class="btn btn-success mr-2">Pencarian</a>
                    <?php if ($auth->isAdmin()): ?>
                    <a href="admin/manage_data.php?action=add" class="btn btn-warning">Tambah Arsip</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

<?php include 'templates/footer.php'; ?>