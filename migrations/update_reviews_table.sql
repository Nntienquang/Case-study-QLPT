-- Update Reviews Table
-- Thêm columns để ẩn (soft delete) và cờ review thay vì xóa vĩnh viễn

ALTER TABLE reviews 
ADD COLUMN is_hidden TINYINT(1) DEFAULT 0 COMMENT 'Review bị ẩn bởi admin',
ADD COLUMN is_flagged TINYINT(1) DEFAULT 0 COMMENT 'Review bị đánh dấu vi phạm',
ADD COLUMN hidden_by INT COMMENT 'Admin ID ẩn review',
ADD COLUMN hidden_at DATETIME COMMENT 'Thời gian ẩn review',
ADD COLUMN hidden_reason VARCHAR(500) COMMENT 'Lý do ẩn',
ADD COLUMN flagged_by INT COMMENT 'Admin ID cờ review',
ADD COLUMN flagged_at DATETIME COMMENT 'Thời gian cờ review',
ADD COLUMN flag_reason VARCHAR(500) COMMENT 'Lý do cờ',
ADD INDEX idx_is_hidden (is_hidden),
ADD INDEX idx_is_flagged (is_flagged),
ADD INDEX idx_hidden_by (hidden_by),
ADD INDEX idx_flagged_by (flagged_by);
