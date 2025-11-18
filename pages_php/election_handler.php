<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Include auto notifications system
require_once __DIR__ . '/includes/auto_notifications.php';

// Require login for this page
requireLogin();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Handle state change actions for elections (admin only)
if (isset($_GET['action']) && in_array($_GET['action'], ['open_nominations', 'close_nominations', 'open_voting', 'close_voting', 'publish_results']) && isset($_GET['id'])) {
    // Only super admin can change election states
    if (!canManageElections()) {
        $_SESSION['error'] = "Only super administrators can change election states.";
        header("Location: elections.php");
        exit();
    }
    
    $electionId = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Get election information
    $sql = "SELECT * FROM elections WHERE election_id = ?";
    $election = fetchOne($sql, [$electionId]);
    
    if (!$election) {
        $_SESSION['error'] = "Election not found.";
        header("Location: elections.php");
        exit();
    }
    
    // Update the election status based on the action
    try {
        $newStatus = '';
        $message = '';
        
        switch ($action) {
            case 'open_nominations':
                $newStatus = 'nomination';
                $message = "Nominations are now open for this election.";
                break;
            
            case 'close_nominations':
                $newStatus = 'pending';
                $message = "Nominations have been closed.";
                break;
            
            case 'open_voting':
                $newStatus = 'active';
                $message = "Voting is now open for this election.";
                break;
            
            case 'close_voting':
                $newStatus = 'completed';
                $message = "Voting has been closed and the election is now complete.";
                break;
                
            case 'publish_results':
                $sql = "UPDATE elections SET results_published = 1 WHERE election_id = ?";
                $result = update($sql, [$electionId]);
                
                if ($result === false) {
                    throw new Exception("Failed to publish election results.");
                }
                
                $_SESSION['success'] = "Election results have been published and are now visible to all users.";
                header("Location: election_results.php?id=" . $electionId);
                exit();
                break;
        }
        
        if (!empty($newStatus)) {
            try {
                // First verify the status is valid
                $statusSql = "SHOW COLUMNS FROM elections WHERE Field = 'status'";
                $statusResult = $conn->query($statusSql);
                $statusColumn = $statusResult->fetch_assoc();
                
                if ($statusColumn) {
                    // Extract the enum values
                    preg_match("/^enum\(\'(.*)\'\)$/", $statusColumn['Type'], $matches);
                    $validStatusValues = explode("','", $matches[1]);
                    
                    if (!in_array($newStatus, $validStatusValues)) {
                        throw new Exception("Status '$newStatus' is not a valid enum value. Valid values are: " . implode(", ", $validStatusValues));
                    }
                }
                
                // Now update the status
                $sql = "UPDATE elections SET status = ? WHERE election_id = ?";
                $result = update($sql, [$newStatus, $electionId]);
                
                if ($result === false) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $_SESSION['success'] = $message;
            } catch (Exception $e) {
                $_SESSION['error'] = "Error updating election status: " . $e->getMessage();
                // If this is likely a database structure issue, provide a fix link
                if (strpos($e->getMessage(), "not a valid enum value") !== false) {
                    $_SESSION['error'] .= " <a href='../fix_status.php' class='alert-link'>Click here to fix database structure</a>.";
                }
            }
        }
    } catch (Exception $e) {
        if (!isset($_SESSION['error'])) { // Only set if not already set by inner catch
            $_SESSION['error'] = "Error updating election status: " . $e->getMessage();
        }
    }
    
    // Redirect to election detail page
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Handle create election
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    // Check permission - only super admin can create elections
    if (!canManageElections()) {
        $_SESSION['error'] = "You don't have permission to create elections.";
        header("Location: elections.php");
        exit();
    }

    // Get form data
    $title = $_POST['election_title'] ?? '';
    $description = $_POST['election_description'] ?? '';
    $startDate = $_POST['election_start_date'] ?? '';
    $endDate = $_POST['election_end_date'] ?? '';
    $status = $_POST['election_status'] ?? 'upcoming';
    
    // Map status values to enum values
    $statusMap = [
        'Planning' => 'upcoming',
        'Upcoming' => 'upcoming',
        'Active' => 'active',
        'Completed' => 'completed',
        'Cancelled' => 'cancelled'
    ];
    
    // Convert status
    $dbStatus = $statusMap[$status] ?? 'upcoming';
    
    // Convert dates to datetime format
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
    
    // Validate input
    if (empty($title) || empty($startDate) || empty($endDate)) {
        $_SESSION['error'] = "Title, start date, and end date are required.";
        header("Location: elections.php");
        exit();
    }
    
    // Check if end date is after start date
    if (strtotime($endDate) <= strtotime($startDate)) {
        $_SESSION['error'] = "End date must be after start date.";
        header("Location: elections.php");
        exit();
    }
    
    // Start database transaction
    $conn->begin_transaction();
    
    try {
        // Insert into elections table
        $sql = "INSERT INTO elections (title, description, start_date, end_date, status) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $title,
            $description,
            $startDateTime,
            $endDateTime,
            $dbStatus
        ];
        
        $electionId = insert($sql, $params);
        
        if (!$electionId) {
            throw new Exception("Failed to create election.");
        }
        
        // Process positions
        if (isset($_POST['positions']) && is_array($_POST['positions']) && isset($_POST['seats']) && is_array($_POST['seats'])) {
            $positions = $_POST['positions'];
            $seats = $_POST['seats'];
            
            for ($i = 0; $i < count($positions); $i++) {
                if (!empty($positions[$i]) && isset($seats[$i]) && $seats[$i] > 0) {
                    $positionTitle = $positions[$i];
                    $positionSeats = intval($seats[$i]);
                    
                    $sql = "INSERT INTO election_positions (election_id, title, seats) 
                            VALUES (?, ?, ?)";
                    
                    $posParams = [
                        $electionId,
                        $positionTitle,
                        $positionSeats
                    ];
                    
                    $positionId = insert($sql, $posParams);
                    
                    if (!$positionId) {
                        throw new Exception("Failed to create position: " . $positionTitle);
                    }
                }
            }
        }
        
        // Commit transaction
        $conn->commit();

        $_SESSION['success'] = "Election created successfully.";

        // Send notification to all users about new election
        autoNotifyElectionCreated($title, $description, getCurrentUser()['user_id'], $electionId);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error creating election: " . $e->getMessage();
    }
    
    header("Location: elections.php");
    exit();
}

