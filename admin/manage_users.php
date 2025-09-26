<?php
require_once '../includes/auth.php';
require_once '../includes/database.php';

$auth = new Auth();
$auth->requireAdmin();
$db = Database::getInstance();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';

// Handle form submissions
if ($_POST) {
    switch ($action) {
        case 'add':
            $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            if ($stmt->execute([$_POST['username'], $hashedPassword, $_POST['role']])) {
                $message = 'User added successfully!';
            }
            break;
            
        case 'edit':
            if (!empty($_POST['password'])) {
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$_POST['username'], $hashedPassword, $_POST['role'], $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmt->execute([$_POST['username'], $_POST['role'], $id]);
            }
            $message = 'User updated successfully!';
            break;
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        header('Location: manage_users.php');
        exit;
    }
}

// Get users
$users = $db->fetchAll("SELECT * FROM users ORDER BY id");

// Get user for editing
$user = null;
if ($action === 'edit' && $id) {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Sistem Arsip Unmul</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header admin">
        <h1>Manage Users</h1>
        <a href="../dashboard.php" style="color: white; float: right;">← Back to Dashboard</a>
    </div>
    
    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($action === 'add' || $action === 'edit'): ?>
        <div style="background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <h3><?= $action === 'edit' ? 'Edit' : 'Add' ?> User</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" value="<?= $user['username'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Password <?= $action === 'edit' ? '(leave blank to keep current)' : '' ?>:</label>
                    <input type="password" name="password" <?= $action === 'add' ? 'required' : '' ?>>
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" style="width: 100%; padding: 8px; max-width: 400px;">
                        <option value="user" <?= ($user['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn"><?= $action === 'edit' ? 'Update' : 'Add' ?> User</button>
                <a href="manage_users.php" class="btn" style="background: #6c757d;">Cancel</a>
            </form>
        </div>
    <?php endif; ?>
    
    <div style="background: white; padding: 20px; border-radius: 5px;">
        <div style="margin-bottom: 15px;">
            <a href="?action=add" class="btn">Add New User</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= ucfirst($u['role']) ?></td>
                        <td><?= $u['created_at'] ?></td>
                        <td>
                            <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-edit">Edit</a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="?action=delete&id=<?= $u['id'] ?>" 
                                   class="btn btn-delete" 
                                   onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>