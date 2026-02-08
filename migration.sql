-- Migration for new features

-- Add karma and coordinates to users
ALTER TABLE users ADD COLUMN karma_score INT DEFAULT 0;
ALTER TABLE users ADD COLUMN latitude DECIMAL(10,7);
ALTER TABLE users ADD COLUMN longitude DECIMAL(10,7);
ALTER TABLE users ADD COLUMN phone VARCHAR(30);
ALTER TABLE users ADD COLUMN whatsapp VARCHAR(30);
ALTER TABLE users ADD COLUMN bio VARCHAR(500);
ALTER TABLE users ADD COLUMN website VARCHAR(255);

-- Table for need images
CREATE TABLE need_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    need_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_main BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (need_id) REFERENCES needs(id) ON DELETE CASCADE
);

-- Allow listing owner to enable/disable public comments
ALTER TABLE needs ADD COLUMN allow_comments BOOLEAN DEFAULT 1;

-- Table for ratings
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rater_id INT NOT NULL,
    rated_id INT NOT NULL,
    score TINYINT NOT NULL CHECK (score BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rater_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rated_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table for reports
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT,
    reported_need_id INT,
    reason TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending', -- pending, reviewed, resolved
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_need_id) REFERENCES needs(id) ON DELETE CASCADE
);
