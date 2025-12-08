CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Optional: Migrate existing addresses from users table if useful, though it was just a single column
-- INSERT INTO addresses (user_id, full_address) SELECT id, address FROM users WHERE address IS NOT NULL AND address != '';
