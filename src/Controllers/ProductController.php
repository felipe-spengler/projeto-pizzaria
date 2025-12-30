<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class ProductController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Helper for image overrides
    private function applyImageOverrides(&$product)
    {
        $pNameNorm = mb_strtoupper($product['name'] ?? '', 'UTF-8');
        if (isset($product['category_id']) && $product['category_id'] == 2) {
            $product['image_url'] = 'assets/images/calzone-real.png';
        } elseif ($pNameNorm === 'COMBO 2 PIZZA G') {
            $product['image_url'] = 'assets/images/combo-real.jpg';
        } elseif ($pNameNorm === 'REFRIGERANTE 2L' || $pNameNorm === 'REFRIGERANTE 1L') {
            $product['image_url'] = 'assets/images/coca-cola-2l.png';
        } elseif ($pNameNorm === 'REFRIGERANTE LATA') {
            $product['image_url'] = 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80';
        } elseif (str_contains($pNameNorm, 'PIZZA PEQUENA')) {
            $product['image_url'] = 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=800&q=80';
        } elseif (str_contains($pNameNorm, 'PIZZA MÉDIA')) {
            $product['image_url'] = 'https://images.unsplash.com/photo-1590947132387-155cc02f3212?auto=format&fit=crop&w=800&q=80';
        } elseif (str_contains($pNameNorm, 'PIZZA GRANDE')) {
            $product['image_url'] = 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?auto=format&fit=crop&w=800&q=80';
        } elseif (str_contains($pNameNorm, 'PIZZA GIGANTE')) {
            $product['image_url'] = 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80';
        } elseif (str_contains($pNameNorm, 'PIZZA BROTO')) {
            $product['image_url'] = 'https://images.unsplash.com/photo-1588315029754-2dd089d39a1a?auto=format&fit=crop&w=800&q=80';
        }
    }

    // LIST (GET)
    public function index()
    {
        $sql = "SELECT p.*, c.name as category_name, c.icon as category_icon 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                ORDER BY p.category_id, p.name";
        $products = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as &$p) {
            $this->applyImageOverrides($p);
        }
        return $products;
    }

    // SHOW (GET via ID)
    public function show($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $this->applyImageOverrides($product);
        }
        return $product;
    }

    // STORE (POST)
    public function store($data)
    {
        // Validation could go here
        $sql = "INSERT INTO products (name, description, price, image_url, category_id, is_customizable, max_flavors, allowed_flavor_types, active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['image_url'] ?? '',
            $data['category_id'],
            isset($data['is_customizable']) ? 1 : 0,
            $data['max_flavors'] ?? 1,
            $data['allowed_flavor_types'] ?? ''
        ]);
        return $this->db->lastInsertId();
    }

    // UPDATE (PUT/POST)
    public function update($id, $data)
    {
        $sql = "UPDATE products SET name=?, description=?, price=?, image_url=?, category_id=?, is_customizable=?, max_flavors=?, allowed_flavor_types=?, active=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['image_url'],
            $data['category_id'],
            isset($data['is_customizable']) ? 1 : 0,
            $data['max_flavors'],
            $data['allowed_flavor_types'],
            isset($data['active']) ? 1 : 0,
            $id
        ]);
    }

    // DELETE (DELETE)
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // TOGGLE ACTIVE (Custom Action)
    public function toggleActive($id)
    {
        $stmt = $this->db->prepare("UPDATE products SET active = NOT active WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
