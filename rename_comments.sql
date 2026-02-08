-- Change messages to comments
RENAME TABLE messages TO comments;
ALTER TABLE comments CHANGE message comment TEXT NOT NULL;
ALTER TABLE comments DROP FOREIGN KEY comments_ibfk_2; -- Dropping receiver constraint
ALTER TABLE comments DROP COLUMN receiver_id; -- Comments are public, no specific receiver
