-- Windsor Welfare Database Schema
-- Import this file into MySQL to set up the database

-- Create database
CREATE DATABASE IF NOT EXISTS windsor_welfare;
USE windsor_welfare;

-- Members table
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
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
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Loans table
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    amount DECIMAL(10,2),
    interest_rate DECIMAL(5,2),
    apply_date DATE,
    approve_date DATE,
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Loan payments table
CREATE TABLE loan_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT,
    amount DECIMAL(10,2),
    payment_date DATE,
    FOREIGN KEY (loan_id) REFERENCES loans(id)
);

-- Users table for admin
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user'
);