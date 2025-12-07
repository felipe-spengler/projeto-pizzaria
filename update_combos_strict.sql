-- Make Combos Customizable
UPDATE products SET is_customizable = 1, allowed_flavor_types = 'salgado,doce,refrigerante' WHERE name LIKE 'COMBO%';
