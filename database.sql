-- Windsor Welfare Database Schema
-- Import this file into MySQL to set up the database

-- Create database


-- Members table
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    department VARCHAR(100),
    join_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Contributions table
CREATE TABLE contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    amount DECIMAL(10,2),
    contribution_date DATE,
    type VARCHAR(50),
    payment_method ENUM('cash', 'card', 'mpesa') DEFAULT 'cash',
    FOREIGN KEY (member_id) REFERENCES users(id)
);

-- Loans table
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    amount DECIMAL(10,2),
    interest_rate DECIMAL(5,2) DEFAULT 0.00,
    total_interest DECIMAL(12,2) DEFAULT 0.00,
    apply_date DATE,
    approve_date DATE,
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    FOREIGN KEY (member_id) REFERENCES users(id)
);

-- Audit logs table
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Loan payments table
CREATE TABLE loan_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id)
);

-- Users table for authentication (admins and members)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE,          -- for staff login (NULL for admins)
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    username VARCHAR(50) UNIQUE,             -- for admin login (NULL for members)
    password VARCHAR(255) NOT NULL,
    role ENUM('member','admin') NOT NULL DEFAULT 'member',
    join_date DATE,
    department VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    password_reset_token VARCHAR(255),       -- token for password reset
    password_reset_expires DATETIME          -- token expiration time
);

-- Insert example data
INSERT INTO users (employee_id, full_name, username, password, role) VALUES
('EMP001', 'Mary Njoki', NULL, '$2y$10$VhardYuMyo34aiOjq.wMZ.9S126VlLU.To.xko4pFaH10LuX2wQ9S', 'member'),
('EMP002', 'John Doe', NULL, $2y$10$VhardYuMyo34aiOjq.wMZ.9S126VlLU.To.xko4pFaH10LuX2wQ9S', 'member'),
(NULL, 'superadmin', 'admin', '$2y$10$VhardYuMyo34aiOjq.wMZ.9S126VlLU.To.xko4pFaH10LuX2wQ9S', 'admin');
-- Add password reset columns to existing users table (if not already present)
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(255);
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_expires DATETIME;