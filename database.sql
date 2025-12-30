-- Database Schema for Casa Nova Pizzaria

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+03:00";

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `google_id` varchar(255) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `flavors`
--

CREATE TABLE `flavors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('salgado','doce','calzone','refrigerante','cerveja') DEFAULT 'salgado',
  `description` text,
  `additional_price` decimal(10,2) DEFAULT 0.00,
  `is_available` boolean DEFAULT TRUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_customizable` boolean DEFAULT FALSE, 
  `allowed_flavor_types` varchar(255) DEFAULT 'salgado', -- comma separated types: salgado,doce
  `max_flavors` int DEFAULT 1,
  `active` boolean DEFAULT TRUE,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Addresses
CREATE TABLE `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `street` varchar(255) NOT NULL,
  `number` varchar(20) NOT NULL,
  `neighborhood` varchar(100) NOT NULL,
  `complement` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT 'Chopinzinho',
  `state` char(2) DEFAULT 'PR',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Orders
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'money',
  `delivery_method` enum('delivery','pickup') DEFAULT 'delivery',
  `delivery_address` text,
  `notes` text,
  `change_for` decimal(10,2) DEFAULT NULL,
  `viewed` boolean DEFAULT FALSE,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ... existing tables ...




--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `order_item_flavors`
--

CREATE TABLE `order_item_flavors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_item_id` int(11) NOT NULL,
  `flavor_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`),
  FOREIGN KEY (`flavor_id`) REFERENCES `flavors`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Population

-- 0. Admin User
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Administrador', 'admin', 'admin123', 'admin');

-- 1. Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES 
(1, 'Pizzas', 'pizzas', 'pizza-slice'),
(2, 'Calzones', 'calzones', 'bread-slice'),
(3, 'Combos', 'combos', 'box-open'),
(4, 'Bebidas', 'bebidas', 'wine-bottle');

-- 2. Flavors

-- Tradicionais Salgados
INSERT INTO `flavors` (`name`, `description`, `type`, `additional_price`) VALUES
('Catufilé', 'Mussarela, filé, catupiry, orégano.', 'salgado', 0.00),
('Cinco Queijos', 'Mussarela, provolone, parmesão, catupiry, cheddar, orégano.', 'salgado', 0.00),
('Brutos', 'Mussarela, doritos, calabresa, orégano.', 'salgado', 0.00),
('Tacos', 'Mussarela, carne moída, cheddar, molho pimenta, doritos, orégano.', 'salgado', 0.00),
('Filé alho e óleo', 'Mussarela, filé, alho, azeite de oliva, orégano.', 'salgado', 0.00),
('Filé com azeitona', 'Mussarela, provolone, catupiry, parmesão, filé, orégano.', 'salgado', 0.00),
('Frango Especial', 'Mussarela, frango, cream cheese, orégano.', 'salgado', 0.00),
('Medusa', 'Mussarela, lombo, bacon, calabresa, catupiry, orégano.', 'salgado', 0.00),
('Quatro queijos bacon', 'Mussarela, provolone, catupiry, parmesão, bacon, orégano.', 'salgado', 0.00),
('Frango cremoso', 'Mussarela, frango em cubos, cream cheese, orégano.', 'salgado', 0.00),
('Sertanejo', 'Mussarela, lombo, bacon, alho, azeitona, tomate, orégano.', 'salgado', 0.00),
('Sinatra', 'Mussarela, peperoni, azeitona, cebola, orégano.', 'salgado', 0.00),
('Tomate Seco', 'Mussarela, tomate seco, rúcula, orégano.', 'salgado', 0.00),
('Vegetariana', 'Mussarela, brócolis, milho, palmito, rúcula, tomate, orégano.', 'salgado', 0.00),
('Alho e óleo', 'Mussarela, alho, azeite de oliva, orégano.', 'salgado', 0.00),
('Atum', 'Mussarela, atum, orégano.', 'salgado', 0.00),
('Americana', 'Mussarela, escarola, calabresa, tomate, orégano.', 'salgado', 0.00),
('A Toledo', 'Mussarela, palmito, catupiry, lombo, azeitona, orégano.', 'salgado', 0.00),
('Bacon', 'Mussarela, bacon, orégano.', 'salgado', 0.00),
('Baconbreeza', 'Mussarela, calabresa, bacon, orégano.', 'salgado', 0.00),
('Baconcheddar', 'Mussarela, bacon, cheddar, orégano.', 'salgado', 0.00),
('Bolonhesa', 'Mussarela, carne moída, parmesão, tomate, orégano.', 'salgado', 0.00),
('Baiana', 'Mussarela, calabresa moída, tomate, pimentão, pimenta vermelha, orégano.', 'salgado', 0.00),
('Brasileira', 'Mussarela, presunto, milho, tomate, orégano.', 'salgado', 0.00),
('Calabresa', 'Mussarela, calabresa, orégano.', 'salgado', 0.00),
('Calacongo', 'Mussarela, calabresa, frango, catupiry, orégano.', 'salgado', 0.00),
('Canadense', 'Mussarela, lombo, catupiry, orégano.', 'salgado', 0.00),
('Catarina', 'Mussarela, calabresa, ovo, alho, tomate, orégano.', 'salgado', 0.00),
('Caipira', 'Mussarela, milho, frango, bacon, tomate, orégano.', 'salgado', 0.00),
('Croacante', 'Mussarela, bacon, batata palha, orégano.', 'salgado', 0.00),
('Chilena', 'Mussarela, atum, cebola, orégano.', 'salgado', 0.00),
('Bacon com Milho', 'Mussarela, bacon, milho, orégano.', 'salgado', 0.00),
('Escarola', 'Mussarela, escarola, bacon, orégano.', 'salgado', 0.00),
('Espanhola', 'Mussarela, calabresa, alho, tomate, orégano.', 'salgado', 0.00),
('Frango', 'Mussarela, frango desfiado, orégano.', 'salgado', 0.00),
('Marguerita', 'Mussarela, parmesão, tomate cereja, manjericão, orégano.', 'salgado', 0.00),
('Mineira', 'Mussarela, palmito, milho, parmesão, tomate, orégano.', 'salgado', 0.00),
('Napolitana', 'Mussarela, tomate, parmesão, orégano.', 'salgado', 0.00),
('Palmito', 'Mussarela, palmito, orégano.', 'salgado', 0.00),
('Peperoni', 'Mussarela, peperoni, orégano.', 'salgado', 0.00),
('Portuguesa', 'Mussarela, presunto, ovos, cebola, azeitona, orégano.', 'salgado', 0.00),
('Frango Catupiry', 'Mussarela, frango, catupiry, orégano.', 'salgado', 0.00),
('Havaiana', 'Mussarela, bacon, abacaxi.', 'salgado', 0.00),
('Italiana', 'Mussarela, salame italiano, parmesão, cebola, azeitona, orégano.', 'salgado', 0.00),
('Milho', 'Mussarela, milho, orégano.', 'salgado', 0.00),
('Musa', 'Mussarela, orégano.', 'salgado', 0.00),
('Portuguesa Apimentada', 'Mussarela, presunto, ovos, cebola, azeitona, molho de pimenta, orégano.', 'salgado', 0.00),
('Quatro queijos', 'Mussarela, provolone, catupiry, parmesão, orégano.', 'salgado', 0.00),
('Salame Italiano', 'Mussarela, salame italiano, cebola, orégano.', 'salgado', 0.00),
('Suprema', 'Mussarela, calabresa, palmito, parmesão, tomate, orégano.', 'salgado', 0.00),
('Strogonoff bovino', 'Mussarela, strogonoff bovino, batata palha, orégano.', 'salgado', 0.00),
('Strogonoff frango', 'Mussarela, strogonoff frango, batata palha, orégano.', 'salgado', 0.00),
('Toledana', 'Mussarela, presunto, bacon, catupiry, orégano.', 'salgado', 0.00);

-- Especiais
INSERT INTO `flavors` (`name`, `description`, `type`, `additional_price`) VALUES
('Brócolis', 'Mussarela, brócolis, bacon, orégano.', 'salgado', 0.00),
('Californiana', 'Mussarela, peperoni, catupiry, orégano.', 'salgado', 0.00),
('Do Chef', 'Mussarela, carne moída, azeitona, cebola, orégano.', 'salgado', 0.00),
('Da Casa', 'Mussarela, bacon, ovos, parmesão, tomate, orégano.', 'salgado', 0.00),
('Elvis', 'Mussarela, bacon, ovo, milho, tomate, orégano.', 'salgado', 0.00),
('Formosa', 'Mussarela, calabresa, ovos, bacon, tomate, orégano.', 'salgado', 0.00),
('Frango Brócolis', 'Mussarela, frango, brócolis, tomate, orégano.', 'salgado', 0.00),
('Frango Mexicano', 'Mussarela, frango, peperone, tomate, orégano.', 'salgado', 0.00),
('Frango Baiano', 'Mussarela, frango, molho de baiana da casa, orégano.', 'salgado', 0.00),
('Maicon', 'Mussarela, bacon, peperini, orégano.', 'salgado', 0.00);

-- Gourmet (Add 5.00)
INSERT INTO `flavors` (`name`, `description`, `type`, `additional_price`) VALUES
('Alcatra na mostarda', 'Mussarela, alcatra na mostarda, orégano.', 'salgado', 5.00),
('Alcatra ao alho', 'Mussarela, alho, alcatra, orégano.', 'salgado', 5.00),
('Coração de Frango', 'Mussarela, coração de frango, orégano.', 'salgado', 5.00),
('Peito de Peru', 'Mussarela, peito de peru, cream cheese, orégano.', 'salgado', 5.00),
('Tilápia', 'Mussarela, tilápia, milho, catupiry, orégano.', 'salgado', 5.00),
('Costela bovina', 'Mussarela, costela desfiada, cebola, azeitona, tomate, orégano.', 'salgado', 5.00),
('Costela catupiry', 'Mussarela, costela desfiada, catupiry, orégano.', 'salgado', 5.00);

-- Doces
INSERT INTO `flavors` (`name`, `description`, `type`, `additional_price`) VALUES
('Abacaxi com leite ninho', 'Abacaxi, leite ninho, leite condensado.', 'doce', 0.00),
('Sensação de valsa', 'Mussarela, chocolate preto, sonho de valsa, leite condensado.', 'doce', 0.00),
('Suspiro', 'Mussarela, chocolate preto, morango, suspiro.', 'doce', 0.00),
('Ovomaltine', 'Mussarela, chocolate preto, ovomaltine, leite condensado.', 'doce', 0.00),
('Pina colada', 'Mussarela, abacaxi, coco, chocolate branco.', 'doce', 0.00),
('Abacaxi', 'Mussarela, abacaxi, leite condensado, açúcar, canela.', 'doce', 0.00),
('Abacaxi nevado', 'Mussarela, abacaxi, chocolate branco, leite condensado.', 'doce', 0.00),
('Banana', 'Mussarela, leite condensado, banana, açúcar, canela.', 'doce', 0.00),
('Beijinho', 'Mussarela, chocolate branco, coco ralado, leite condensado.', 'doce', 0.00),
('Brigadeiro', 'Mussarela, chocolate preto, granulado, leite condensado.', 'doce', 0.00),
('Bis', 'Mussarela, chocolate preto, bis, leite condensado.', 'doce', 0.00),
('Banana Nevada', 'Mussarela, banana, chocolate branco, leite condensado.', 'doce', 0.00),
('Chocobanana', 'Mussarela, banana, chocolate preto, leite condensado.', 'doce', 0.00),
('Chokito', 'Mussarela, chocolate preto, flokos arroz, leite condensado.', 'doce', 0.00),
('Chocolate Branco', 'Mussarela, chocolate branco, leite condensado.', 'doce', 0.00),
('Chocolate Preto', 'Mussarela, chocolate preto, leite condensado.', 'doce', 0.00),
('Cereja', 'Mussarela, chocolate preto, amendoim, leite condensado.', 'doce', 0.00),
('Prestígio', 'Mussarela, chocolate preto, coco ralado, leite condensado.', 'doce', 0.00),
('Dois Amores', 'Mussarela, chocolate branco e preto, leite condensado.', 'doce', 0.00),
('Kit Kat', 'Mussarela, chocolate branco, kit kat, leite condensado.', 'doce', 0.00),
('Limão', 'Mussarela, chocolate branco, raspas limão, leite condensado.', 'doce', 0.00),
('Mms', 'Mussarela, chocolate preto, mms, leite condensado.', 'doce', 0.00),
('Ninho', 'Mussarela, chocolate preto, leite ninho, leite condensado.', 'doce', 0.00),
('Negresco', 'Mussarela, chocolate branco, negresco, creme leite.', 'doce', 0.00),
('Paçoca', 'Mussarela, chocolate preto, paçoca rolas, leite condensado.', 'doce', 0.00),
('Sensação (Preto)', 'Mussarela, chocolate preto, morango, leite condensado.', 'doce', 0.00),
('Sedutora (Branco)', 'Mussarela, chocolate branco, morango, leite condensado.', 'doce', 0.00);

-- Gourmet Doce
INSERT INTO `flavors` (`name`, `description`, `type`, `additional_price`) VALUES
('Banoff', 'Mussarela, doce de leite, banana, chocolate branco, canela.', 'doce', 5.00),
('Fondue', 'Mussarela, chocolate preto, morango, banana, abacaxi, chocolate forneavel.', 'doce', 5.00),
('Floresta Negra', 'Mussarela, chocolate preto, bis, cereja, leite condensado.', 'doce', 5.00),
('Nutella', 'Mussarela, nutela, leite condensado.', 'doce', 5.00);

-- Calzone Flavors
INSERT INTO `flavors` (`name`, `description`, `type`, `additional_price`) VALUES
('Calzone Portuguesa', 'Mussarela, ovos, presunto, cebola, azeitona, orégano.', 'calzone', 0.00),
('Calzone Catufrango', 'Mussarela, calabresa, frango, catupiry, orégano.', 'calzone', 0.00),
('Calzone Moda da casa', 'Mussarela, carne moída, ovos, cebola, catupiry, orégano.', 'calzone', 0.00),
('Calzone Abacaxi nevado', 'Mussarela, abacaxi, chocolate branco, leite condensado.', 'calzone', 0.00),
('Calzone Chocobanana', 'Mussarela, banana, chocolate branco ou preto, leite condensado.', 'calzone', 0.00),
('Calzone Dois amores', 'Mussarela, chocolate branco e preto, leite condensado.', 'calzone', 0.00),
('Calzone Sensação (Preto)', 'Mussarela, chocolate preto, morango, leite condensado.', 'calzone', 0.00),
('Calzone Sensação (Branco)', 'Mussarela, chocolate branco, morango, leite condensado.', 'calzone', 0.00);

-- Bebidas Flavors (NOVOS)
INSERT INTO `flavors` (`name`, `description`, `type`, `additional_price`) VALUES
('Coca-Cola Original', '', 'refrigerante', 5.00),
('Coca-Cola Zero', '', 'refrigerante', 5.00),
('Fanta Laranja', '', 'refrigerante', 5.00),
('Fanta Uva', '', 'refrigerante', 5.00),
('Kuat', '', 'refrigerante', 0.00),
('Sprite', '', 'refrigerante', 5.00),
('Guaraná Antarctica', '', 'refrigerante', 5.00),
('Skol', '', 'cerveja', 0.00),
('Itaipava', '', 'cerveja', 0.00),
('Brahma', '', 'cerveja', 0.00),
('Budweiser', '', 'cerveja', 0.00),
('Heineken', '', 'cerveja', 0.00);

-- 3. Products
-- Pizzas
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `is_customizable`, `max_flavors`, `allowed_flavor_types`, `image_url`) VALUES
(1, 'Pizza Broto (25cm)', '6 Pedaços. Escolha 1 sabor.', 40.00, 1, 1, 'salgado,doce', 'https://images.unsplash.com/photo-1588315029754-2dd089d39a1a?auto=format&fit=crop&w=800&q=80'),
(1, 'Pizza Pequena (P - 30cm)', '8 Pedaços. Escolha até 2 sabores.', 49.00, 1, 2, 'salgado,doce', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=800&q=80'),
(1, 'Pizza Média (M - 35cm)', '12 Pedaços. Escolha até 3 sabores.', 60.00, 1, 3, 'salgado,doce', 'https://images.unsplash.com/photo-1590947132387-155cc02f3212?auto=format&fit=crop&w=800&q=80'),
(1, 'Pizza Grande (G - 40cm)', '16 Pedaços. Escolha até 4 sabores.', 70.00, 1, 4, 'salgado,doce', 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?auto=format&fit=crop&w=800&q=80'),
(1, 'Pizza Gigante (GG - 45cm)', '20 Pedaços. Escolha até 4 sabores.', 90.00, 1, 4, 'salgado,doce', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80');

-- Calzones
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `is_customizable`, `max_flavors`, `allowed_flavor_types`, `image_url`) VALUES
(2, 'Calzone (30cm)', 'Escolha 1 sabor do cardápio de calzones.', 55.00, 1, 1, 'calzone', 'assets/images/calzone-real.png');

-- Combos
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `is_customizable`, `allowed_flavor_types`, `image_url`) VALUES
(3, 'COMBO P', 'Pizza P + Broto Doce + Kuat 2L.', 76.00, 1, 'salgado,doce,refrigerante', 'https://images.unsplash.com/photo-1585238342024-78d387f4a707?auto=format&fit=crop&w=800&q=80'),
(3, 'COMBO G', 'Pizza G + Broto Doce + Kuat 2L.', 95.00, 1, 'salgado,doce,refrigerante', 'https://images.unsplash.com/photo-1585238342024-78d387f4a707?auto=format&fit=crop&w=800&q=80'),
(3, 'COMBO GG', 'Pizza GG + Broto Doce + Kuat 2L.', 113.00, 1, 'salgado,doce,refrigerante', 'https://images.unsplash.com/photo-1585238342024-78d387f4a707?auto=format&fit=crop&w=800&q=80'),
(3, 'COMBO 2 PIZZA G', 'Duas Pizzas G + Kuat 2L.', 135.00, 1, 'salgado,doce,refrigerante', 'assets/images/combo-real.jpg');

-- Bebidas (REORGANIZADAS)
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `is_customizable`, `max_flavors`, `allowed_flavor_types`, `image_url`) VALUES
(4, 'Refrigerante 2L', 'Escolha o sabor.', 15.00, 1, 1, 'refrigerante', 'assets/images/coca-cola-2l.png'),
(4, 'Refrigerante 1L', 'Escolha o sabor.', 10.00, 1, 1, 'refrigerante', 'assets/images/coca-cola-2l.png'),
(4, 'Refrigerante 600ml', 'Escolha o sabor.', 8.00, 1, 1, 'refrigerante', 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80'),
(4, 'Refrigerante Lata', '350ml. Escolha o sabor.', 6.00, 1, 1, 'refrigerante', 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80'),
(4, 'Cerveja Lata', '350ml. Escolha a marca. (+18 anos)', 6.00, 1, 1, 'cerveja', 'https://images.unsplash.com/photo-1659714850889-7603c9d18721?auto=format&fit=crop&w=800&q=80'),
(4, 'Cerveja Long Neck', '330ml. Escolha a marca. (+18 anos)', 9.50, 1, 1, 'cerveja', 'https://images.unsplash.com/photo-1663431326402-af4eb05a6977?auto=format&fit=crop&w=800&q=80');

COMMIT;

-- --------------------------------------------------------
-- Table structure for table access_logs
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS access_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  city VARCHAR(100) DEFAULT NULL,
  region VARCHAR(100) DEFAULT NULL,
  country VARCHAR(100) DEFAULT NULL,
  device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
  os VARCHAR(50) DEFAULT NULL,
  browser VARCHAR(50) DEFAULT NULL,
  page_url VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at),
  INDEX (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
