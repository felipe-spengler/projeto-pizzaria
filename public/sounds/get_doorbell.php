<?php
/**
 * Gera ou retorna um áudio de campainha
 * Usa um CDN público confiável para o áudio
 */
header('Content-Type: audio/mpeg');
header('Cache-Control: public, max-age=31536000');

// URL de um áudio de campainha público (pode ser substituído por arquivo local)
$audioUrl = 'https://assets.mixkit.co/sfx/preview/mixkit-doorbell-single-press-569.mp3';

// Se o arquivo existe localmente, usa ele
$localFile = __DIR__ . '/doorbell.mp3';
if (file_exists($localFile)) {
    readfile($localFile);
    exit;
}

// Caso contrário, redireciona para o CDN (ou você pode fazer proxy)
// Por enquanto, vamos criar um áudio simples
echo file_get_contents($audioUrl);
exit;

