<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/security.php';

class ArchiveAPI {
    private $auth;
    private $db;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->db = Database::getInstance();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $_GET['path'] ?? '';
        
        try {
            switch ($method) {
                case 'GET':
                    return $this->handleGet($path);
                case 'POST':
                    return $this->handlePost($path);
                case 'PUT':
                    return $this->handlePut($path);
                case 'DELETE':
                    return $this->handleDelete($path);
                default:
                    return $this->errorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Security::logSecurityEvent('api_exception', [
                'error' => $e->getMessage(),
                'path' => $path,
                'method' => $method
            ]);
            
            return $this->errorResponse('Internal server error', 500);
        }
    }
    
    private function handleGet($path) {
        switch ($path) {
            case 'archives':
                return $this->getArchives();
            case 'archive':
                return $this->getArchive();
            case 'search':
                return $this->searchArchives();
            case 'stats':
                return $this->getStats();
            case 'user':
                return $this->getUser();
            default:
                return $this->errorResponse('Endpoint not found', 404);
        }
    }
    
    private function handlePost($path) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($path) {
            case 'login':
                return $this->login($input);
            case 'archive':
                return $this->createArchive($input);
            case 'user':
                return $this->createUser($input);
            default:
                return $this->errorResponse('Endpoint not found', 404);
        }
    }
    
    private function handlePut($path) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($path) {
            case 'archive':
                return $this->updateArchive($input);
            case 'user':
                return $this->updateUser($input);
            default:
                return $this->errorResponse('Endpoint not found', 404);
        }
    }
    
    private function handleDelete($path) {
        switch ($path) {
            case 'archive':
                return $this->deleteArchive();
            case 'user':
                return $this->deleteUser();
            default:
                return $this->errorResponse('Endpoint not found', 404);
        }
    }
    
    private function getArchives() {
        if (!$this->auth->isLoggedIn()) {
            return $this->errorResponse('Authentication required', 401);
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 25);
        $offset = ($page - 1) * $limit;
        
        $total = $this->db->fetchOne("SELECT COUNT(*) as count FROM mytable")['count'] ?? 0;
        $archives = $this->db->fetchAll(
            "SELECT * FROM mytable ORDER BY `NO` ASC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
        
        return $this->successResponse([
            'archives' => $archives,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    private function getArchive() {
        if (!$this->auth->isLoggedIn()) {
            return $this->errorResponse('Authentication required', 401);
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('Archive ID required', 400);
        }
        
        $archive = $this->db->fetchOne("SELECT * FROM mytable WHERE `NO` = ?", [$id]);
        
        if (!$archive) {
            return $this->errorResponse('Archive not found', 404);
        }
        
        return $this->successResponse($archive);
    }
    
    private function searchArchives() {
        if (!$this->auth->isLoggedIn()) {
            return $this->errorResponse('Authentication required', 401);
        }
        
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            return $this->errorResponse('Search query required', 400);
        }
        
        $sql = "SELECT * FROM mytable WHERE 
                `NOMOR ARSIP` LIKE ? OR 
                `KODE KLASIFIKASI` LIKE ? OR 
                `PERIHAL` LIKE ? OR 
                `URAIAN` LIKE ? OR 
                `TAHUN` LIKE ?";
        
        $searchTerm = "%$query%";
        $results = $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        
        return $this->successResponse([
            'query' => $query,
            'results' => $results,
            'count' => count($results)
        ]);
    }
    
    private function getStats() {
        if (!$this->auth->isLoggedIn()) {
            return $this->errorResponse('Authentication required', 401);
        }
        
        $totalArchives = $this->db->fetchOne("SELECT COUNT(*) as count FROM mytable")['count'] ?? 0;
        $totalUsers = $this->db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
        $recentArchives = $this->db->fetchOne("SELECT COUNT(*) as count FROM mytable WHERE YEAR(`TAHUN`) = YEAR(CURDATE())")['count'] ?? 0;
        
        return $this->successResponse([
            'total_archives' => $totalArchives,
            'total_users' => $totalUsers,
            'recent_archives' => $recentArchives
        ]);
    }
    
    private function getUser() {
        if (!$this->auth->isLoggedIn()) {
            return $this->errorResponse('Authentication required', 401);
        }
        
        return $this->successResponse($this->auth->getCurrentUser());
    }
    
    private function login($input) {
        if (!isset($input['username']) || !isset($input['password'])) {
            return $this->errorResponse('Username and password required', 400);
        }
        
        $result = $this->auth->login($input['username'], $input['password']);
        
        if ($result['success']) {
            return $this->successResponse([
                'message' => 'Login successful',
                'user' => $this->auth->getCurrentUser()
            ]);
        } else {
            return $this->errorResponse($result['message'], 401);
        }
    }
    
    private function createArchive($input) {
        if (!$this->auth->requireAdmin()) {
            return $this->errorResponse('Admin privileges required', 403);
        }
        
        // Validate required fields
        $requiredFields = ['nomor_arsip', 'kode_klasifikasi', 'perihal', 'bentuk_redaksi', 'tingkat_perkembangan', 'tahun'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                return $this->errorResponse("Field '$field' is required", 400);
            }
        }
        
        $data = [
            'NOMOR ARSIP' => Security::sanitizeInput($input['nomor_arsip'], 'string'),
            'KODE KLASIFIKASI' => Security::sanitizeInput($input['kode_klasifikasi'], 'string'),
            'PERIHAL' => Security::sanitizeInput($input['perihal'], 'string'),
            'BENTUK REDAKSI' => Security::sanitizeInput($input['bentuk_redaksi'], 'string'),
            'TINGKAT PERKEMBANGAN' => Security::sanitizeInput($input['tingkat_perkembangan'], 'string'),
            'URAIAN' => Security::sanitizeInput($input['uraian'] ?? '', 'string'),
            'TAHUN' => Security::sanitizeInput($input['tahun'], 'int'),
            'FILE' => Security::sanitizeInput($input['file'] ?? '', 'string')
        ];
        
        $insertId = $this->db->insert('mytable', $data);
        
        if ($insertId) {
            Security::logSecurityEvent('api_archive_created', [
                'archive_id' => $insertId,
                'user_id' => $_SESSION['user_id']
            ]);
            
            return $this->successResponse([
                'message' => 'Archive created successfully',
                'id' => $insertId
            ]);
        } else {
            return $this->errorResponse('Failed to create archive', 500);
        }
    }
    
    private function updateArchive($input) {
        if (!$this->auth->requireAdmin()) {
            return $this->errorResponse('Admin privileges required', 403);
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('Archive ID required', 400);
        }
        
        $data = [];
        $allowedFields = ['nomor_arsip', 'kode_klasifikasi', 'perihal', 'bentuk_redaksi', 'tingkat_perkembangan', 'uraian', 'tahun', 'file'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $dbField = strtoupper(str_replace('_', ' ', $field));
                $data[$dbField] = Security::sanitizeInput($input[$field], 'string');
            }
        }
        
        if (empty($data)) {
            return $this->errorResponse('No valid fields to update', 400);
        }
        
        $affectedRows = $this->db->update('mytable', $data, '`NO` = ?', [$id]);
        
        if ($affectedRows > 0) {
            Security::logSecurityEvent('api_archive_updated', [
                'archive_id' => $id,
                'user_id' => $_SESSION['user_id']
            ]);
            
            return $this->successResponse(['message' => 'Archive updated successfully']);
        } else {
            return $this->errorResponse('Archive not found or no changes made', 404);
        }
    }
    
    private function deleteArchive() {
        if (!$this->auth->requireAdmin()) {
            return $this->errorResponse('Admin privileges required', 403);
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('Archive ID required', 400);
        }
        
        $affectedRows = $this->db->delete('mytable', '`NO` = ?', [$id]);
        
        if ($affectedRows > 0) {
            Security::logSecurityEvent('api_archive_deleted', [
                'archive_id' => $id,
                'user_id' => $_SESSION['user_id']
            ]);
            
            return $this->successResponse(['message' => 'Archive deleted successfully']);
        } else {
            return $this->errorResponse('Archive not found', 404);
        }
    }
    
    private function createUser($input) {
        if (!$this->auth->requireAdmin()) {
            return $this->errorResponse('Admin privileges required', 403);
        }
        
        if (!isset($input['username']) || !isset($input['password']) || !isset($input['role'])) {
            return $this->errorResponse('Username, password, and role are required', 400);
        }
        
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        
        try {
            $insertId = $this->db->insert('users', [
                'username' => Security::sanitizeInput($input['username'], 'string'),
                'password' => $hashedPassword,
                'role' => Security::sanitizeInput($input['role'], 'string')
            ]);
            
            Security::logSecurityEvent('api_user_created', [
                'user_id' => $insertId,
                'created_by' => $_SESSION['user_id']
            ]);
            
            return $this->successResponse([
                'message' => 'User created successfully',
                'id' => $insertId
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Username already exists', 409);
        }
    }
    
    private function updateUser($input) {
        if (!$this->auth->requireAdmin()) {
            return $this->errorResponse('Admin privileges required', 403);
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('User ID required', 400);
        }
        
        $data = [];
        
        if (isset($input['username'])) {
            $data['username'] = Security::sanitizeInput($input['username'], 'string');
        }
        
        if (isset($input['password']) && !empty($input['password'])) {
            $data['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($input['role'])) {
            $data['role'] = Security::sanitizeInput($input['role'], 'string');
        }
        
        if (empty($data)) {
            return $this->errorResponse('No valid fields to update', 400);
        }
        
        try {
            $affectedRows = $this->db->update('users', $data, 'id = ?', [$id]);
            
            if ($affectedRows > 0) {
                Security::logSecurityEvent('api_user_updated', [
                    'user_id' => $id,
                    'updated_by' => $_SESSION['user_id']
                ]);
                
                return $this->successResponse(['message' => 'User updated successfully']);
            } else {
                return $this->errorResponse('User not found', 404);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Username already exists', 409);
        }
    }
    
    private function deleteUser() {
        if (!$this->auth->requireAdmin()) {
            return $this->errorResponse('Admin privileges required', 403);
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            return $this->errorResponse('User ID required', 400);
        }
        
        if ($id == $_SESSION['user_id']) {
            return $this->errorResponse('Cannot delete your own account', 400);
        }
        
        $affectedRows = $this->db->delete('users', 'id = ?', [$id]);
        
        if ($affectedRows > 0) {
            Security::logSecurityEvent('api_user_deleted', [
                'user_id' => $id,
                'deleted_by' => $_SESSION['user_id']
            ]);
            
            return $this->successResponse(['message' => 'User deleted successfully']);
        } else {
            return $this->errorResponse('User not found', 404);
        }
    }
    
    private function successResponse($data) {
        return [
            'status' => 'success',
            'data' => $data,
            'timestamp' => date('c')
        ];
    }
    
    private function errorResponse($message, $code = 400) {
        http_response_code($code);
        return [
            'status' => 'error',
            'message' => $message,
            'code' => $code,
            'timestamp' => date('c')
        ];
    }
}

// Handle the API request
$api = new ArchiveAPI();
$response = $api->handleRequest();
echo json_encode($response, JSON_PRETTY_PRINT);
?>
