<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/database.php';

$auth = new Auth();
$auth->requireLogin();
$db = Database::getInstance();

$message = '';
$error = '';

// Get user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

// Handle form submission
if ($_POST) {
    require_once 'includes/security.php';
    
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = Security::sanitizeInput($_POST['username'] ?? '', 'string');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate input
        $validationErrors = Security::validateInput($_POST, [
            'username' => [
                'required' => true,
                'min_length' => 3,
                'max_length' => 50,
                'message' => 'Username must be 3-50 characters'
            ]
        ]);
        
        if (!empty($validationErrors)) {
            $error = implode(', ', $validationErrors);
        } else {
            // Handle password change if new password provided
            if ($newPassword) {
                if ($newPassword !== $confirmPassword) {
                    $error = 'Konfirmasi password tidak cocok';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'Password baru minimal 6 karakter';
                } else {
                    // Use the new changePassword method
                    $result = $auth->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                    if (!$result['success']) {
                        $error = $result['message'];
                    }
                }
            }
            
            if (!$error) {
                // Update username
                $db->update('users', 
                    ['username' => $username], 
                    'id = ?', 
                    [$_SESSION['user_id']]
                );
                
                $_SESSION['username'] = $username;
                $message = 'Profile berhasil diupdate';
                
                // Refresh user data
                $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            }
        }
    }
}

$pageTitle = 'Profile - Sistem Arsip Unmul';
include 'templates/header.php';
include 'templates/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Profile</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Edit Profile</h3>
                        </div>
                        
                        <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-check"></i> <?= $message ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="role">Role</label>
                                    <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" readonly>
                                </div>
                                
                                <hr>
                                <h5>Ganti Password (Opsional)</h5>
                                
                                <div class="form-group">
                                    <label for="current_password">Password Saat Ini</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">Password Baru</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Informasi Akun</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Username:</strong></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Role:</strong></td>
                                    <td><span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>"><?= ucfirst($user['role']) ?></span></td>
                                </tr>
                                <tr>
                                    <td><strong>Terdaftar:</strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Tips Keamanan</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Gunakan password yang kuat (minimal 6 karakter)</li>
                                <li><i class="fas fa-check text-success"></i> Jangan bagikan password Anda</li>
                                <li><i class="fas fa-check text-success"></i> Logout setelah selesai menggunakan sistem</li>
                                <li><i class="fas fa-check text-success"></i> Ganti password secara berkala</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'templates/footer.php'; ?>