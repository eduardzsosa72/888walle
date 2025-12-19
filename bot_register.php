<?php
// ============================================
// BOT DE TELEGRAM PARA REGISTRO CON AUTORIZACIÓN
// ============================================

// Configuración
define('BOT_TOKEN', '7969207140:AAGAxpi-uWlAGhqL294f0F_Hk_T6RGSv4Ng');
define('ADMIN_CHAT_ID', '6319087504'); // Tu Chat ID
define('DB_HOST', 'gokucheker.ceheeiow0knm.us-east-1.rds.amazonaws.com');
define('DB_NAME', '888wallet_db');
define('DB_USER', 'admin');
define('DB_PASS', 'gokucheker123');

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents('bot_error.log', date('Y-m-d H:i:s') . " - Error DB: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    exit;
}

// Obtener datos de Telegram
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    exit;
}

// Guardar log
file_put_contents('telegram_updates.log', date('Y-m-d H:i:s') . " - " . json_encode($update) . PHP_EOL, FILE_APPEND);

// Procesar mensaje
$chat_id = $update['message']['chat']['id'] ?? null;
$user_id = $update['message']['from']['id'] ?? null;
$username = $update['message']['from']['username'] ?? null;
$first_name = $update['message']['from']['first_name'] ?? '';
$last_name = $update['message']['from']['last_name'] ?? '';
$text = $update['message']['text'] ?? '';

// Comando /start
if ($text === '/start') {
    $message = "👋 *Bienvenido al Sistema 888Wallet*\n\n";
    $message .= "Para solicitar acceso al sistema, utiliza el comando:\n";
    $message .= "`/registro`\n\n";
    $message .= "También puedes usar:\n";
    $message .= "`/estado` - Ver estado de tu solicitud\n";
    $message .= "`/ayuda` - Mostrar esta ayuda";
    
    sendMessage($chat_id, $message);
    exit;
}

