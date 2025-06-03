<?php
// Include necessary files
require_once 'auth_functions.php';
require_once 'db_config.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    // Redirect to login or access denied page
    header("Location: pages_php/login.php");
    exit();
}

// Initialize variables
$successMessage = '';
$errorMessage = '';
$users = [];

// Fetch users who are not already admins
$sql = "SELECT user_id, username, first_name, last_name, email, role, status FROM users WHERE role != 'admin' ORDER BY username";
$users = fetchAll($sql);

// Process single user promotion
if (isset($_GET['promote']) && is_numeric($_GET['promote'])) {
    $userId = (int)$_GET['promote'];
    
    // Direct database update approach (more reliable)
    global $conn;
    $updateSql = "UPDATE users SET role = 'member' WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $updateSql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        $result = mysqli_stmt_execute($stmt);
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        
        if ($result && $affectedRows > 0) {
            $successMessage = "User successfully promoted to member role. This user now has access to create events, news, documents, gallery, elections, minutes, reports, budgets, and respond to feedback.";
        } else {
            $errorMessage = "Failed to promote user. Please try again.";
        }
    } else {
        $errorMessage = "Failed to prepare database query. Please try again.";
    }
    
    // Refresh user list
    $users = fetchAll($sql);
}

// Process promotion form for multiple users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_users'])) {
    if (isset($_POST['selected_users']) && is_array($_POST['selected_users'])) {
        $selectedUsers = $_POST['selected_users'];
        
        if (empty($selectedUsers)) {
            $errorMessage = "No users selected for promotion.";
        } else {
            $successCount = 0;
            $errorCount = 0;
            global $conn;
            
            foreach ($selectedUsers as $userId) {
                // Direct database update approach (more reliable)
                $updateSql = "UPDATE users SET role = 'member' WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $updateSql);
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $userId);
                    $result = mysqli_stmt_execute($stmt);
                    $affectedRows = mysqli_stmt_affected_rows($stmt);
                    mysqli_stmt_close($stmt);
                    
                    if ($result && $affectedRows > 0) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            }
            
            if ($successCount > 0) {
                $successMessage = "$successCount user(s) successfully promoted to member role. These users now have access to create events, news, documents, gallery, elections, minutes, reports, budgets, and respond to feedback.";
            }
            
            if ($errorCount > 0) {
                $errorMessage = "$errorCount user(s) could not be promoted. Please try again.";
            }
            
            // Refresh user list
            $users = fetchAll($sql);
        }
    } else {
        $errorMessage = "No users selected for promotion.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promote Users to Member Role</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-users-cog me-2"></i>Promote Users to Member Role</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($errorMessage): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Members have access to create and manage events, news, documents, gallery items, elections, minutes, reports, budgets, and respond to feedback.
                        </div>
                        
                        <?php if (empty($users)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>No users available for promotion.
                        </div>
                        <?php else: ?>
                        <form method="POST" action="">
                            <div class="table-responsive">
                                <table id="users-table" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th width="40">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="select-all">
                                                </div>
                                            </th>
                                            <th>Username</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Current Role</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input user-checkbox" type="checkbox" name="selected_users[]" value="<?php echo $user['user_id']; ?>" <?php echo $user['role'] === 'member' ? 'disabled checked' : ''; ?>>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] === 'member' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['role'] !== 'member'): ?>
                                                <a href="?promote=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-user-shield me-1"></i>Promote
                                                </a>
                                                <?php else: ?>
                                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>Member</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-grid gap-2 mt-3">
                                <button type="submit" name="promote_users" class="btn btn-primary">
                                    <i class="fas fa-user-shield me-2"></i>Promote Selected Users to Member
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                                </a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#users-table').DataTable({
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
            
            // Select All checkbox functionality
            $('#select-all').change(function() {
                $('.user-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
            });
            
            // Update select all checkbox when individual checkboxes change
            $('.user-checkbox').change(function() {
                if ($('.user-checkbox:not(:disabled)').length === $('.user-checkbox:checked:not(:disabled)').length) {
                    $('#select-all').prop('checked', true);
                } else {
                    $('#select-all').prop('checked', false);
                }
            });
        });
    </script>
</body>
</html> 