<?php

namespace App\Config;

/**
 * Session Configuration
 * Configura a sessão para durar até fechar o navegador ou por muitas horas
 */
class Session
{
    public static function start()
    {
        // Configurações de sessão antes de iniciar
        // Cookie dura até fechar o navegador (0 = até fechar)
        // Mas também configuramos o timeout do servidor para ser bem longo
        
        // Cookie de sessão válido até fechar o navegador OU por 24 horas
        ini_set('session.cookie_lifetime', 86400); // 24 horas em segundos
        
        // Tempo de inatividade antes de expirar (8 horas = 28800 segundos)
        // Isso garante que mesmo sem atividade, a sessão dura a noite toda
        ini_set('session.gc_maxlifetime', 28800); // 8 horas
        
        // Garante que o cookie seja seguro e httpOnly
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        
        // Inicia a sessão se ainda não foi iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Renova o cookie de sessão a cada requisição para manter vivo
        if (isset($_SESSION['user_id'])) {
            setcookie(session_name(), session_id(), [
                'expires' => time() + 86400, // 24 horas
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }
}

