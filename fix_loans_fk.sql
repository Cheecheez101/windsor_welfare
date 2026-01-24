-- Fix foreign key constraint for loans table
-- This script should be run if you have existing data

-- First, drop the existing foreign key constraint
ALTER TABLE loans DROP FOREIGN KEY loans_ibfk_1;

-- Then add the correct foreign key constraint
ALTER TABLE loans ADD CONSTRAINT loans_member_id_fk FOREIGN KEY (member_id) REFERENCES users(id);

-- Optional: If you want to clean up the members table (if it's not used)
-- DROP TABLE members;