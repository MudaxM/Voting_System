-- Create Database
CREATE DATABASE IF NOT EXISTS student_voting_system;
USE student_voting_system;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    has_voted BOOLEAN DEFAULT 0,
    is_admin BOOLEAN DEFAULT 0,
    is_verified BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Positions table
CREATE TABLE positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    max_candidates INT DEFAULT 3,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Candidates table
CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    position_id INT,
    student_id VARCHAR(20),
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    manifesto TEXT,
    photo VARCHAR(255) DEFAULT 'default.jpg',
    votes INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE SET NULL
);

-- Votes table
CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voter_id INT NOT NULL,
    position_id INT NOT NULL,
    candidate_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (voter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (voter_id, position_id)
);

-- Election settings
CREATE TABLE election_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_name VARCHAR(100) NOT NULL,
    start_date DATETIME,
    end_date DATETIME,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: Admin@123)
INSERT INTO users (student_id, full_name, email, password, department, year, is_admin, is_verified) 
VALUES ('ADMIN001', 'System Administrator', 'admin@voting.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administration', 5, 1, 1);

-- Insert sample positions
INSERT INTO positions (title, description, max_candidates) VALUES
('President', 'Overall head of student union', 3),
('Vice President', 'Assists the president', 3),
('General Secretary', 'Handles documentation', 4),
('Treasurer', 'Manages finances', 3),
('Sports Secretary', 'Sports activities coordinator', 4),
('Cultural Secretary', 'Cultural events coordinator', 4);

-- Insert sample candidates
INSERT INTO candidates (position_id, student_id, full_name, department, year, manifesto) VALUES
(1, 'S001', 'John Smith', 'Computer Science', 3, 'I will work for better facilities and transparency.'),
(1, 'S002', 'Emma Johnson', 'Electrical Engineering', 4, 'Focus on student welfare and career guidance.'),
(2, 'S003', 'Michael Brown', 'Mechanical Engineering', 3, 'Will bridge gap between students and administration.'),
(2, 'S004', 'Sarah Davis', 'Civil Engineering', 2, 'Advocate for more extracurricular activities.');

-- Insert election settings
INSERT INTO election_settings (election_name, start_date, end_date, is_active) VALUES
('Student Union Elections 2024', '2024-03-01 09:00:00', '2024-03-10 17:00:00', 1);

-- Create indexes for performance
CREATE INDEX idx_votes_voter ON votes(voter_id);
CREATE INDEX idx_votes_position ON votes(position_id);
CREATE INDEX idx_candidates_position ON candidates(position_id);
CREATE INDEX idx_users_voted ON users(has_voted);

-- Create view for election results
CREATE VIEW election_results AS
SELECT 
    p.title as position,
    c.full_name as candidate,
    c.department,
    c.votes,
    ROUND((c.votes * 100.0 / NULLIF(SUM(c.votes) OVER(PARTITION BY p.id), 0)), 2) as percentage
FROM candidates c
JOIN positions p ON c.position_id = p.id
WHERE c.is_active = 1
ORDER BY p.id, c.votes DESC;