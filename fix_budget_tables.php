<?php
// Include database configuration
require_once 'db_config.php';

// Start output buffering
ob_start();

echo "<h1>Budget Tables Fix Script</h1>";

// Check if the budget_items table exists with the wrong structure
$checkBudgetItemsTable = "SHOW TABLES LIKE 'budget_items'";
$budgetItemsTableExists = mysqli_query($conn, $checkBudgetItemsTable) && mysqli_num_rows(mysqli_query($conn, $checkBudgetItemsTable)) > 0;

if ($budgetItemsTableExists) {
    // Check if the table has the wrong structure (missing budget_id column)
    $checkBudgetIdColumn = "SHOW COLUMNS FROM budget_items LIKE 'budget_id'";
    $hasBudgetIdColumn = mysqli_query($conn, $checkBudgetIdColumn) && mysqli_num_rows(mysqli_query($conn, $checkBudgetIdColumn)) > 0;
    
    if (!$hasBudgetIdColumn) {
        echo "<p>The budget_items table exists but has the wrong structure (missing budget_id column).</p>";
        echo "<p>Renaming the existing table to budget_items_old...</p>";
        
        // Rename the existing table
        $renameTable = "RENAME TABLE budget_items TO budget_items_old";
        if (mysqli_query($conn, $renameTable)) {
            echo "<p>Successfully renamed the table.</p>";
        } else {
            echo "<p>Error renaming table: " . mysqli_error($conn) . "</p>";
        }
        
        // Create the correct budget_items table
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
        
        if (mysqli_query($conn, $createBudgetItemsTable)) {
            echo "<p>Successfully created the correct budget_items table.</p>";
        } else {
            echo "<p>Error creating budget_items table: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>The budget_items table already has the correct structure.</p>";
    }
} else {
    echo "<p>The budget_items table does not exist. Creating it now...</p>";
    
    // Create the budget_items table
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
    
    if (mysqli_query($conn, $createBudgetItemsTable)) {
        echo "<p>Successfully created the budget_items table.</p>";
    } else {
        echo "<p>Error creating budget_items table: " . mysqli_error($conn) . "</p>";
    }
}

// Check if budget_comments table exists
$checkBudgetCommentsTable = "SHOW TABLES LIKE 'budget_comments'";
$budgetCommentsTableExists = mysqli_query($conn, $checkBudgetCommentsTable) && mysqli_num_rows(mysqli_query($conn, $checkBudgetCommentsTable)) > 0;

if (!$budgetCommentsTableExists) {
    echo "<p>The budget_comments table does not exist. Creating it now...</p>";
    
    // Create the budget_comments table
    $createBudgetCommentsTable = "CREATE TABLE IF NOT EXISTS budget_comments (
        comment_id INT AUTO_INCREMENT PRIMARY KEY,
        budget_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (budget_id) REFERENCES budgets(budget_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (mysqli_query($conn, $createBudgetCommentsTable)) {
        echo "<p>Successfully created the budget_comments table.</p>";
    } else {
        echo "<p>Error creating budget_comments table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>The budget_comments table already exists.</p>";
}

// Check if budget_history table exists
$checkBudgetHistoryTable = "SHOW TABLES LIKE 'budget_history'";
$budgetHistoryTableExists = mysqli_query($conn, $checkBudgetHistoryTable) && mysqli_num_rows(mysqli_query($conn, $checkBudgetHistoryTable)) > 0;

if (!$budgetHistoryTableExists) {
    echo "<p>The budget_history table does not exist. Creating it now...</p>";
    
    // Create the budget_history table
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
    
    if (mysqli_query($conn, $createBudgetHistoryTable)) {
        echo "<p>Successfully created the budget_history table.</p>";
    } else {
        echo "<p>Error creating budget_history table: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>The budget_history table already exists.</p>";
}

// Get the output buffer content
$output = ob_get_clean();

// Save to file
file_put_contents('fix_budget_tables_results.html', $output);

// Also output to screen
echo $output;
?> 