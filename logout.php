<?php
session_start();

// Enviar notificación de cierre de sesión si existe usuario
if (isset($_SESSION['telegram_id']) && isset($_SESSION['user_name'])) {
    // Configuración del Bot de Telegram (usa los mismos valores que en login.php)
    define('TELEGRAM_BOT_TOKEN', '8454388731:AAF8GHffHrsaSB8uAy8WEZLhsHcPptAIDFk');
    define('TELEGRAM_ADMIN_ID', '6319087504');
    
    $telegram_message = "👋 *Sesión Cerrada 888Wallet*\n\n";
    $telegram_message .= "👤 Usuario: " . $_SESSION['user_name'] . "\n";
    $telegram_message .= "🆔 Telegram ID: `" . $_SESSION['telegram_id'] . "`\n";
    $telegram_message .= "📅 Fecha: " . date('d/m/Y H:i:s') . "\n";
    $telegram_message .= "🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $telegram_message .= "🔒 Estado: SESIÓN FINALIZADA";
    
    // Función para enviar a Telegram
    function sendTelegramMessage($chat_id, $message) {
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
        
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
        return true;
    }
    
    sendTelegramMessage(TELEGRAM_ADMIN_ID, $telegram_message);
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir a la página principal
header("Location: index.php");
exit();
?>