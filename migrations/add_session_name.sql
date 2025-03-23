-- Add name column to sessions table
ALTER TABLE sessions ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT 'Unnamed Session' AFTER access_code;

-- Update existing sessions with default names
UPDATE sessions SET name = CONCAT('Session #', session_id) WHERE name = 'Unnamed Session'; 