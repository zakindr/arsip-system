<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/security.php';
require_once '../includes/file_upload.php';

$auth = new Auth();
$auth->requireAdmin();
$db = Database::getInstance();
$fileUpload = new FileUpload();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';

// Handle form submissions
if (Security::isPost()) {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
    } else {
        // Validate input
        $validationRules = [
            'nomor_arsip' => ['required' => true, 'max_length' => 255],
            'kode_klasifikasi' => ['required' => true, 'max_length' => 255],
            'perihal' => ['required' => true, 'max_length' => 255],
            'bentuk_redaksi' => ['required' => true, 'max_length' => 255],
            'tingkat_perkembangan' => ['required' => true, 'max_length' => 255],
            'uraian' => ['max_length' => 1000],
            'tahun' => ['required' => true, 'type' => 'int']
        ];
        
        $validationErrors = Security::validateInput($_POST, $validationRules);
        
        if (empty($validationErrors)) {
            // Handle file upload if file is provided
            $fileData = '';
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $fileUpload->uploadFile($_FILES['file']);
                if ($uploadResult['success']) {
                    $fileData = $fileUpload->createDownloadLink($uploadResult['path'], $uploadResult['original_name']);
                } else {
                    $message = 'File upload failed: ' . implode(', ', $uploadResult['errors']);
                }
            } else {
                // Keep existing file if no new file uploaded
                if ($action === 'edit' && isset($_POST['existing_file'])) {
                    $fileData = $_POST['existing_file'];
                }
            }
            
            if (empty($message)) { // Only proceed if no upload errors
                $data = [
                    'NOMOR ARSIP' => Security::sanitizeInput($_POST['nomor_arsip'], 'string'),
                    'KODE KLASIFIKASI' => Security::sanitizeInput($_POST['kode_klasifikasi'], 'string'),
                    'PERIHAL' => Security::sanitizeInput($_POST['perihal'], 'string'),
                    'BENTUK REDAKSI' => Security::sanitizeInput($_POST['bentuk_redaksi'], 'string'),
                    'TINGKAT PERKEMBANGAN' => Security::sanitizeInput($_POST['tingkat_perkembangan'], 'string'),
                    'URAIAN' => Security::sanitizeInput($_POST['uraian'], 'string'),
                    'TAHUN' => Security::sanitizeInput($_POST['tahun'], 'int'),
                    'FILE' => $fileData
                ];
                
                switch ($action) {
                    case 'add':
                        $insertId = $db->insert('mytable', $data);
                        if ($insertId) {
                            $message = 'Record added successfully!';
                            Security::logSecurityEvent('archive_added', [
                                'record_id' => $insertId,
                                'user_id' => $_SESSION['user_id']
                            ]);
                        }
                        break;
                        
                    case 'edit':
                        $affectedRows = $db->update('mytable', $data, '`NO` = ?', [$id]);
                        if ($affectedRows > 0) {
                            $message = 'Record updated successfully!';
                            Security::logSecurityEvent('archive_updated', [
                                'record_id' => $id,
                                'user_id' => $_SESSION['user_id']
                            ]);
                        }
                        break;
                }
            }
        } else {
            $message = 'Validation errors: ' . implode(', ', $validationErrors);
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM mytable WHERE `NO` = ?");
    if ($stmt->execute([$id])) {
        header('Location: ../user/view_data.php');
        exit;
    }
}

// Get record for editing
$record = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM mytable WHERE `NO` = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Data - Sistem Arsip Unmul</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header admin">
        <h1><?= ucfirst($action) ?> Data Arsip</h1>
        <a href="../user/view_data.php" style="color: white; float: right;">← Back to Data</a>
    </div>
    
    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div style="background: white; padding: 20px; border-radius: 5px;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <?php if ($action === 'edit' && isset($record['FILE'])): ?>
                    <input type="hidden" name="existing_file" value="<?= Security::escapeHtml($record['FILE']) ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nomor_arsip">Nomor Arsip:</label>
                    <input type="text" id="nomor_arsip" name="nomor_arsip" 
                           value="<?= Security::escapeHtml($record['NOMOR ARSIP'] ?? '') ?>" 
                           required maxlength="255" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="kode_klasifikasi">Kode Klasifikasi:</label>
                    <input type="text" id="kode_klasifikasi" name="kode_klasifikasi" 
                           value="<?= Security::escapeHtml($record['KODE KLASIFIKASI'] ?? '') ?>" 
                           required maxlength="255" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="perihal">Perihal:</label>
                    <input type="text" id="perihal" name="perihal" 
                           value="<?= Security::escapeHtml($record['PERIHAL'] ?? '') ?>" 
                           required maxlength="255" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="bentuk_redaksi">Bentuk Redaksi:</label>
                    <input type="text" id="bentuk_redaksi" name="bentuk_redaksi" 
                           value="<?= Security::escapeHtml($record['BENTUK REDAKSI'] ?? '') ?>" 
                           required maxlength="255" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="tingkat_perkembangan">Tingkat Perkembangan:</label>
                    <input type="text" id="tingkat_perkembangan" name="tingkat_perkembangan" 
                           value="<?= Security::escapeHtml($record['TINGKAT PERKEMBANGAN'] ?? '') ?>" 
                           required maxlength="255" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="uraian">Uraian:</label>
                    <textarea id="uraian" name="uraian" rows="3" maxlength="1000" class="form-control"><?= Security::escapeHtml($record['URAIAN'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="tahun">Tahun:</label>
                    <input type="number" id="tahun" name="tahun" 
                           value="<?= Security::escapeHtml($record['TAHUN'] ?? date('Y')) ?>" 
                           required min="1900" max="2100" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="file">File:</label>
                    <?php if ($action === 'edit' && !empty($record['FILE'])): ?>
                        <div class="mb-2">
                            <small class="text-muted">Current file:</small>
                            <div><?= $record['FILE'] ?></div>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv" class="form-control-file">
                    <small class="form-text text-muted">
                        Allowed formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, TXT, CSV (Max: 10MB)
                    </small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $action === 'edit' ? 'Update' : 'Add' ?> Record
                    </button>
                    <a href="../user/view_data.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div style="background: white; padding: 20px; border-radius: 5px;">
            <p>Please specify an action: <a href="?action=add" class="btn">Add New Record</a></p>
            <p><a href="../user/view_data.php" class="btn">View All Data</a></p>
        </div>
    <?php endif; ?>
</body>
</html>