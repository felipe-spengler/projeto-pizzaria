ALTER TABLE orders ADD COLUMN delivery_method ENUM('delivery', 'pickup') DEFAULT 'delivery';
ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'money';
