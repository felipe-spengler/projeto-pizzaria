<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class FlavorController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index()
    {
        $sql = "SELECT * FROM flavors ORDER BY type, name";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function show($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM flavors WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function store($data)
    {
        $sql = "INSERT INTO flavors (name, description, type, additional_price, is_available) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['type'],
            $data['additional_price'],
            isset($data['is_available']) ? 1 : 0
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $sql = "UPDATE flavors SET name=?, description=?, type=?, additional_price=?, is_available=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['type'],
            $data['additional_price'],
            isset($data['is_available']) ? 1 : 0,
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM flavors WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function toggleAvailability($id)
    {
        $stmt = $this->db->prepare("UPDATE flavors SET is_available = NOT is_available WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getByType($type)
    {
        $stmt = $this->db->prepare("SELECT * FROM flavors WHERE type = ? AND is_available = 1 ORDER BY name");
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
