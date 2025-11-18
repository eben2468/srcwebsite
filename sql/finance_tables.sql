-- Budget Table
CREATE TABLE IF NOT EXISTS budget (
    budget_id INT AUTO_INCREMENT PRIMARY KEY,
    fiscal_year VARCHAR(10) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    allocated_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('draft', 'approved', 'closed') NOT NULL DEFAULT 'draft',
    created_by INT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Budget Categories Table
CREATE TABLE IF NOT EXISTS budget_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    allocated_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    spent_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budget(budget_id) ON DELETE CASCADE
);

-- Budget Transactions Table
CREATE TABLE IF NOT EXISTS budget_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('income', 'expense') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    document_id INT,
    created_by INT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES budget_categories(category_id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Budget History Table
CREATE TABLE IF NOT EXISTS budget_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES budget_transactions(transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert Default Budget Categories
INSERT INTO budget (fiscal_year, total_amount, allocated_amount, remaining_amount, status, created_by)
VALUES ('2023/2024', 120000.00, 0.00, 120000.00, 'approved', 1);

-- Get the inserted budget_id
SET @budget_id = LAST_INSERT_ID();

-- Insert default categories
INSERT INTO budget_categories (budget_id, name, description, allocated_amount)
VALUES
(@budget_id, 'Events & Programs', 'Budget for all SRC events and programs', 35000.00),
(@budget_id, 'Administrative', 'Office supplies and administrative expenses', 20000.00),
(@budget_id, 'Welfare & Support', 'Student welfare and support initiatives', 18000.00),
(@budget_id, 'Marketing & Communications', 'Marketing materials and communication expenses', 15000.00),
(@budget_id, 'Training & Development', 'Training programs and development initiatives', 12000.00),
(@budget_id, 'Contingency', 'Emergency and unforeseen expenses', 10000.00),
(@budget_id, 'Capital Expenses', 'Long-term investments and assets', 10000.00);

-- Update allocated amount in budget table
UPDATE budget 
SET allocated_amount = (SELECT SUM(allocated_amount) FROM budget_categories WHERE budget_id = @budget_id),
    remaining_amount = total_amount - (SELECT SUM(allocated_amount) FROM budget_categories WHERE budget_id = @budget_id)
WHERE budget_id = @budget_id; 