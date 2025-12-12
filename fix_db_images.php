<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "Updating Image URLs...\n";

// 1. Swap Combo/Calzone (using new filenames)
$db->exec("UPDATE products SET image_url = 'assets/images/calzone-real.png' WHERE category_id = 2"); // Calzones
$db->exec("UPDATE products SET image_url = 'assets/images/combo-real.jpg' WHERE name = 'COMBO 2 PIZZA G'");

// 2. Rotate Pizzas
// Old G (Img D) -> P
$imgD = 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=800&q=80';
$db->prepare("UPDATE products SET image_url = ? WHERE name LIKE 'Pizza Pequena%'")->execute([$imgD]);

// Old M (Img C) -> GG
$imgC = 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80';
$db->prepare("UPDATE products SET image_url = ? WHERE name LIKE 'Pizza Gigante%'")->execute([$imgC]);

// Old Broto (Img A) -> G
$imgA = 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?auto=format&fit=crop&w=800&q=80';
$db->prepare("UPDATE products SET image_url = ? WHERE name LIKE 'Pizza Grande%'")->execute([$imgA]);

// Old P (Img B) -> M
$imgB = 'https://images.unsplash.com/photo-1590947132387-155cc02f3212?auto=format&fit=crop&w=800&q=80';
$db->prepare("UPDATE products SET image_url = ? WHERE name LIKE 'Pizza Média%'")->execute([$imgB]);

// Old GG (Img E) -> Broto (Assumption to complete cycle)
$imgE = 'https://images.unsplash.com/photo-1588315029754-2dd089d39a1a?auto=format&fit=crop&w=800&q=80';
$db->prepare("UPDATE products SET image_url = ? WHERE name LIKE 'Pizza Broto%'")->execute([$imgE]);

echo "Done.";
?>