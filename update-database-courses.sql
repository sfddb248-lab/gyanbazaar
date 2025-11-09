-- Course Video Playlist System
-- Run this SQL to add course functionality

-- Course Sections/Modules Table
CREATE TABLE IF NOT EXISTS course_sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_section_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Course Videos Table
CREATE TABLE IF NOT EXISTS course_videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    product_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_path VARCHAR(500) NOT NULL,
    video_duration VARCHAR(20),
    video_size BIGINT,
    notes_path VARCHAR(500),
    order_index INT DEFAULT 0,
    is_preview BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_video_section (section_id),
    INDEX idx_video_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Video Progress Table
CREATE TABLE IF NOT EXISTS user_video_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    product_id INT NOT NULL,
    watched_duration INT DEFAULT 0,
    total_duration INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES course_videos(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_video (user_id, video_id),
    INDEX idx_progress_user (user_id),
    INDEX idx_progress_video (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
