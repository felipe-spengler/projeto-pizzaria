<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class CustomerController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index($filters = [])
    {
        $sql = "SELECT u.*, 
                       COUNT(o.id) as orders_count, 
                       SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END) as total_spent,
                       MAX(o.created_at) as last_order_date
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id
                WHERE u.role = 'customer'"; // Role filter

        if (isset($filters['inactive_days'])) {
            // This requires having clause or logic after fetch, 
            // but for SQL efficiency we can use HAVING if filtering by aggregate
            // However, date diff in SQL is cleaner
            // Let's filter in PHP for simplicity or complex SQL if needed.
            // Complex SQL approaches are better for pagination but here we fetch all for now?
            // Let's fetch all and filter in PHP or improve query if dataset is large.
        }

        $sql .= " GROUP BY u.id ORDER BY total_spent DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Filter Inactive Logic in PHP for now (easier to read/maintain for this scale)
        if (isset($filters['inactive_days'])) {
            $days = (int) $filters['inactive_days'];
            $customers = array_filter($customers, function ($c) use ($days) {
                // Determine days since last order
                if (!$c['last_order_date'])
                    return true; // Never ordered = inactive? Or ignore? Usually inactive.
                $diff = time() - strtotime($c['last_order_date']);
                $daysSince = floor($diff / (60 * 60 * 24));
                return $daysSince > $days;
            });
        }

        return $customers;
    }

    public function show($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create/Update/Delete customers could proceed here
}
