-- Add change_for column to orders table
ALTER TABLE orders ADD COLUMN change_for DECIMAL(10,2) DEFAULT NULL;
