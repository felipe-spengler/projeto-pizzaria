<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Acesso negado');
}

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    die('Pedido não encontrado');
}

$db = Database::getInstance()->getConnection();

// Fetch Order
$stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die('Pedido não encontrado');
}

// Fetch Items
$stmt = $db->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark as viewed
$stmt = $db->prepare("UPDATE orders SET viewed = TRUE WHERE id = ?");
$stmt->execute([$orderId]);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Pedido #<?= $orderId ?></title>
    <style>
        @media print {
            body {
                font-family: 'Courier New', monospace;
                font-size: 12px;
                margin: 0;
                padding: 10px;
                width: 80mm;
                /* Thermal printer width */
            }

            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Courier New', monospace;
            max-width: 300px;
            margin: 20px auto;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }

        .section {
            margin: 15px 0;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .item {
            margin: 8px 0;
        }

        .item-name {
            font-weight: bold;
        }

        .flavors {
            font-size: 11px;
            margin-left: 15px;
            font-style: italic;
        }

        .total {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            text-align: right;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="header">
        CASA NOVA PIZZARIA<br>
        PEDIDO #<?= $orderId ?>
    </div>

    <div class="section">
        <div class="row">
            <span>Data/Hora:</span>
            <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
        </div>
        <div class="row">
            <span>Cliente:</span>
            <span><?= $order['customer_name'] ?></span>
        </div>
        <?php if ($order['phone']): ?>
            <div class="row">
                <span>Telefone:</span>
                <span><?= $order['phone'] ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="section">
        <div style="font-weight: bold; margin-bottom: 5px;">ENTREGA:</div>
        <?php if ($order['delivery_method'] == 'delivery'): ?>
            <div><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></div>
        <?php else: ?>
            <div><strong>RETIRADA NO BALCÃO</strong></div>
        <?php endif; ?>
    </div>

    <div class="section">
        <div style="font-weight: bold; margin-bottom: 5px;">PAGAMENTO:</div>
        <div><?php
        $paymentLabels = [
            'pix' => 'PIX',
            'credit_card' => 'Cartão de Crédito',
            'debit_card' => 'Cartão de Débito',
            'cash' => 'Dinheiro'
        ];
        echo $paymentLabels[$order['payment_method']] ?? ucfirst($order['payment_method']);
        ?></div>
    </div>

    <div class="section">
        <div style="font-weight: bold; margin-bottom: 8px;">ITENS DO PEDIDO:</div>
        <?php foreach ($items as $item): ?>
            <div class="item">
                <div class="row">
                    <span class="item-name"><?= $item['quantity'] ?>x <?= $item['product_name'] ?></span>
                    <span>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></span>
                </div>
                <?php
                // Fetch flavors
                $stmtF = $db->prepare("SELECT f.name FROM order_item_flavors oif JOIN flavors f ON oif.flavor_id = f.id WHERE oif.order_item_id = ?");
                $stmtF->execute([$item['id']]);
                $flavors = $stmtF->fetchAll(PDO::FETCH_COLUMN);

                if ($flavors): ?>
                    <div class="flavors">(<?= implode(', ', $flavors) ?>)</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($order['notes']): ?>
        <div class="section">
            <div style="font-weight: bold;">OBSERVAÇÕES:</div>
            <div><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
        </div>
    <?php endif; ?>

    <div class="total">
        TOTAL: R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
    </div>

    <div class="footer">
        ================================<br>
        Obrigado pela preferência!<br>
        Casa Nova Pizzaria<br>
        Tel: (49) 99945-9490
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #e11d48; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
            🖨️ Imprimir
        </button>
        <button onclick="window.close()"
            style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            Fechar
        </button>
    </div>

    <script>
        // Auto-print on load (optional, commented out for now)
        // window.onload = () => window.print();
    </script>
</body>

</html>