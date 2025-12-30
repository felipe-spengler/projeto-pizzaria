<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class OrderController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // LIST (GET) - Admin: All orders, User: My orders
    public function index($filters = [])
    {
        $sql = "SELECT o.*, u.name as customer_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id";

        $where = [];
        $params = [];

        if (isset($filters['user_id'])) {
            $where[] = "o.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (isset($filters['status'])) {
            $where[] = "o.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['date_start']) && isset($filters['date_end'])) {
            $where[] = "DATE(o.created_at) BETWEEN ? AND ?";
            $params[] = $filters['date_start'];
            $params[] = $filters['date_end'];
        }

        // Just today (quick filter)
        if (isset($filters['today'])) {
            $where[] = "DATE(o.created_at) = CURDATE()";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY o.created_at DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int) $filters['limit'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // SHOW (GET via ID)
    public function show($id)
    {
        // Fetch Order
        $stmt = $this->db->prepare("SELECT o.*, u.name as customer_name, u.phone, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order)
            return null;

        // Fetch Items
        $stmtItems = $this->db->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmtItems->execute([$id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Flavors for each item
        foreach ($items as &$item) {
            $stmtFlavors = $this->db->prepare("SELECT f.name FROM order_item_flavors oif JOIN flavors f ON oif.flavor_id = f.id WHERE oif.order_item_id = ?");
            $stmtFlavors->execute([$item['id']]);
            $item['flavors'] = $stmtFlavors->fetchAll(PDO::FETCH_COLUMN);
        }

        $order['items'] = $items;
        return $order;
    }

    // STORE (POST) - Create Order
    public function store($data)
    {
        try {
            $this->db->beginTransaction();

            // Insert Order
            $stmt = $this->db->prepare("INSERT INTO orders (user_id, status, total_amount, delivery_address, notes, delivery_method, payment_method, change_for) VALUES (?, 'pending', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['user_id'],
                $data['total_amount'],
                $data['delivery_address'],
                $data['notes'] ?? '',
                $data['delivery_method'],
                $data['payment_method'],
                $data['change_for'] ?? null
            ]);
            $orderId = $this->db->lastInsertId();

            // Insert Items
            $stmtItem = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmtFlavor = $this->db->prepare("INSERT INTO order_item_flavors (order_item_id, flavor_id) VALUES (?, ?)");

            foreach ($data['items'] as $item) {
                $stmtItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal']
                ]);
                $itemId = $this->db->lastInsertId();

                if (!empty($item['flavors'])) {
                    foreach ($item['flavors'] as $flavorId) {
                        $stmtFlavor->execute([$itemId, $flavorId]);
                    }
                }
            }

            $this->db->commit();
            return $orderId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // UPDATE STATUS (PUT/PATCH) - Admin Action
    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE orders SET status = ?, viewed = TRUE WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    // MARK VIEWED
    public function markAsViewed($id)
    {
        $stmt = $this->db->prepare("UPDATE orders SET viewed = TRUE WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // CHECK NEW ORDERS (Polling)
    public function checkNewOrders($lastId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as new_count, MAX(id) as max_id FROM orders WHERE id > ?");
        $stmt->execute([$lastId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // GET STATISTICS
    public function getStats($date = null)
    {
        $date = $date ?: date('Y-m-d');

        $revenue = $this->db->query("SELECT SUM(total_amount) FROM orders WHERE DATE(created_at) = '$date' AND status != 'cancelled'")->fetchColumn() ?: 0;
        $count = $this->db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = '$date'")->fetchColumn() ?: 0;
        $pending = $this->db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0;

        return [
            'revenue' => $revenue,
            'orders_count' => $count,
            'pending_orders' => $pending
        ];
    }

    // REPORT: Best Sellers
    public function getBestSellers($startDate, $endDate)
    {
        $sql = "SELECT p.name, 
                       SUM(oi.quantity) as total_qty, 
                       SUM(oi.subtotal) as total_rev 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN orders o ON oi.order_id = o.id
                WHERE DATE(o.created_at) BETWEEN ? AND ? 
                AND o.status != 'cancelled'
                GROUP BY p.id, p.name 
                ORDER BY total_qty DESC 
                LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // REPORT: Total Revenue Period
    public function getRevenueByPeriod($startDate, $endDate)
    {
        $sql = "SELECT SUM(total_amount) 
                FROM orders 
                WHERE DATE(created_at) BETWEEN ? AND ? 
                AND status != 'cancelled'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchColumn() ?: 0;
    }
}
