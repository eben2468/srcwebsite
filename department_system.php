<?php
// Simple dashboard interface for the departments system
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department System Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-success {
            background-color: #28a745;
        }
        .status-warning {
            background-color: #ffc107;
        }
        .status-danger {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">School Departments System Management</h1>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        Department System Components
                    </div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <span>
                                <span class="status-indicator status-success"></span>
                                Main Departments Page
                            </span>
                            <a href="pages_php/departments.php" class="btn btn-sm btn-primary">Visit</a>
                        </div>
                        <div class="list-group-item">
                            <span>
                                <span class="status-indicator status-success"></span>
                                Department Detail Pages
                            </span>
                            <div>
                                <a href="pages_php/department-detail.php?code=NURSA" class="btn btn-sm btn-outline-primary me-1">NURSA</a>
                                <a href="pages_php/department-detail.php?code=THEMSA" class="btn btn-sm btn-outline-primary me-1">THEMSA</a>
                                <a href="pages_php/department-detail.php?code=EDSA" class="btn btn-sm btn-outline-primary me-1">EDSA</a>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <span>
                                <span class="status-indicator status-success"></span>
                                Admin Functionality
                            </span>
                            <div>
                                <a href="pages_php/departments.php" class="btn btn-sm btn-outline-success me-1">Manage Departments</a>
                                <a href="pages_php/department-detail.php?code=NURSA&admin=1" class="btn btn-sm btn-outline-info me-1">Example Admin View</a>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <span>
                                <span class="status-indicator status-success"></span>
                                Image Placeholders
                            </span>
                            <a href="images/departments/" class="btn btn-sm btn-info">View Folder</a>
                        </div>
                        <div class="list-group-item">
                            <span>
                                <span class="status-indicator status-success"></span>
                                Document Placeholders
                            </span>
                            <a href="documents/departments/" class="btn btn-sm btn-info">View Folder</a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        Maintenance Tools
                    </div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <span>
                                <i class="fas fa-tools me-2"></i>
                                Create Placeholder Files
                            </span>
                            <a href="create_placeholder_files.php" class="btn btn-sm btn-secondary">Run</a>
                        </div>
                        <div class="list-group-item">
                            <span>
                                <i class="fas fa-check-circle me-2"></i>
                                Check GD Library Status
                            </span>
                            <a href="check_gd.php" class="btn btn-sm btn-secondary">Run</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        System Info
                    </div>
                    <div class="card-body">
                        <p><strong>Department System Version:</strong> 1.0</p>
                        <p><strong>Last Updated:</strong> <?php echo date('F j, Y'); ?></p>
                        <p><strong>Total Departments:</strong> 6</p>
                        <hr>
                        <h6>Departments:</h6>
                        <ul>
                            <li>School of Nursing and Midwifery (NURSA)</li>
                            <li>School of Theology and Mission (THEMSA)</li>
                            <li>School of Education (EDSA)</li>
                            <li>Faculty of Science (COSSA)</li>
                            <li>Development Studies (DESSA)</li>
                            <li>School of Business (SOBSA)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        Implementation Notes
                    </div>
                    <div class="card-body">
                        <p>The School Departments section includes:</p>
                        <ul>
                            <li>Main departments listing page</li>
                            <li>Individual department detail pages</li>
                            <li>Department profile information</li>
                            <li>Programs, events, contacts and documents</li>
                            <li>Media gallery for each department</li>
                        </ul>
                        <hr>
                        <p><strong>Admin Features:</strong></p>
                        <ul>
                            <li>Create, update, and delete departments</li>
                            <li>Manage department events, documents, staff, and gallery</li>
                            <li>All admin operations accessible from intuitive UI</li>
                        </ul>
                        <hr>
                        <p class="mb-0"><strong>Note:</strong> In a production environment, you would replace placeholder files with actual images and documents.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 