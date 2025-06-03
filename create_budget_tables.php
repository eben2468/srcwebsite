<?php
// Include database configuration
require_once 'db_config.php';

// Check if budgets table already exists
$checkBudgetsTable = "SHOW TABLES LIKE 'budgets'";
$budgetsTableExists = mysqli_query($conn, $checkBudgetsTable) && mysqli_num_rows(mysqli_query($conn, $checkBudgetsTable)) > 0;

// Check if budget_items table already exists
$checkBudgetItemsTable = "SHOW TABLES LIKE 'budget_items'";
$budgetItemsTableExists = mysqli_query($conn, $checkBudgetItemsTable) && mysqli_num_rows(mysqli_query($conn, $checkBudgetItemsTable)) > 0;

// Check if budget_comments table already exists
$checkBudgetCommentsTable = "SHOW TABLES LIKE 'budget_comments'";
$budgetCommentsTableExists = mysqli_query($conn, $checkBudgetCommentsTable) && mysqli_num_rows(mysqli_query($conn, $checkBudgetCommentsTable)) > 0;

// Check if budget_history table already exists
$checkBudgetHistoryTable = "SHOW TABLES LIKE 'budget_history'";
$budgetHistoryTableExists = mysqli_query($conn, $checkBudgetHistoryTable) && mysqli_num_rows(mysqli_query($conn, $checkBudgetHistoryTable)) > 0;

// Create budgets table if it doesn't exist
if (!$budgetsTableExists) {
    $createBudgetsTable = "CREATE TABLE IF NOT EXISTS budgets (
        budget_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        amount DECIMAL(15,2) NOT NULL,
        status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
        category VARCHAR(100),
        department_id INT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    try {
        mysqli_query($conn, $createBudgetsTable);
        echo "Budgets table created successfully!<br>";
    } catch (Exception $e) {
        echo "Error creating budgets table: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Budgets table already exists.<br>";
}

// Create budget items table if it doesn't exist
if (!$budgetItemsTableExists) {
    $createBudgetItemsTable = "CREATE TABLE IF NOT EXISTS budget_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        amount DECIMAL(15,2) NOT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (budget_id) REFERENCES budgets(budget_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    try {
        mysqli_query($conn, $createBudgetItemsTable);
        echo "Budget items table created successfully!<br>";
    } catch (Exception $e) {
        echo "Error creating budget items table: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Budget items table already exists.<br>";
}

// Create budget comments table if it doesn't exist
if (!$budgetCommentsTableExists) {
    $createBudgetCommentsTable = "CREATE TABLE IF NOT EXISTS budget_comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (budget_id) REFERENCES budgets(budget_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    try {
        mysqli_query($conn, $createBudgetCommentsTable);
        echo "Budget comments table created successfully!<br>";
    } catch (Exception $e) {
        echo "Error creating budget comments table: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Budget comments table already exists.<br>";
}

// Create budget history table if it doesn't exist
if (!$budgetHistoryTableExists) {
    $createBudgetHistoryTable = "CREATE TABLE IF NOT EXISTS budget_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        old_status VARCHAR(50),
        new_status VARCHAR(50),
        old_amount DECIMAL(15,2),
        new_amount DECIMAL(15,2),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (budget_id) REFERENCES budgets(budget_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    try {
        mysqli_query($conn, $createBudgetHistoryTable);
        echo "Budget history table created successfully!<br>";
    } catch (Exception $e) {
        echo "Error creating budget history table: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Budget history table already exists.<br>";
}

// Insert sample data if budgets table is empty
try {
    // Check if we already have budget data
    $checkBudgets = "SELECT COUNT(*) as count FROM budgets";
    $result = mysqli_query($conn, $checkBudgets);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        // Get a user ID to use as created_by
        $userQuery = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
        $userResult = mysqli_query($conn, $userQuery);
        $userId = 1; // Default fallback
        
        if ($userResult && mysqli_num_rows($userResult) > 0) {
            $userRow = mysqli_fetch_assoc($userResult);
            $userId = $userRow['user_id'];
        }
        
        // Insert sample budgets
        $sampleBudgets = [
            [
                'title' => 'Annual Student Events',
                'description' => 'Budget for organizing student events throughout the academic year',
                'amount' => 8000.00,
                'status' => 'approved',
                'category' => 'Events',
                'created_by' => $userId
            ],
            [
                'title' => 'Campus Improvement Project',
                'description' => 'Funds for improving campus facilities and infrastructure',
                'amount' => 12000.00,
                'status' => 'pending',
                'category' => 'Infrastructure',
                'created_by' => $userId
            ],
            [
                'title' => 'Student Welfare Program',
                'description' => 'Budget for student welfare initiatives and support services',
                'amount' => 5500.00,
                'status' => 'approved',
                'category' => 'Welfare',
                'created_by' => $userId
            ],
            [
                'title' => 'Sports Equipment Purchase',
                'description' => 'Funds for purchasing new sports equipment for student teams',
                'amount' => 3000.00,
                'status' => 'pending',
                'category' => 'Sports',
                'created_by' => $userId
            ],
            [
                'title' => 'Technology Upgrade',
                'description' => 'Budget for upgrading technology in student common areas',
                'amount' => 7500.00,
                'status' => 'declined',
                'category' => 'Technology',
                'created_by' => $userId
            ]
        ];
        
        foreach ($sampleBudgets as $budget) {
            $insertBudget = "INSERT INTO budgets (title, description, amount, status, category, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insertBudget);
            mysqli_stmt_bind_param($stmt, 'ssdssi', 
                $budget['title'], 
                $budget['description'], 
                $budget['amount'], 
                $budget['status'], 
                $budget['category'], 
                $budget['created_by']
            );
            mysqli_stmt_execute($stmt);
            
            $budgetId = mysqli_insert_id($conn);
            
            // Add some sample items for this budget
            if ($budgetId) {
                $sampleItems = [
                    [
                        'name' => 'Item 1 for ' . $budget['title'],
                        'description' => 'Description for item 1',
                        'amount' => $budget['amount'] * 0.4,
                        'quantity' => 2
                    ],
                    [
                        'name' => 'Item 2 for ' . $budget['title'],
                        'description' => 'Description for item 2',
                        'amount' => $budget['amount'] * 0.6,
                        'quantity' => 1
                    ]
                ];
                
                foreach ($sampleItems as $item) {
                    $insertItem = "INSERT INTO budget_items (budget_id, name, description, amount, quantity) 
                                  VALUES (?, ?, ?, ?, ?)";
                    $itemStmt = mysqli_prepare($conn, $insertItem);
                    mysqli_stmt_bind_param($itemStmt, 'issdi', 
                        $budgetId, 
                        $item['name'], 
                        $item['description'], 
                        $item['amount'], 
                        $item['quantity']
                    );
                    mysqli_stmt_execute($itemStmt);
                }
            }
        }
        
        echo "Sample budget data inserted successfully!<br>";
    } else {
        echo "Budget data already exists. No sample data inserted.<br>";
    }
} catch (Exception $e) {
    echo "Error inserting sample data: " . $e->getMessage() . "<br>";
}
?> 