// Comando /registro
if ($text === '/registro') {
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telegram_id = ?");
    $stmt->execute([$user_id]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        $status = $existing_user['activo'] == 1 ? "✅ ACTIVO" : "⏳ PENDIENTE";
        $message = "📋 *Ya tienes una solicitud*\n\n";
        $message .= "🆔 ID: `" . $user_id . "`\n";
        $message .= "👤 Nombre: " . $existing_user['nombre'] . "\n";
        $message .= "📅 Fecha: " . $existing_user['fecha_registro'] . "\n";
        $message .= "🔒 Estado: " . $status . "\n\n";
        
        if ($existing_user['activo'] == 1) {
            $message .= "Tu cuenta está activa. Puedes acceder al sistema con:\n";
            $message .= "Contraseña: `888team`";
        } else {
            $message .= "Tu solicitud está en revisión. Te notificaré cuando sea aprobada.";
        }
        
        sendMessage($chat_id, $message);
        exit;
    }
    
    // Crear solicitud pendiente
    $nombre_completo = trim($first_name . ' ' . $last_name);
    $username_display = $username ? "@" . $username : "Sin username";
    $fecha_registro = date('Y-m-d H:i:s');
    
    try {
        $stmt = $pdo->prepare("INSERT INTO solicitudes_registro 
                              (telegram_id, username, nombre, fecha_solicitud, estado) 
                              VALUES (?, ?, ?, ?, 'pendiente')");
        $stmt->execute([$user_id, $username, $nombre_completo, $fecha_registro]);
        
        // Mensaje al usuario
        $user_message = "✅ *Solicitud Enviada*\n\n";
        $user_message .= "📋 Tu solicitud ha sido enviada al administrador.\n";
        $user_message .= "👤 Nombre: " . $nombre_completo . "\n";
        $user_message .= "🆔 Telegram: " . $username_display . "\n";
        $user_message .= "🔢 ID: `" . $user_id . "`\n";
        $user_message .= "📅 Fecha: " . $fecha_registro . "\n\n";
        $user_message .= "Recibirás una notificación cuando tu solicitud sea revisada.";
        
        sendMessage($chat_id, $user_message);
        
        // Mensaje al administrador
        $admin_message = "📋 *NUEVA SOLICITUD DE REGISTRO*\n\n";
        $admin_message .= "👤 Nombre: " . $nombre_completo . "\n";
        $admin_message .= "🆔 Username: " . $username_display . "\n";
        $admin_message .= "🔢 ID Telegram: `" . $user_id . "`\n";
        $admin_message .= "📅 Fecha: " . $fecha_registro . "\n\n";
        $admin_message .= "Para aprobar:\n";
        $admin_message .= "`/aprobar_" . $user_id . "`\n\n";
        $admin_message .= "Para rechazar:\n";
        $admin_message .= "`/rechazar_" . $user_id . "`";
        
        sendMessage(ADMIN_CHAT_ID, $admin_message);
        
    } catch (Exception $e) {
        sendMessage($chat_id, "❌ Error al procesar tu solicitud. Intenta más tarde.");
        file_put_contents('bot_error.log', date('Y-m-d H:i:s') . " - Error registro: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
    
    exit;
}

// Comando /estado
if ($text === '/estado') {
    // Verificar en solicitudes
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro WHERE telegram_id = ?");
    $stmt->execute([$user_id]);
    $solicitud = $stmt->fetch();
    
    if ($solicitud) {
        $estado = $solicitud['estado'] == 'aprobado' ? "✅ APROBADO" : "⏳ PENDIENTE";
        $message = "📊 *Estado de tu Solicitud*\n\n";
        $message .= "👤 Nombre: " . $solicitud['nombre'] . "\n";
        $message .= "🆔 ID: `" . $user_id . "`\n";
        $message .= "📅 Fecha solicitud: " . $solicitud['fecha_solicitud'] . "\n";
        $message .= "🔒 Estado: " . $estado . "\n\n";
        
        if ($solicitud['estado'] == 'aprobado') {
            $message .= "Tu cuenta ha sido aprobada. Ahora puedes acceder al sistema:\n";
            $message .= "🌐 URL: [888Wallet](https://888wallet.vpskraker.shop/index.php)\n";
            $message .= "🔑 Contraseña: `888team`";
        } else {
            $message .= "Tu solicitud está en proceso de revisión.";
        }
        
        sendMessage($chat_id, $message);
    } else {
        // Verificar si ya es usuario activo
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telegram_id = ?");
        $stmt->execute([$user_id]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            $message = "✅ *Cuenta Activa*\n\n";
            $message .= "Tu cuenta ya está activa en el sistema.\n";
            $message .= "🌐 URL: [888Wallet](https://888wallet.vpskraker.shop/index.php)\n";
            $message .= "🔑 Contraseña: `888team`\n\n";
            $message .= "👤 Nombre: " . $usuario['nombre'] . "\n";
            $message .= "📅 Registro: " . $usuario['fecha_registro'];
            
            sendMessage($chat_id, $message);
        } else {
            sendMessage($chat_id, "❌ No tienes solicitudes pendientes. Usa `/registro` para solicitar acceso.");
        }
    }
    exit;
}

// Comando /ayuda
if ($text === '/ayuda') {
    $message = "🆘 *Comandos Disponibles*\n\n";
    $message .= "`/start` - Iniciar el bot\n";
    $message .= "`/registro` - Solicitar acceso al sistema\n";
    $message .= "`/estado` - Ver estado de tu solicitud\n";
    $message .= "`/ayuda` - Mostrar esta ayuda\n\n";
    $message .= "Para soporte, contacta al administrador.";
    
    sendMessage($chat_id, $message);
    exit;
}

// Comandos del administrador (aprobar/rechazar)
if (strpos($text, '/aprobar_') === 0 && $chat_id == ADMIN_CHAT_ID) {
    $user_id_aprobar = str_replace('/aprobar_', '', $text);
    
    // Buscar solicitud
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro WHERE telegram_id = ? AND estado = 'pendiente'");
    $stmt->execute([$user_id_aprobar]);
    $solicitud = $stmt->fetch();
    
    if ($solicitud) {
        // Crear usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (telegram_id, username, nombre, activo, fecha_registro) 
                              VALUES (?, ?, ?, 1, NOW())");
        $stmt->execute([$solicitud['telegram_id'], $solicitud['username'], $solicitud['nombre']]);
        
        // Actualizar estado de solicitud
        $stmt = $pdo->prepare("UPDATE solicitudes_registro SET estado = 'aprobado', fecha_resolucion = NOW() WHERE telegram_id = ?");
        $stmt->execute([$user_id_aprobar]);
        
        // Notificar al usuario
        $user_message = "🎉 *¡Solicitud Aprobada!*\n\n";
        $user_message .= "Tu solicitud para acceder a 888Wallet ha sido *APROBADA*.\n\n";
        $user_message .= "🌐 URL de acceso: [888Wallet](https://888wallet.vpskraker.shop/index.php)\n";
        $user_message .= "🔑 Contraseña: `888team`\n\n";
        $user_message .= "👤 Nombre: " . $solicitud['nombre'] . "\n";
        $user_message .= "🆔 ID Telegram: `" . $solicitud['telegram_id'] . "`\n\n";
        $user_message .= "¡Bienvenido al sistema!";
        
        sendMessage($user_id_aprobar, $user_message);
        
        // Confirmar al admin
        sendMessage(ADMIN_CHAT_ID, "✅ Usuario " . $solicitud['nombre'] . " (@" . $solicitud['username'] . ") aprobado correctamente.");
        
        // Log
        file_put_contents('registros_aprobados.log', date('Y-m-d H:i:s') . " - Aprobado: " . $solicitud['nombre'] . " (ID: " . $user_id_aprobar . ")" . PHP_EOL, FILE_APPEND);
        
    } else {
        sendMessage(ADMIN_CHAT_ID, "❌ No se encontró solicitud pendiente para ese ID.");
    }
    exit;
}

if (strpos($text, '/rechazar_') === 0 && $chat_id == ADMIN_CHAT_ID) {
    $user_id_rechazar = str_replace('/rechazar_', '', $text);
    
    // Buscar solicitud
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro WHERE telegram_id = ? AND estado = 'pendiente'");
    $stmt->execute([$user_id_rechazar]);
    $solicitud = $stmt->fetch();
    
    if ($solicitud) {
        // Actualizar estado de solicitud
        $stmt = $pdo->prepare("UPDATE solicitudes_registro SET estado = 'rechazado', fecha_resolucion = NOW() WHERE telegram_id = ?");
        $stmt->execute([$user_id_rechazar]);
        
        // Notificar al usuario
        $user_message = "❌ *Solicitud Rechazada*\n\n";
        $user_message .= "Tu solicitud para acceder a 888Wallet ha sido *RECHAZADA*.\n\n";
        $user_message .= "📋 Motivo: *Revisión del administrador*\n";
        $user_message .= "🕐 Fecha: " . date('d/m/Y H:i:s') . "\n\n";
        $user_message .= "Para más información, contacta al administrador.";
        
        sendMessage($user_id_rechazar, $user_message);
        
        // Confirmar al admin
        sendMessage(ADMIN_CHAT_ID, "❌ Solicitud de " . $solicitud['nombre'] . " rechazada.");
        
        // Log
        file_put_contents('registros_rechazados.log', date('Y-m-d H:i:s') . " - Rechazado: " . $solicitud['nombre'] . " (ID: " . $user_id_rechazar . ")" . PHP_EOL, FILE_APPEND);
        
    } else {
        sendMessage(ADMIN_CHAT_ID, "❌ No se encontró solicitud pendiente para ese ID.");
    }
    exit;
}

// Comando /lista (admin) - Ver todas las solicitudes
if ($text === '/lista' && $chat_id == ADMIN_CHAT_ID) {
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro ORDER BY fecha_solicitud DESC");
    $stmt->execute();
    $solicitudes = $stmt->fetchAll();
    
    if (empty($solicitudes)) {
        sendMessage(ADMIN_CHAT_ID, "📭 No hay solicitudes pendientes.");
        exit;
    }
    
    $message = "📋 *Solicitudes Pendientes*\n\n";
    
    foreach ($solicitudes as $solicitud) {
        $estado_emoji = $solicitud['estado'] == 'pendiente' ? "⏳" : ($solicitud['estado'] == 'aprobado' ? "✅" : "❌");
        $message .= $estado_emoji . " " . $solicitud['nombre'] . " (@" . $solicitud['username'] . ")\n";
        $message .= "ID: `" . $solicitud['telegram_id'] . "`\n";
        $message .= "Fecha: " . $solicitud['fecha_solicitud'] . "\n";
        $message .= "Estado: " . $solicitud['estado'] . "\n";
        
        if ($solicitud['estado'] == 'pendiente') {
            $message .= "Aprobar: `/aprobar_" . $solicitud['telegram_id'] . "`\n";
            $message .= "Rechazar: `/rechazar_" . $solicitud['telegram_id'] . "`\n";
        }
        
        $message .= "──────────────\n";
    }
    
    sendMessage(ADMIN_CHAT_ID, $message);
    exit;
}

// Función para enviar mensajes
function sendMessage($chat_id, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
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
}
?>