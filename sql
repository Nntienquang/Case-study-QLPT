ALTER TABLE users
ADD owner_verification_status VARCHAR(50) DEFAULT 'pending_verification';
