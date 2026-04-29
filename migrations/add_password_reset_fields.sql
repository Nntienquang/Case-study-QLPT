-- Add password reset columns to users table
ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL;
ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL DEFAULT NULL;

-- Create indexes for faster queries
CREATE INDEX idx_reset_token ON users(reset_token);
CREATE INDEX idx_email ON users(email);
