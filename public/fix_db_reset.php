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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg p-8 max-w-2xl w-full border-t-4 border-brand-600">
        <h1 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-2">
            <i class="fas fa-database text-brand-600"></i> Importar/Resetar Banco de Dados
        </h1>
        <p class="text-gray-600 mb-6 text-sm">
            Faça upload do arquivo <code>.sql</code> para resetar e popular as tabelas. <br>
            <strong class="text-red-500">Atenção:</strong> Isso apagará produtos e categorias existentes!
        </p>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])): ?>
            <div
                class="space-y-4 max-h-[50vh] overflow-y-auto custom-scrollbar p-4 bg-gray-900 text-green-400 font-mono text-xs rounded-lg mb-6 shadow-inner">
                <?php
                try {
                    $file = $_FILES['sql_file'];
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("Erro no upload do arquivo. Código: " . $file['error']);
                    }
                    if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'sql') {
                        throw new Exception("Por favor envie apenas arquivos .sql");
                    }

                    $db = Database::getInstance()->getConnection();
                    echo '<div>⚡ Conectado ao banco.</div>';

                    // 1. Limpar tabelas
                    $tablesToClear = ['order_item_flavors', 'order_items', 'orders', 'products', 'flavors', 'categories'];

                    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                    foreach ($tablesToClear as $table) {
                        $db->exec("TRUNCATE TABLE `$table`");
                        echo "<div>🗑️ Tabela <strong>$table</strong> limpa.</div>";
                    }
                    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

                    // 2. Ler Arquivo Enviado
                    $sqlContent = file_get_contents($file['tmp_name']);

                    // Tratamento simples
                    $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
                    $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

                    $statements = array_filter(array_map('trim', explode(';', $sqlContent)));

                    echo "<div class='text-blue-400 mt-2'>� Processando " . count($statements) . " comandos...</div>";

                    $db->beginTransaction();
                    $count = 0;

                    foreach ($statements as $stmt) {
                        if (empty($stmt))
                            continue;

                        // Determine type
                        $params = [];
                        $stmtUpper = strtoupper($stmt);
                        $isInsert = strpos($stmtUpper, 'INSERT INTO') === 0;
                        $isCreate = strpos($stmtUpper, 'CREATE TABLE') === 0;
                        $isAlter = strpos($stmtUpper, 'ALTER TABLE') === 0;

                        // Skip users insert to avoid admin overwrite/conflict if strategy implies
                        if ($isInsert && strpos($stmtUpper, 'INSERT INTO `USERS`') !== false) {
                            // Check if we should skip. The original script skipped it.
                            // Let's keep skipping users insert if the table wasn't truncated (it wasn't in the list above)
                            continue;
                        }

                        // Allow INSERT, CREATE, ALTER
                        if (!$isInsert && !$isCreate && !$isAlter)
                            continue;

                        try {
                            $db->exec($stmt);
                            $count++;

                            // Log Success based on type
                            $preview = substr($stmt, 0, 60);
                            $previewSafe = htmlspecialchars($preview);
                            if ($isCreate) {
                                echo "<div class='text-cyan-400'>🔨 CRIADO: <span class='text-gray-400 text-[10px]'>$previewSafe...</span></div>";
                            } elseif ($isAlter) {
                                echo "<div class='text-purple-400'>🔧 ALTERADO: <span class='text-gray-400 text-[10px]'>$previewSafe...</span></div>";
                            } elseif ($isInsert) {
                                // Extract table name for cleaner log
                                preg_match('/INSERT INTO `?(\w+)`?/', $stmt, $matches);
                                $tbl = $matches[1] ?? 'tabela';
                                echo "<div class='text-green-500'>➕ INSERT: $tbl</div>";
                            }
                        } catch (Exception $e) {
                            // Ignore "Table already exists" errors (Code 42S01 or message comparison)
                            // MySQL Error 1050: Table already exists
                            if (strpos($e->getMessage(), '1050') !== false) {
                                echo "<div class='text-yellow-500 text-xs'>⚠️ Tabela já existe (ignorado).</div>";
                            }
                            // Ignore "Column already exists" (Code 42S21 or 1060)
                            elseif (strpos($e->getMessage(), '1060') !== false) {
                                echo "<div class='text-yellow-500 text-xs'>⚠️ Coluna já existe (ignorado).</div>";
                            } else {
                                echo "<div class='text-red-400'>❌ Erro: " . substr(htmlspecialchars($stmt), 0, 50) . "... (" . $e->getMessage() . ")</div>";
                            }
                        }
                    }

                    $db->commit();
                    echo "<div class='text-white font-bold mt-4 border-t border-gray-700 pt-2'>✅ Sucesso! $count comandos executados.</div>";

                } catch (Exception $e) {
                    if ($db->inTransaction())
                        $db->rollBack();
                    echo '<div class="text-red-500 font-bold mt-4">Erro Fatal: ' . $e->getMessage() . '</div>';
                }
                ?>
            </div>

            <div class="text-center">
                <a href="menu.php"
                    class="inline-flex items-center gap-2 bg-gray-800 text-white px-6 py-3 rounded-lg font-bold hover:bg-gray-700 transition">
                    <i class="fas fa-check"></i> Ir para o Cardápio
                </a>
                <a href="fix_db_reset.php" class="ml-4 text-gray-500 hover:text-gray-800 underline text-sm">Nova
                    Importação</a>
            </div>

        <?php else: ?>

            <form action="" method="POST" enctype="multipart/form-data"
                class="border-2 border-dashed border-gray-300 rounded-xl p-8 flex flex-col items-center justify-center hover:border-brand-500 hover:bg-brand-50 transition-all cursor-pointer group relative"
                onclick="document.getElementById('fileInput').click()">

                <!-- Loading Overlay -->
                <div id="loadingOverlay"
                    class="absolute inset-0 bg-white/80 flex flex-col items-center justify-center z-10 hidden backdrop-blur-sm rounded-xl">
                    <i class="fas fa-circle-notch fa-spin text-4xl text-brand-600 mb-2"></i>
                    <p class="font-bold text-gray-700">Processando...</p>
                </div>

                <div
                    class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h3 class="font-bold text-gray-700 text-lg mb-1">Clique ou Arraste o arquivo aqui</h3>
                <p class="text-gray-400 text-sm mb-4">Selecione o arquivo <strong>database.sql</strong></p>

                <input type="file" name="sql_file" id="fileInput" accept=".sql" class="hidden"
                    onchange="document.getElementById('loadingOverlay').classList.remove('hidden'); this.form.submit();">
            </form>

        <?php endif; ?>
    </div>

    <script>
        // Drag and Drop visual feedback
        const dropZone = document.querySelector('form');

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.classList.add('border-brand-500', 'bg-brand-50');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-brand-500', 'bg-brand-50');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                document.getElementById('fileInput').files = files;
                document.getElementById('loadingOverlay').classList.remove('hidden');
                dropZone.submit();
            }
        }, false);
    </script>
</body>

</html>