<?php
// fix_db_encoding.php
// Script Web para corrigir erros de acentuação no banco de dados
// Acesso: seudominio.com/fix_db_encoding.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correção do Banco de Dados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl w-full">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Ferramenta de Correção do Banco</h1>

        <div class="space-y-4">
            <?php
            try {
                // Tenta conectar usando a classe padrão do sistema
                $db = Database::getInstance()->getConnection();
                echo '<div class="p-4 bg-green-50 text-green-700 rounded-lg border border-green-200 mb-4">✅ Conectado ao banco de dados com sucesso.</div>';

                // Usando HEX para garantir que o PHP/HTML não deturpe os caracteres durante o envio
                // 'Pedaços' em UTF-8 HEX = 50656461C3A76F73
                // ' até ' em UTF-8 HEX = 206174C3A920
            
                // 1. Corrigir 'Pedaos' -> 'Pedaços'
                $sql1 = "UPDATE products SET description = REPLACE(description, 'Pedaos', CAST(0x50656461C3A76F73 AS CHAR CHARACTER SET utf8mb4))";
                $stmt1 = $db->query($sql1);
                $rows1 = $stmt1->rowCount();

                echo '<div class="flex items-center justify-between p-3 bg-gray-50 rounded">';
                echo '<span>Corrigindo "Pedaos" para "Pedaços"...</span>';
                if ($rows1 > 0) {
                    echo '<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">' . $rows1 . ' registros alterados</span>';
                } else {
                    echo '<span class="px-3 py-1 bg-gray-200 text-gray-600 rounded-full text-sm">Nada a alterar</span>';
                }
                echo '</div>';

                // 2. Corrigir ' at ' -> ' até '
                $sql2 = "UPDATE products SET description = REPLACE(description, ' at ', CAST(0x206174C3A920 AS CHAR CHARACTER SET utf8mb4))";
                $stmt2 = $db->query($sql2);
                $rows2 = $stmt2->rowCount();

                echo '<div class="flex items-center justify-between p-3 bg-gray-50 rounded">';
                echo '<span>Corrigindo " at " para " até "...</span>';
                if ($rows2 > 0) {
                    echo '<span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">' . $rows2 . ' registros alterados</span>';
                } else {
                    echo '<span class="px-3 py-1 bg-gray-200 text-gray-600 rounded-full text-sm">Nada a alterar</span>';
                }
                echo '</div>';

                // Verificação visual
                echo '<h3 class="text-lg font-semibold mt-6 mb-2">Verificação Atual (Amostra):</h3>';
                echo '<div class="bg-gray-800 text-gray-200 p-4 rounded-lg font-mono text-sm overflow-x-auto">';

                $check = $db->query("SELECT id, name, description FROM products WHERE description LIKE '%Pedaços%' OR description LIKE '%até%' LIMIT 5");
                $results = $check->fetchAll(PDO::FETCH_ASSOC);

                if (count($results) > 0) {
                    foreach ($results as $row) {
                        echo "<div class='mb-2 pb-2 border-b border-gray-700 last:border-0'>";
                        echo "<span class='text-yellow-400'>[ID {$row['id']}]</span> <span class='text-blue-300'>{$row['name']}</span>:<br>";
                        echo "{$row['description']}";
                        echo "</div>";
                    }
                } else {
                    echo "Nenhum registro encontrado com a grafia correta (isso pode indicar que a correção falhou ou não havia dados target).";
                }
                echo '</div>';

            } catch (Exception $e) {
                echo '<div class="p-4 bg-red-50 text-red-700 rounded-lg border border-red-200">';
                echo '<strong>Erro Fatal:</strong> ' . $e->getMessage();
                echo '</div>';
            }
            ?>
        </div>

        <div class="mt-8 text-center text-sm text-gray-500">
            Pode apagar este arquivo do servidor após o uso.
        </div>
    </div>
</body>

</html>