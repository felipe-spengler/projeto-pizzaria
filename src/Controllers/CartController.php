<?php
namespace App\Controllers;

use App\Config\Database;
use App\Config\Session;

class CartController
{
    private $db;
    private $orderController;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->orderController = new OrderController();
        if (session_status() === PHP_SESSION_NONE) {
            Session::start();
        }
    }

    public function getCart()
    {
        return $_SESSION['cart'] ?? [];
    }

    public function addToCart($data)
    {
        $productId = $data['product_id'];
        $quantity = (int) $data['quantity'];
        $rawFlavors = $data['flavors'] ?? [];
        $flavors = [];

        // Flatten flavors
        foreach ($rawFlavors as $item) {
            if (is_array($item)) {
                foreach ($item as $subItem)
                    $flavors[] = $subItem;
            } else {
                $flavors[] = $item;
            }
        }

        // Fetch Product
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            $cartItem = [
                'product_id' => $product['id'],
                'name' => $product['name'],
                'price' => (float) $product['price'],
                'quantity' => $quantity,
                'flavors' => []
            ];

            // Calculate Flavors
            $totalFlavorPrice = 0;
            if (!empty($flavors)) {
                $in = str_repeat('?,', count($flavors) - 1) . '?';
                $stmt = $this->db->prepare("SELECT * FROM flavors WHERE id IN ($in)");
                $stmt->execute($flavors);
                $flavorData = $stmt->fetchAll();

                $isCombo = ($product['category_id'] == 3 || str_starts_with($product['name'], 'COMBO'));

                foreach ($flavorData as $f) {
                    $flavorPrice = ($isCombo || !in_array($f['type'], ['refrigerante', 'bebida', 'cerveja'])) ? (float) $f['additional_price'] : 0;

                    $cartItem['flavors'][] = [
                        'id' => $f['id'],
                        'name' => $f['name'],
                        'price' => $flavorPrice
                    ];
                    $totalFlavorPrice += $flavorPrice;
                }
            }

            $cartItem['unit_total'] = $cartItem['price'] + $totalFlavorPrice;
            $cartItem['total'] = $cartItem['unit_total'] * $quantity;

            $_SESSION['cart'][] = $cartItem;
        }
    }

    public function removeItem($index)
    {
        if (isset($_SESSION['cart'][$index])) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }
    }

    public function checkout($postData)
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            return ['success' => false, 'error' => 'Carrinho vazio.'];
        }

        // 1. Identify User
        $userId = null;
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        } else {
            // Guest Logic
            $guestName = trim($postData['guest_name'] ?? '');
            $guestPhone = trim($postData['guest_phone'] ?? '');

            if (empty($guestName))
                return ['success' => false, 'error' => 'Nome obrigatório.'];

            if (!isset($_SESSION['guest_user_id'])) {
                // Create Guest
                $generatedEmail = 'guest+' . session_id() . '-' . time() . '@pedido.local';
                $stmt = $this->db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, NULL, 'customer')");
                $stmt->execute([$guestName, $generatedEmail, $guestPhone]);
                $_SESSION['guest_user_id'] = $this->db->lastInsertId();
                $_SESSION['guest_user_name'] = $guestName;
            } else {
                // Update Guest
                $stmt = $this->db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $stmt->execute([$guestName, $guestPhone, $_SESSION['guest_user_id']]);
            }
            $userId = $_SESSION['guest_user_id'];
        }

        // 2. Address Logic
        $deliveryMethod = $postData['delivery_method'] ?? 'pickup';
        $address = "Retirada no Balcão";

        if ($deliveryMethod === 'delivery') {
            $addressOption = $postData['address_option'] ?? 'new';

            if ($addressOption === 'new') {
                $street = trim($postData['street'] ?? '');
                $number = trim($postData['number'] ?? '');
                $neighborhood = trim($postData['neighborhood'] ?? '');
                $complement = trim($postData['complement'] ?? '');

                if (empty($street) || empty($number))
                    return ['success' => false, 'error' => 'Endereço incompleto.'];

                $address = "$street, $number - $neighborhood";
                if ($complement)
                    $address .= " ($complement)";
                $address .= " - Toledo/PR";

                // Save Address (only for registered usually, but we save for guest session too if we want)
                try {
                    $stmtAddr = $this->db->prepare("INSERT INTO addresses (user_id, street, number, neighborhood, complement, city, state) VALUES (?, ?, ?, ?, ?, 'Toledo', 'PR')");
                    $stmtAddr->execute([$userId, $street, $number, $neighborhood, $complement]);
                } catch (\Exception $e) {
                }

            } else {
                // Fetch saved address
                $stmt = $this->db->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
                $stmt->execute([$addressOption, $userId]);
                $addrRow = $stmt->fetch();
                if ($addrRow) {
                    $address = "{$addrRow['street']}, {$addrRow['number']} - {$addrRow['neighborhood']}";
                    if ($addrRow['complement'])
                        $address .= " ({$addrRow['complement']})";
                    $address .= " - {$addrRow['city']}/{$addrRow['state']}";
                }
            }
        }

        // 3. Create Order via OrderController
        $orderData = [
            'user_id' => $userId,
            'total_amount' => array_sum(array_column($cart, 'total')),
            'delivery_address' => $address,
            'notes' => $postData['notes'] ?? '',
            'delivery_method' => $deliveryMethod,
            'payment_method' => $postData['payment_method'] ?? 'cash',
            'change_for' => ($postData['payment_method'] === 'cash') ? ($postData['change_for'] ?? null) : null,
            'items' => []
        ];

        // Format Items for OrderController
        foreach ($cart as $item) {
            $orderItem = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_total'],
                'subtotal' => $item['total'],
                'flavors' => array_column($item['flavors'] ?? [], 'id')
            ];
            $orderData['items'][] = $orderItem;
        }

        try {
            $orderId = $this->orderController->store($orderData);
            unset($_SESSION['cart']);
            return ['success' => true, 'order_id' => $orderId];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
