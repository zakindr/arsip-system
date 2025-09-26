<?php
require_once 'security.php';

class FileUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct($uploadDir = '../uploads/', $maxFileSize = 10485760) { // 10MB default
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->maxFileSize = $maxFileSize;
        
        // Default allowed file types
        $this->allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
            'text/csv'
        ];
        
        $this->createUploadDirectory();
    }
    
    /**
     * Create upload directory if it doesn't exist
     */
    private function createUploadDirectory() {
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new Exception('Cannot create upload directory');
            }
        }
        
        // Create subdirectories by year/month
        $yearMonth = date('Y/m');
        $subDir = $this->uploadDir . $yearMonth . '/';
        if (!is_dir($subDir)) {
            if (!mkdir($subDir, 0755, true)) {
                throw new Exception('Cannot create upload subdirectory');
            }
        }
    }
    
    /**
     * Upload file with security validation
     */
    public function uploadFile($file, $customName = null) {
        try {
            // Validate file upload
            $errors = Security::validateFileUpload($file, $this->allowedTypes, $this->maxFileSize);
            
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors
                ];
            }
            
            // Generate secure filename
            $originalName = $file['name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            if ($customName) {
                $filename = $customName . '.' . $extension;
            } else {
                $filename = $this->generateSecureFilename($originalName);
            }
            
            // Create year/month subdirectory
            $yearMonth = date('Y/m');
            $uploadPath = $this->uploadDir . $yearMonth . '/' . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Log successful upload
                Security::logSecurityEvent('file_upload_success', [
                    'filename' => $filename,
                    'original_name' => $originalName,
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'path' => $uploadPath
                ]);
                
                return [
                    'success' => true,
                    'filename' => $filename,
                    'original_name' => $originalName,
                    'path' => $uploadPath,
                    'url' => 'uploads/' . $yearMonth . '/' . $filename,
                    'size' => $file['size'],
                    'type' => $file['type']
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => ['Failed to move uploaded file']
                ];
            }
            
        } catch (Exception $e) {
            Security::logSecurityEvent('file_upload_exception', [
                'error' => $e->getMessage(),
                'filename' => $file['name'] ?? 'unknown'
            ]);
            
            return [
                'success' => false,
                'errors' => ['Upload failed: ' . $e->getMessage()]
            ];
        }
    }
    
    /**
     * Generate secure filename
     */
    private function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize filename
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nameWithoutExt);
        $sanitizedName = substr($sanitizedName, 0, 50); // Limit length
        
        // Generate unique identifier
        $uniqueId = uniqid();
        $timestamp = date('YmdHis');
        
        return $sanitizedName . '_' . $timestamp . '_' . $uniqueId . '.' . $extension;
    }
    
    /**
     * Delete file
     */
    public function deleteFile($filepath) {
        try {
            if (file_exists($filepath)) {
                if (unlink($filepath)) {
                    Security::logSecurityEvent('file_deleted', [
                        'filepath' => $filepath
                    ]);
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            Security::logSecurityEvent('file_delete_exception', [
                'filepath' => $filepath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get file info
     */
    public function getFileInfo($filepath) {
        if (!file_exists($filepath)) {
            return null;
        }
        
        $info = pathinfo($filepath);
        $info['size'] = filesize($filepath);
        $info['modified'] = date('Y-m-d H:i:s', filemtime($filepath));
        $info['mime_type'] = mime_content_type($filepath);
        
        return $info;
    }
    
    /**
     * Create download link
     */
    public function createDownloadLink($filepath, $displayName = null) {
        if (!file_exists($filepath)) {
            return 'File not found';
        }
        
        $filename = $displayName ?: basename($filepath);
        $encodedFilename = urlencode($filename);
        
        return "<a href='download.php?file=" . urlencode($filepath) . "&name=" . $encodedFilename . "' 
                class='btn btn-sm btn-outline-primary' target='_blank'>
                <i class='fas fa-download'></i> Download
               </a>";
    }
    
    /**
     * Set allowed file types
     */
    public function setAllowedTypes($types) {
        $this->allowedTypes = $types;
    }
    
    /**
     * Get allowed file types
     */
    public function getAllowedTypes() {
        return $this->allowedTypes;
    }
    
    /**
     * Set maximum file size
     */
    public function setMaxFileSize($size) {
        $this->maxFileSize = $size;
    }
    
    /**
     * Get maximum file size
     */
    public function getMaxFileSize() {
        return $this->maxFileSize;
    }
    
    /**
     * Format file size
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get file icon based on extension
     */
    public static function getFileIcon($extension) {
        $icons = [
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc' => 'fas fa-file-word text-primary',
            'docx' => 'fas fa-file-word text-primary',
            'xls' => 'fas fa-file-excel text-success',
            'xlsx' => 'fas fa-file-excel text-success',
            'ppt' => 'fas fa-file-powerpoint text-warning',
            'pptx' => 'fas fa-file-powerpoint text-warning',
            'jpg' => 'fas fa-file-image text-info',
            'jpeg' => 'fas fa-file-image text-info',
            'png' => 'fas fa-file-image text-info',
            'gif' => 'fas fa-file-image text-info',
            'txt' => 'fas fa-file-alt text-secondary',
            'csv' => 'fas fa-file-csv text-success'
        ];
        
        return $icons[strtolower($extension)] ?? 'fas fa-file text-secondary';
    }
}
?>
