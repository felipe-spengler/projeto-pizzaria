-- Database Schema for Pizzaria System

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

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
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_customizable` boolean DEFAULT FALSE, -- If true, user picks flavors
  `max_flavors` int DEFAULT 1,
  `active` boolean DEFAULT TRUE,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `flavors` (for customizable pizzas)
--

CREATE TABLE `flavors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `additional_price` decimal(10,2) DEFAULT 0.00,
  `is_available` boolean DEFAULT TRUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','preparing','delivery','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `flavors_desc` text, -- JSON or comma separated list of flavor IDs/Names if customizable
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `order_item_flavors` (Junction table for precise flavor tracking)
--

CREATE TABLE `order_item_flavors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_item_id` int(11) NOT NULL,
  `flavor_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`),
  FOREIGN KEY (`flavor_id`) REFERENCES `flavors`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Data
INSERT INTO `categories` (`name`, `slug`, `icon`) VALUES ('Pizzas', 'pizzas', 'pizza-slice'), ('Bebidas', 'bebidas', 'wine-bottle');

INSERT INTO `flavors` (`name`, `description`, `additional_price`) VALUES 
('Calabresa', 'Calabresa fatiada, cebola e orégano', 0.00),
('Mussarela', 'Mussarela, tomate e orégano', 0.00),
('Portuguesa', 'Presunto, mussarela, ovo, cebola e ervilha', 2.00),
('Quatro Queijos', 'Mussarela, provolone, parmesão e gorgonzola', 5.00);

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `is_customizable`, `max_flavors`, `image_url`) VALUES
(1, 'Pizza Grande', 'Pizza de 8 fatias, escolha até 2 sabores', 49.90, 1, 2, 'assets/img/pizza-grande.jpg'),
(1, 'Pizza Gigante', 'Pizza de 12 fatias, escolha até 3 sabores', 69.90, 1, 3, 'assets/img/pizza-gigante.jpg'),
(2, 'Coca-Cola 2L', 'Refrigerante garrafa 2 litros', 12.00, 0, 0, 'assets/img/coca-2l.jpg');

COMMIT;
