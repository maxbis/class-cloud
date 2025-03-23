-- Create database
CREATE DATABASE IF NOT EXISTS class_cloud;
USE class_cloud;

-- Teachers table
CREATE TABLE IF NOT EXISTS teachers (
    teacher_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    access_code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    bulletpoint_limit INT DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id)
);

-- Bullet points table
CREATE TABLE IF NOT EXISTS bullet_points (
    bulletpoint_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    order_position INT DEFAULT 0,
    cloud_x INT DEFAULT NULL,
    cloud_y INT DEFAULT NULL,
    is_in_cloud BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);

-- Keywords table for cloud visualization
CREATE TABLE IF NOT EXISTS keywords (
    keyword_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    word VARCHAR(50) NOT NULL,
    frequency INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id)
);

-- Create indexes for better performance
CREATE INDEX idx_access_code ON sessions(access_code);
CREATE INDEX idx_session_active ON sessions(is_active);
CREATE INDEX idx_bulletpoint_session ON bullet_points(session_id);
CREATE INDEX idx_keyword_session ON keywords(session_id); 