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
            <div class="space-y-4 max-h-[50vh] overflow-y-auto custom-scrollbar p-4 bg-gray-900 text-green-400 font-mono text-xs rounded-lg mb-6 shadow-inner">
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
                        if (empty($stmt)) continue;
                        $isInsert = stripos($stmt, 'INSERT INTO') === 0;
                        if (!$isInsert) continue; 
                        
                        if (stripos($stmt, 'INSERT INTO `users`') !== false) continue;
                        
                        try {
                            $db->exec($stmt);
                            $count++;
                        } catch (Exception $e) {
                            echo "<div class='text-red-400'>❌ Erro: " . substr(htmlspecialchars($stmt), 0, 50) . "...</div>";
                        }
                    }
                    
                    $db->commit();
                    echo "<div class='text-white font-bold mt-4 border-t border-gray-700 pt-2'>✅ Sucesso! $count registros importados.</div>";
                    
                } catch (Exception $e) {
                    if ($db->inTransaction()) $db->rollBack();
                    echo '<div class="text-red-500 font-bold mt-4">Erro Fatal: ' . $e->getMessage() . '</div>';
                }
                ?>
            </div>
            
            <div class="text-center">
                <a href="menu.php" class="inline-flex items-center gap-2 bg-gray-800 text-white px-6 py-3 rounded-lg font-bold hover:bg-gray-700 transition">
                    <i class="fas fa-check"></i> Ir para o Cardápio
                </a>
                <a href="fix_db_reset.php" class="ml-4 text-gray-500 hover:text-gray-800 underline text-sm">Nova Importação</a>
            </div>

        <?php else: ?>

            <form action="" method="POST" enctype="multipart/form-data" class="border-2 border-dashed border-gray-300 rounded-xl p-8 flex flex-col items-center justify-center hover:border-brand-500 hover:bg-brand-50 transition-all cursor-pointer group" onclick="document.getElementById('fileInput').click()">
                <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h3 class="font-bold text-gray-700 text-lg mb-1">Clique para selecionar o arquivo</h3>
                <p class="text-gray-400 text-sm mb-4">Ou arraste o arquivo database.sql aqui</p>
                <input type="file" name="sql_file" id="fileInput" accept=".sql" class="hidden" onchange="document.getElementById('uploadBtn').classList.remove('hidden'); document.querySelector('h3').innerText = this.files[0].name">
                
                <button id="uploadBtn" type="submit" class="hidden mt-4 bg-brand-600 text-white px-8 py-3 rounded-lg font-bold shadow-lg hover:bg-brand-700 transition-all transform hover:-translate-y-1" onclick="event.stopPropagation()">
                    <i class="fas fa-play mr-2"></i> Iniciar Importação
                </button>
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
            document.getElementById('fileInput').files = files;
            
            // Trigger change event manually
            const event = new Event('change');
            document.getElementById('fileInput').dispatchEvent(event);
        }, false);
    </script>
</body>
</html>