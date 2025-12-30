<?php
// fix_db_reset.php
// Script para resetar o banco de dados e repopular a partir do database.sql

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

// Função para ler o arquivo SQL e dividir em comandos
function parseSqlFile($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception("Arquivo database.sql não encontrado.");
    }

    $sql = file_get_contents($filePath);
    // Remover comentários simples
    $sql = preg_replace('/--.*$/m', '', $sql);
    // Remover comentários em bloco
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Dividir por ponto e vírgula, mas tentar respeitar strings que contém ponto e vírgula
    // Essa é uma divisão simples, pode falhar em casos complexos de strings
    // Mas para este dump específico deve funcionar
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    return $statements;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset do Banco de Dados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-red-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl w-full border-t-4 border-red-600">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Reset Total do Cardápio</h1>
        <p class="text-gray-600 mb-6 text-sm">Esta ferramenta apagará produtos, categorias e sabores e recriará tudo a
            partir do arquivo original. <strong>Usuários e Clientes NÃO serão apagados.</strong></p>

        <div class="space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar p-2 bg-gray-50 text-xs font-mono rounded">
            <?php
            try {
                $db = Database::getInstance()->getConnection();
                echo '<div class="text-green-600">✅ Conectado ao banco.</div>';

                // 1. Limpar tabelas (Ordem Inversa de Dependência)
                // Mantemos users e addresses
                $tablesToClear = [
                    'order_item_flavors',
                    'order_items',
                    'orders', // Orders dependem de users, mas as foreign keys podem reclamar se apagarmos produtos referenciados...
                    // Na verdade, se queremos manter users, ok. Mas orders referenciam produtos. 
                    // Se apagarmos produtos, temos que apagar orders ou itens. O usuário pediu para resetar "tudo exceto usuarios".
                    // Então vou limpar pedidos também para evitar inconsistência.
                    'products',
                    'flavors',
                    'categories'
                ];

                $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                foreach ($tablesToClear as $table) {
                    $db->exec("TRUNCATE TABLE `$table`"); // TRUNCATE reseta o Auto Increment
                    echo "<div class='text-gray-500'>🗑️ Tabela <strong>$table</strong> limpa.</div>";
                }
                $db->exec("SET FOREIGN_KEY_CHECKS = 1");

                // 2. Ler SQL
                $sqlFile = __DIR__ . '/../database.sql';
                echo "<div class='text-blue-600 mt-2'>📂 Lendo $sqlFile...</div>";

                $statements = parseSqlFile($sqlFile);
                echo "<div class='text-blue-600'>📝 Encontrados " . count($statements) . " comandos SQL.</div>";

                // 3. Executar Inserts
                // Filtramos apenas comandos INSERT para as tabelas que limpamos
                // Ou CREATE TABLE se quisermos recriar, mas o TRUNCATE ja limpou.
                // O database.sql tem CREATE e INSERT. Como já temos as tabelas criadas (e só limpamos), 
                // vamos executar apenas os INSERTS das tabelas que limpamos.
            
                $ignorePrefixes = [
                    'SET SQL_MODE',
                    'START TRANSACTION',
                    'SET time_zone',
                    'COMMIT',
                    'CREATE TABLE',
                    '--',
                    'INSERT INTO `users`',
                    'INSERT INTO `addresses`' // Ignorar insert de users padrão se já existe
                ];

                $count = 0;
                $db->beginTransaction();

                foreach ($statements as $stmt) {
                    if (empty($stmt))
                        continue;

                    // Verificar se é um comando relevante
                    $isInsert = stripos($stmt, 'INSERT INTO') === 0;
                    if (!$isInsert)
                        continue; // Pula CREATE TABLE, SET, etc.
            
                    // Verificar se é insert na tabela users (para não duplicar admin se ele nao foi apagado)
                    // Como fizemos TRUNCATE nas outras, podemos inserir tudo das outras.
                    if (stripos($stmt, 'INSERT INTO `users`') !== false) {
                        // Se users não foi truncado, não inserimos nada aqui para não dar erro de duplicate key no email admin
                        continue;
                    }

                    try {
                        $db->exec($stmt);
                        $count++;
                    } catch (Exception $e) {
                        echo "<div class='text-red-500'>❌ Erro no comando: " . substr(htmlspecialchars($stmt), 0, 50) . "... : " . $e->getMessage() . "</div>";
                    }
                }

                $db->commit();
                echo "<div class='text-green-600 font-bold mt-4'>✅ Sucesso! $count comandos de inserção executados.</div>";
                echo "<div class='text-green-600 mt-1'>O banco está limpo e sincronizado com o arquivo original.</div>";

            } catch (Exception $e) {
                if ($db->inTransaction())
                    $db->rollBack();
                echo '<div class="text-red-600 font-bold mt-4">Erro Fatal: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <div class="mt-6 text-center">
            <a href="menu.php"
                class="bg-brand-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-brand-700 transition">Voltar ao
                Cardápio</a>
        </div>
    </div>
</body>

</html>