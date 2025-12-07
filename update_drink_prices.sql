-- Define o preço adicional de R$ 5,00 para refrigerantes (Coca, Fanta, etc) nos combos
-- Mantém o Kuat como R$ 0,00 (padrão do combo)
UPDATE flavors 
SET additional_price = 5.00 
WHERE type = 'refrigerante' 
AND name NOT LIKE '%Kuat%';