// Handle edit election
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    // Check permission - only super admin can edit elections
    if (!canManageElections()) {
        $_SESSION['error'] = "You don't have permission to edit elections.";
        header("Location: elections.php");
        exit();
    }
    
    // Get form data
    $electionId = intval($_POST['election_id'] ?? 0);
    $title = $_POST['election_title'] ?? '';
    $description = $_POST['election_description'] ?? '';
    $startDate = $_POST['election_start_date'] ?? '';
    $endDate = $_POST['election_end_date'] ?? '';
    $status = $_POST['election_status'] ?? 'upcoming';
    
    // Map status values to enum values
    $statusMap = [
        'Planning' => 'upcoming',
        'Upcoming' => 'upcoming',
        'Active' => 'active',
        'Completed' => 'completed',
        'Cancelled' => 'cancelled'
    ];
    
    // Convert status
    $dbStatus = $statusMap[$status] ?? 'upcoming';
    
    // Convert dates to datetime format
    $startDateTime = $startDate . ' 00:00:00';
    $endDateTime = $endDate . ' 23:59:59';
    
    // Validate input
    if (empty($title) || empty($startDate) || empty($endDate) || $electionId <= 0) {
        $_SESSION['error'] = "Invalid input data.";
        header("Location: elections.php");
        exit();
    }
    
    // Check if end date is after start date
    if (strtotime($endDate) <= strtotime($startDate)) {
        $_SESSION['error'] = "End date must be after start date.";
        header("Location: elections.php");
        exit();
    }
    
    // Start database transaction
    $conn->begin_transaction();
    
    try {
        // Update election
        $sql = "UPDATE elections 
                SET title = ?, description = ?, start_date = ?, end_date = ?, status = ? 
                WHERE election_id = ?";
        
        $params = [
            $title,
            $description,
            $startDateTime,
            $endDateTime,
            $dbStatus,
            $electionId
        ];
        
        $result = update($sql, $params);
        
        if ($result === false) {
            throw new Exception("Failed to update election.");
        }
        
        // Process existing positions updates if provided
        if (isset($_POST['existing_positions']) && is_array($_POST['existing_positions']) && 
            isset($_POST['existing_position_ids']) && is_array($_POST['existing_position_ids']) && 
            isset($_POST['existing_seats']) && is_array($_POST['existing_seats'])) {
            
            $existingPositions = $_POST['existing_positions'];
            $existingPositionIds = $_POST['existing_position_ids'];
            $existingSeats = $_POST['existing_seats'];
            
            for ($i = 0; $i < count($existingPositionIds); $i++) {
                if (!empty($existingPositions[$i]) && isset($existingSeats[$i]) && $existingSeats[$i] > 0) {
                    $positionId = intval($existingPositionIds[$i]);
                    $positionTitle = $existingPositions[$i];
                    $positionSeats = intval($existingSeats[$i]);
                    
                    $sql = "UPDATE election_positions 
                            SET title = ?, seats = ? 
                            WHERE position_id = ? AND election_id = ?";
                    
                    $posParams = [
                        $positionTitle,
                        $positionSeats,
                        $positionId,
                        $electionId
                    ];
                    
                    $result = update($sql, $posParams);
                    
                    if ($result === false) {
                        throw new Exception("Failed to update position: " . $positionTitle);
                    }
                }
            }
        }
        
        // Process new positions
        if (isset($_POST['new_positions']) && is_array($_POST['new_positions']) && 
            isset($_POST['new_seats']) && is_array($_POST['new_seats'])) {
            
            $newPositions = $_POST['new_positions'];
            $newSeats = $_POST['new_seats'];
            
            for ($i = 0; $i < count($newPositions); $i++) {
                if (!empty($newPositions[$i]) && isset($newSeats[$i]) && $newSeats[$i] > 0) {
                    $positionTitle = $newPositions[$i];
                    $positionSeats = intval($newSeats[$i]);
                    
                    $sql = "INSERT INTO election_positions (election_id, title, seats) 
                            VALUES (?, ?, ?)";
                    
                    $posParams = [
                        $electionId,
                        $positionTitle,
                        $positionSeats
                    ];
                    
                    $positionId = insert($sql, $posParams);
                    
                    if (!$positionId) {
                        throw new Exception("Failed to create new position: " . $positionTitle);
                    }
                }
            }
        }
        
        // Process position deletions
        if (isset($_POST['deleted_position_ids']) && !empty($_POST['deleted_position_ids'])) {
            $deletedPositionIds = explode(',', $_POST['deleted_position_ids']);
            
            foreach ($deletedPositionIds as $positionId) {
                if (!empty($positionId)) {
                    $positionId = intval($positionId);
                    
                    $sql = "DELETE FROM election_positions 
                            WHERE position_id = ? AND election_id = ?";
                    
                    $delParams = [
                        $positionId,
                        $electionId
                    ];
                    
                    $result = delete($sql, $delParams);
                    
                    if ($result === false) {
                        throw new Exception("Failed to delete position ID: " . $positionId);
                    }
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Election updated successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error updating election: " . $e->getMessage();
    }
    
    header("Location: elections.php");
    exit();
}

// Handle delete election
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $electionId = intval($_GET['id']);
    
    // Check permission - only super admin can delete elections
    if (!canManageElections()) {
        $_SESSION['error'] = "You don't have permission to delete elections.";
        header("Location: elections.php");
        exit();
    }
    
    // Get election information
    $sql = "SELECT * FROM elections WHERE election_id = ?";
    $election = fetchOne($sql, [$electionId]);
    
    if (!$election) {
        $_SESSION['error'] = "Election not found.";
        header("Location: elections.php");
        exit();
    }
    
    // Start database transaction
    $conn->begin_transaction();
    
    try {
        // Delete candidates (should be handled by foreign key cascade, but just to be safe)
        $sql = "DELETE FROM election_candidates WHERE election_id = ?";
        delete($sql, [$electionId]);
        
        // Delete positions (should be handled by foreign key cascade, but just to be safe)
        $sql = "DELETE FROM election_positions WHERE election_id = ?";
        delete($sql, [$electionId]);
        
        // Delete election
        $sql = "DELETE FROM elections WHERE election_id = ?";
        $result = delete($sql, [$electionId]);
        
        if ($result === false) {
            throw new Exception("Failed to delete election.");
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Election deleted successfully.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error deleting election: " . $e->getMessage();
    }
    
    header("Location: elections.php");
    exit();
}

// If no action was performed, redirect back to elections page
header("Location: elections.php");
exit();
?> 
