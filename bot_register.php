<?php
// ============================================
// BOT DE REGISTRO 888WALLET - COMPLETO
// ============================================

// CONFIGURACIÓN - REEMPLAZA CON TUS DATOS
define('BOT_TOKEN', '7969207140:AAGAxpi-uWlAGhqL294f0F_Hk_T6RGSv4Ng');
define('ADMIN_CHAT_ID', '6319087504'); // Tu Chat ID
define('DOMINIO', 'https://888wallet.vpskraker.shop/index.php'); // Cambia por tu dominio real
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

// Log de entrada
if ($update) {
    file_put_contents('telegram_updates.log', date('Y-m-d H:i:s') . " - " . json_encode($update) . PHP_EOL, FILE_APPEND);
}

// Si es acceso directo, mostrar info
if (!$update && $_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "🤖 Bot de Registro 888Wallet activo\n";
    echo "📅 " . date('Y-m-d H:i:s') . "\n";
    echo "✅ Sistema funcionando\n";
    echo "🔗 Token: " . substr(BOT_TOKEN, 0, 10) . "...\n";
    exit;
}

// Si no hay update, salir
if (!$update) {
    exit;
}

// Procesar mensaje
$chat_id = $update['message']['chat']['id'] ?? null;
$user_id = $update['message']['from']['id'] ?? null;
$username = $update['message']['from']['username'] ?? null;
$first_name = $update['message']['from']['first_name'] ?? '';
$last_name = $update['message']['from']['last_name'] ?? '';
$text = $update['message']['text'] ?? '';

// ==================== COMANDOS PÚBLICOS ====================

// /start - Comando inicial
if ($text === '/start') {
    $message = "👋 *Bienvenido al Sistema 888Wallet*\n\n";
    $message .= "🌟 *Sistema privado de gestión de tarjetas*\n\n";
    $message .= "📋 *Comandos disponibles:*\n";
    $message .= "`/registro` - Solicitar acceso al sistema\n";
    $message .= "`/estado` - Ver estado de tu solicitud\n";
    $message .= "`/ayuda` - Mostrar ayuda\n";
    $message .= "`/id` - Ver tu ID de Telegram\n\n";
    $message .= "🔒 *Requisitos:*\n";
    $message .= "• Solicitud aprobada por administrador\n";
    $message .= "• Contraseña única proporcionada\n\n";
    $message .= "👑 *Administrador:* @Macrzz6";
    
    sendMessage($chat_id, $message);
    exit;
}

// /registro - Solicitar acceso
if ($text === '/registro') {
    procesarRegistro($chat_id, $user_id, $username, $first_name, $last_name);
    exit;
}

// /estado - Ver estado
if ($text === '/estado') {
    verificarEstado($chat_id, $user_id);
    exit;
}

// /ayuda - Mostrar ayuda
if ($text === '/ayuda') {
    $message = "🆘 *CENTRO DE AYUDA 888WALLET*\n\n";
    $message .= "📋 *Comandos disponibles:*\n";
    $message .= "`/start` - Iniciar el bot\n";
    $message .= "`/registro` - Solicitar acceso al sistema\n";
    $message .= "`/estado` - Ver estado de tu solicitud\n";
    $message .= "`/id` - Ver tu ID de Telegram\n";
    $message .= "`/ayuda` - Mostrar esta ayuda\n\n";
    $message .= "🔒 *Información importante:*\n";
    $message .= "• Todas las solicitudes son revisadas manualmente\n";
    $message .= "• El acceso es exclusivo y por invitación\n";
    $message .= "• Contraseña única para usuarios aprobados\n\n";
    $message .= "📞 *Soporte:* @Macrzz6";
    
    sendMessage($chat_id, $message);
    exit;
}

// /id - Ver ID de usuario
if ($text === '/id') {
    $message = "🆔 *TU ID DE TELEGRAM*\n\n";
    $message .= "🔢 *ID:* `" . $user_id . "`\n";
    $message .= "👤 *Nombre:* " . $first_name . " " . $last_name . "\n";
    $message .= "📛 *Username:* " . ($username ? "@" . $username : "No tiene") . "\n\n";
    $message .= "⚠️ *Importante:*\n";
    $message .= "Guarda este ID, lo necesitarás para el registro.";
    
    sendMessage($chat_id, $message);
    exit;
}

// ==================== COMANDOS DE ADMINISTRADOR ====================

if ($chat_id == ADMIN_CHAT_ID) {
    // /lista - Ver todas las solicitudes
    if ($text === '/lista') {
        listarSolicitudes();
        exit;
    }
    
    // /usuarios - Ver usuarios activos
    if ($text === '/usuarios') {
        listarUsuarios();
        exit;
    }
    
    // /aprobar_ID - Aprobar usuario
    if (strpos($text, '/aprobar_') === 0) {
        $user_id_aprobar = str_replace('/aprobar_', '', $text);
        aprobarUsuario($user_id_aprobar);
        exit;
    }
    
    // /rechazar_ID - Rechazar usuario
    if (strpos($text, '/rechazar_') === 0) {
        $user_id_rechazar = str_replace('/rechazar_', '', $text);
        rechazarUsuario($user_id_rechazar);
        exit;
    }
    
    // /activar_ID - Activar usuario (si estaba desactivado)
    if (strpos($text, '/activar_') === 0) {
        $user_id_activar = str_replace('/activar_', '', $text);
        activarUsuario($user_id_activar);
        exit;
    }
    
    // /desactivar_ID - Desactivar usuario
    if (strpos($text, '/desactivar_') === 0) {
        $user_id_desactivar = str_replace('/desactivar_', '', $text);
        desactivarUsuario($user_id_desactivar);
        exit;
    }
    
    // /broadcast - Mensaje a todos los usuarios
    if (strpos($text, '/broadcast ') === 0) {
        $mensaje = str_replace('/broadcast ', '', $text);
        broadcastMensaje($mensaje);
        exit;
    }
    
    // /estadisticas - Ver estadísticas
    if ($text === '/estadisticas') {
        mostrarEstadisticas();
        exit;
    }
    
    // /admin - Comandos de admin
    if ($text === '/admin') {
        $message = "👑 *PANEL DE ADMINISTRADOR*\n\n";
        $message .= "📊 *Comandos disponibles:*\n";
        $message .= "`/lista` - Ver solicitudes pendientes\n";
        $message .= "`/usuarios` - Ver usuarios activos\n";
        $message .= "`/estadisticas` - Ver estadísticas\n\n";
        $message .= "✅ *Aprobar/Rechazar:*\n";
        $message .= "`/aprobar_123456` - Aprobar usuario\n";
        $message .= "`/rechazar_123456` - Rechazar usuario\n";
        $message .= "`/activar_123456` - Activar usuario\n";
        $message .= "`/desactivar_123456` - Desactivar usuario\n\n";
        $message .= "📢 *Broadcast:*\n";
        $message .= "`/broadcast mensaje` - Enviar a todos\n\n";
        $message .= "📈 *Estadísticas actuales:*";
        
        sendMessage(ADMIN_CHAT_ID, $message);
        
        // Mostrar estadísticas después de 1 segundo
        sleep(1);
        mostrarEstadisticas();
        exit;
    }
}

// ==================== FUNCIONES PRINCIPALES ====================

function procesarRegistro($chat_id, $user_id, $username, $first_name, $last_name) {
    global $pdo;
    
    // Verificar si ya existe como usuario activo
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telegram_id = ?");
    $stmt->execute([$user_id]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        $estado = $existing_user['activo'] == 1 ? "✅ ACTIVO" : "❌ INACTIVO";
        
        $message = "📋 *YA ERES USUARIO*\n\n";
        $message .= "Tu cuenta ya existe en el sistema.\n\n";
        $message .= "👤 *Nombre:* " . $existing_user['nombre'] . "\n";
        $message .= "🆔 *ID:* `" . $user_id . "`\n";
        $message .= "🔒 *Estado:* " . $estado . "\n";
        $message .= "📅 *Registro:* " . $existing_user['fecha_registro'] . "\n\n";
        
        if ($existing_user['activo'] == 1) {
            $message .= "🌐 *Acceso al sistema:*\n";
            $message .= "URL: " . DOMINIO . "\n";
            $message .= "🔑 Contraseña: `888team`\n\n";
            $message .= "¡Bienvenido de nuevo!";
        } else {
            $message .= "❌ *Tu cuenta está desactivada.*\n";
            $message .= "Contacta al administrador para reactivarla.";
        }
        
        sendMessage($chat_id, $message);
        return;
    }
    
    // Verificar si ya tiene solicitud pendiente
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro WHERE telegram_id = ? AND estado = 'pendiente'");
    $stmt->execute([$user_id]);
    $existing_request = $stmt->fetch();
    
    if ($existing_request) {
        $message = "⏳ *SOLICITUD PENDIENTE*\n\n";
        $message .= "Ya tienes una solicitud en revisión.\n\n";
        $message .= "👤 *Nombre:* " . $existing_request['nombre'] . "\n";
        $message .= "📅 *Fecha:* " . $existing_request['fecha_solicitud'] . "\n";
        $message .= "🔒 *Estado:* PENDIENTE\n\n";
        $message .= "El administrador la revisará pronto.\n";
        $message .= "Recibirás una notificación cuando sea procesada.";
        
        sendMessage($chat_id, $message);
        return;
    }
    
    // Crear nueva solicitud
    $nombre_completo = trim($first_name . ' ' . $last_name);
    if (empty($nombre_completo) || $nombre_completo === ' ') {
        $nombre_completo = "Usuario Telegram";
    }
    
    $username_display = $username ? "@" . $username : "Sin username";
    $fecha_registro = date('d/m/Y H:i:s');
    
    try {
        $stmt = $pdo->prepare("INSERT INTO solicitudes_registro 
                              (telegram_id, username, nombre, fecha_solicitud, estado) 
                              VALUES (?, ?, ?, NOW(), 'pendiente')");
        $stmt->execute([$user_id, $username, $nombre_completo]);
        
        // Mensaje al usuario
        $user_message = "✅ *SOLICITUD ENVIADA*\n\n";
        $user_message .= "Tu solicitud ha sido enviada al administrador.\n\n";
        $user_message .= "📋 *Tus datos:*\n";
        $user_message .= "👤 *Nombre:* " . $nombre_completo . "\n";
        $user_message .= "📛 *Username:* " . $username_display . "\n";
        $user_message .= "🔢 *ID:* `" . $user_id . "`\n";
        $user_message .= "📅 *Fecha:* " . $fecha_registro . "\n\n";
        $user_message .= "⏰ *Tiempo de espera:*\n";
        $user_message .= "• Revisión: 24-48 horas\n";
        $user_message .= "• Notificación vía Telegram\n\n";
        $user_message .= "📞 *Contacto:* @Macrzz6";
        
        sendMessage($chat_id, $user_message);
        
        // Mensaje al administrador
        $admin_message = "📥 *NUEVA SOLICITUD DE REGISTRO*\n\n";
        $admin_message .= "👤 *Nombre:* " . $nombre_completo . "\n";
        $admin_message .= "📛 *Username:* " . $username_display . "\n";
        $admin_message .= "🔢 *ID Telegram:* `" . $user_id . "`\n";
        $admin_message .= "📅 *Fecha:* " . $fecha_registro . "\n";
        $admin_message .= "🌐 *IP:* " . $_SERVER['REMOTE_ADDR'] . "\n\n";
        $admin_message .= "✅ *Aprobar:*\n";
        $admin_message .= "`/aprobar_" . $user_id . "`\n\n";
        $admin_message .= "❌ *Rechazar:*\n";
        $admin_message .= "`/rechazar_" . $user_id . "`\n\n";
        $admin_message .= "📋 *Ver todas:* `/lista`";
        
        sendMessage(ADMIN_CHAT_ID, $admin_message);
        
        // Log
        file_put_contents('registros_nuevos.log', date('Y-m-d H:i:s') . " | Nueva: " . $nombre_completo . " | ID: " . $user_id . " | IP: " . $_SERVER['REMOTE_ADDR'] . PHP_EOL, FILE_APPEND);
        
    } catch (Exception $e) {
        $error_msg = "❌ *ERROR DEL SISTEMA*\n\n";
        $error_msg .= "No pudimos procesar tu solicitud.\n";
        $error_msg .= "Intenta nuevamente en unos minutos.\n\n";
        $error_msg .= "📞 *Soporte:* @Macrzz6";
        
        sendMessage($chat_id, $error_msg);
        
        file_put_contents('bot_error.log', date('Y-m-d H:i:s') . " - Error registro: " . $e->getMessage() . " | User: " . $user_id . PHP_EOL, FILE_APPEND);
    }
}

function verificarEstado($chat_id, $user_id) {
    global $pdo;
    
    // Verificar si ya es usuario activo
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telegram_id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $estado = $usuario['activo'] == 1 ? "✅ ACTIVO" : "❌ INACTIVO";
        
        $message = "📊 *ESTADO DE TU CUENTA*\n\n";
        $message .= "👤 *Nombre:* " . $usuario['nombre'] . "\n";
        $message .= "🆔 *ID:* `" . $user_id . "`\n";
        $message .= "🔒 *Estado:* " . $estado . "\n";
        $message .= "📅 *Registro:* " . $usuario['fecha_registro'] . "\n\n";
        
        if ($usuario['activo'] == 1) {
            $message .= "🌐 *Acceso al sistema:*\n";
            $message .= "URL: " . DOMINIO . "\n";
            $message .= "🔑 Contraseña: `888team`\n\n";
            $message .= "¡Tu cuenta está lista para usar!";
        } else {
            $message .= "❌ *Tu cuenta está desactivada.*\n";
            $message .= "Contacta al administrador para reactivarla.";
        }
        
        sendMessage($chat_id, $message);
        return;
    }
    
    // Verificar solicitudes
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro WHERE telegram_id = ? ORDER BY fecha_solicitud DESC");
    $stmt->execute([$user_id]);
    $solicitudes = $stmt->fetchAll();
    
    if (empty($solicitudes)) {
        $message = "❌ *SIN SOLICITUDES*\n\n";
        $message .= "No tienes solicitudes de registro.\n";
        $message .= "Usa el comando `/registro` para solicitar acceso.\n\n";
        $message .= "📞 *Ayuda:* @Macrzz6";
        
        sendMessage($chat_id, $message);
        return;
    }
    
    $ultima = $solicitudes[0];
    $estado_emoji = $ultima['estado'] == 'pendiente' ? "⏳" : ($ultima['estado'] == 'aprobado' ? "✅" : "❌");
    
    $message = $estado_emoji . " *ESTADO DE SOLICITUD*\n\n";
    $message .= "👤 *Nombre:* " . $ultima['nombre'] . "\n";
    $message .= "🆔 *ID:* `" . $user_id . "`\n";
    $message .= "📅 *Fecha solicitud:* " . $ultima['fecha_solicitud'] . "\n";
    $message .= "🔒 *Estado:* " . strtoupper($ultima['estado']) . "\n\n";
    
    if ($ultima['estado'] == 'aprobado') {
        $message .= "🎉 *¡SOLICITUD APROBADA!*\n\n";
        $message .= "Tu cuenta ha sido aprobada.\n";
        $message .= "Accede al sistema con:\n";
        $message .= "🌐 URL: " . DOMINIO . "\n";
        $message .= "🔑 Contraseña: `888team`\n\n";
        $message .= "¡Bienvenido a 888Wallet!";
    } elseif ($ultima['estado'] == 'pendiente') {
        $message .= "⏰ *EN REVISIÓN*\n\n";
        $message .= "Tu solicitud está siendo revisada.\n";
        $message .= "Tiempo estimado: 24-48 horas\n\n";
        $message .= "Recibirás una notificación cuando sea procesada.";
    } else {
        $message .= "❌ *SOLICITUD RECHAZADA*\n\n";
        $message .= "Tu solicitud fue rechazada.\n";
        $message .= "📅 *Fecha:* " . ($ultima['fecha_resolucion'] ?? 'N/A') . "\n\n";
        $message .= "Para más información, contacta al administrador.";
    }
    
    sendMessage($chat_id, $message);
}

function aprobarUsuario($user_id) {
    global $pdo;
    
    // Buscar solicitud pendiente
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro WHERE telegram_id = ? AND estado = 'pendiente'");
    $stmt->execute([$user_id]);
    $solicitud = $stmt->fetch();
    
    if (!$solicitud) {
        sendMessage(ADMIN_CHAT_ID, "❌ No se encontró solicitud pendiente para ID: `" . $user_id . "`");
        return;
    }
    
    try {
        // Verificar si ya existe como usuario
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telegram_id = ?");
        $stmt->execute([$user_id]);
        $usuario_existente = $stmt->fetch();
        
        if ($usuario_existente) {
            // Actualizar usuario existente
            $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1, nombre = ? WHERE telegram_id = ?");
            $stmt->execute([$solicitud['nombre'], $user_id]);
            
            $accion = "reactivado";
        } else {
            // Crear nuevo usuario
            $stmt = $pdo->prepare("INSERT INTO usuarios (telegram_id, username, nombre, activo, fecha_registro) 
                                  VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$solicitud['telegram_id'], $solicitud['username'], $solicitud['nombre']]);
            
            $accion = "aprobado";
        }
        
        // Actualizar solicitud
        $stmt = $pdo->prepare("UPDATE solicitudes_registro SET estado = 'aprobado', fecha_resolucion = NOW() 
                              WHERE telegram_id = ? AND estado = 'pendiente'");
        $stmt->execute([$user_id]);
        
        // Notificar al usuario
        $user_message = "🎉 *¡SOLICITUD APROBADA!*\n\n";
        $user_message .= "Tu solicitud para 888Wallet ha sido *APROBADA*.\n\n";
        $user_message .= "🌐 *Acceso al sistema:*\n";
        $user_message .= "URL: " . DOMINIO . "\n";
        $user_message .= "🔑 Contraseña: `888team`\n\n";
        $user_message .= "📋 *Tus datos:*\n";
        $user_message .= "👤 Nombre: " . $solicitud['nombre'] . "\n";
        $user_message .= "🆔 ID: `" . $solicitud['telegram_id'] . "`\n";
        $user_message .= "📅 Fecha: " . date('d/m/Y H:i:s') . "\n\n";
        $user_message .= "🔒 *Instrucciones:*\n";
        $user_message .= "1. Accede a la URL\n";
        $user_message .= "2. Ingresa tu ID de Telegram\n";
        $user_message .= "3. Usa la contraseña: `888team`\n\n";
        $user_message .= "¡Bienvenido al sistema!";
        
        sendMessage($user_id, $user_message);
        
        // Confirmar al admin
        $admin_message = "✅ *USUARIO " . strtoupper($accion) . "*\n\n";
        $admin_message .= "👤 *Nombre:* " . $solicitud['nombre'] . "\n";
        $admin_message .= "📛 *Username:* " . ($solicitud['username'] ? "@" . $solicitud['username'] : "Sin user") . "\n";
        $admin_message .= "🔢 *ID:* `" . $user_id . "`\n";
        $admin_message .= "📅 *Fecha:* " . date('d/m/Y H:i:s') . "\n";
        $admin_message .= "🔑 *Contraseña:* 888team\n\n";
        $admin_message .= "🌐 *URL sistema:* " . DOMINIO;
        
        sendMessage(ADMIN_CHAT_ID, $admin_message);
        
        // Log
        file_put_contents('registros_aprobados.log', date('Y-m-d H:i:s') . " | " . $accion . " | " . $solicitud['nombre'] . " | ID: " . $user_id . PHP_EOL, FILE_APPEND);
        
    } catch (Exception $e) {
        $error_msg = "❌ *ERROR AL APROBAR*\n\n";
        $error_msg .= "Usuario: " . ($solicitud['nombre'] ?? 'N/A') . "\n";
        $error_msg .= "ID: `" . $user_id . "`\n";
        $error_msg .= "Error: " . $e->getMessage();
        
        sendMessage(ADMIN_CHAT_ID, $error_msg);
        
        file_put_contents('bot_error.log', date('Y-m-d H:i:s') . " - Error aprobar: " . $e->getMessage() . " | User: " . $user_id . PHP_EOL, FILE_APPEND);
    }
}

function rechazarUsuario($user_id) {
    global $pdo;
    
    // Buscar solicitud pendiente
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro WHERE telegram_id = ? AND estado = 'pendiente'");
    $stmt->execute([$user_id]);
    $solicitud = $stmt->fetch();
    
    if (!$solicitud) {
        sendMessage(ADMIN_CHAT_ID, "❌ No se encontró solicitud pendiente para ID: `" . $user_id . "`");
        return;
    }
    
    try {
        // Actualizar solicitud
        $stmt = $pdo->prepare("UPDATE solicitudes_registro SET estado = 'rechazado', fecha_resolucion = NOW() 
                              WHERE telegram_id = ? AND estado = 'pendiente'");
        $stmt->execute([$user_id]);
        
        // Notificar al usuario
        $user_message = "❌ *SOLICITUD RECHAZADA*\n\n";
        $user_message .= "Tu solicitud para 888Wallet ha sido *RECHAZADA*.\n\n";
        $user_message .= "📋 *Motivo:* Revisión del administrador\n";
        $user_message .= "📅 *Fecha:* " . date('d/m/Y H:i:s') . "\n";
        $user_message .= "👤 *Nombre:* " . $solicitud['nombre'] . "\n";
        $user_message .= "🆔 *ID:* `" . $solicitud['telegram_id'] . "`\n\n";
        $user_message .= "ℹ️ *Información:*\n";
        $user_message .= "• El acceso al sistema es exclusivo\n";
        $user_message .= "• Todas las solicitudes son revisadas\n";
        $user_message .= "• No se proporcionan motivos específicos\n\n";
        $user_message .= "📞 *Contacto:* @Macrzz6";
        
        sendMessage($user_id, $user_message);
        
        // Confirmar al admin
        $admin_message = "❌ *SOLICITUD RECHAZADA*\n\n";
        $admin_message .= "👤 *Nombre:* " . $solicitud['nombre'] . "\n";
        $admin_message .= "📛 *Username:* " . ($solicitud['username'] ? "@" . $solicitud['username'] : "Sin user") . "\n";
        $admin_message .= "🔢 *ID:* `" . $user_id . "`\n";
        $admin_message .= "📅 *Fecha:* " . date('d/m/Y H:i:s');
        
        sendMessage(ADMIN_CHAT_ID, $admin_message);
        
        // Log
        file_put_contents('registros_rechazados.log', date('Y-m-d H:i:s') . " | Rechazado | " . $solicitud['nombre'] . " | ID: " . $user_id . PHP_EOL, FILE_APPEND);
        
    } catch (Exception $e) {
        $error_msg = "❌ *ERROR AL RECHAZAR*\n\n";
        $error_msg .= "Usuario: " . ($solicitud['nombre'] ?? 'N/A') . "\n";
        $error_msg .= "ID: `" . $user_id . "`\n";
        $error_msg .= "Error: " . $e->getMessage();
        
        sendMessage(ADMIN_CHAT_ID, $error_msg);
        
        file_put_contents('bot_error.log', date('Y-m-d H:i:s') . " - Error rechazar: " . $e->getMessage() . " | User: " . $user_id . PHP_EOL, FILE_APPEND);
    }
}

function listarSolicitudes() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM solicitudes_registro ORDER BY fecha_solicitud DESC LIMIT 15");
    $stmt->execute();
    $solicitudes = $stmt->fetchAll();
    
    if (empty($solicitudes)) {
        sendMessage(ADMIN_CHAT_ID, "📭 *No hay solicitudes registradas*");
        return;
    }
    
    $message = "📋 *ÚLTIMAS 15 SOLICITUDES*\n\n";
    
    $contador = 0;
    foreach ($solicitudes as $s) {
        $contador++;
        $estado_emoji = $s['estado'] == 'pendiente' ? "⏳" : ($s['estado'] == 'aprobado' ? "✅" : "❌");
        $username_display = $s['username'] ? "@" . $s['username'] : "Sin user";
        
        $message .= $estado_emoji . " *" . $s['nombre'] . "*\n";
        $message .= "   👤 User: " . $username_display . "\n";
        $message .= "   🔢 ID: `" . $s['telegram_id'] . "`\n";
        $message .= "   📅 Fecha: " . $s['fecha_solicitud'] . "\n";
        $message .= "   🔒 Estado: " . strtoupper($s['estado']) . "\n";
        
        if ($s['estado'] == 'pendiente') {
            $message .= "   ✅ `/aprobar_" . $s['telegram_id'] . "`\n";
            $message .= "   ❌ `/rechazar_" . $s['telegram_id'] . "`\n";
        }
        
        $message .= "   ───────────\n";
        
        // Telegram tiene límite de 4096 caracteres
        if (strlen($message) > 3500 && $contador < count($solicitudes)) {
            sendMessage(ADMIN_CHAT_ID, $message);
            $message = "📋 *CONTINUACIÓN...*\n\n";
            sleep(1);
        }
    }
    
    sendMessage(ADMIN_CHAT_ID, $message);
    
    // Enviar resumen
    sleep(1);
    $stmt = $pdo->prepare("SELECT estado, COUNT(*) as total FROM solicitudes_registro GROUP BY estado");
    $stmt->execute();
    $resumen = $stmt->fetchAll();
    
    $resumen_msg = "📊 *RESUMEN DE SOLICITUDES*\n\n";
    foreach ($resumen as $r) {
        $emoji = $r['estado'] == 'pendiente' ? "⏳" : ($r['estado'] == 'aprobado' ? "✅" : "❌");
        $resumen_msg .= $emoji . " " . strtoupper($r['estado']) . ": " . $r['total'] . "\n";
    }
    
    sendMessage(ADMIN_CHAT_ID, $resumen_msg);
}

function listarUsuarios() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios ORDER BY fecha_registro DESC LIMIT 15");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
    
    if (empty($usuarios)) {
        sendMessage(ADMIN_CHAT_ID, "👤 *No hay usuarios registrados*");
        return;
    }
    
    $message = "👥 *ÚLTIMOS 15 USUARIOS*\n\n";
    
    $contador = 0;
    foreach ($usuarios as $u) {
        $contador++;
        $username_display = $u['username'] ? "@" . $u['username'] : "Sin user";
        $activo = $u['activo'] == 1 ? "✅" : "❌";
        
        $message .= $activo . " *" . $u['nombre'] . "*\n";
        $message .= "   👤 User: " . $username_display . "\n";
        $message .= "   🔢 ID: `" . $u['telegram_id'] . "`\n";
        $message .= "   📅 Registro: " . $u['fecha_registro'] . "\n";
        $message .= "   🔒 Estado: " . ($u['activo'] == 1 ? "ACTIVO" : "INACTIVO") . "\n";
        
        if ($u['activo'] == 1) {
            $message .= "   ❌ `/desactivar_" . $u['telegram_id'] . "`\n";
        } else {
            $message .= "   ✅ `/activar_" . $u['telegram_id'] . "`\n";
        }
        
        $message .= "   ───────────\n";
        
        // Telegram tiene límite de 4096 caracteres
        if (strlen($message) > 3500 && $contador < count($usuarios)) {
            sendMessage(ADMIN_CHAT_ID, $message);
            $message = "👥 *CONTINUACIÓN...*\n\n";
            sleep(1);
        }
    }
    
    sendMessage(ADMIN_CHAT_ID, $message);
    
    // Enviar resumen
    sleep(1);
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(activo = 1) as activos,
        SUM(activo = 0) as inactivos
        FROM usuarios");
    $stmt->execute();
    $resumen = $stmt->fetch();
    
    $resumen_msg = "📊 *RESUMEN DE USUARIOS*\n\n";
    $resumen_msg .= "👥 Total: " . $resumen['total'] . "\n";
    $resumen_msg .= "✅ Activos: " . $resumen['activos'] . "\n";
    $resumen_msg .= "❌ Inactivos: " . $resumen['inactivos'];
    
    sendMessage(ADMIN_CHAT_ID, $resumen_msg);
}

function activarUsuario($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE telegram_id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Obtener datos del usuario
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telegram_id = ?");
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch();
            
            // Notificar al admin
            $msg = "✅ *USUARIO ACTIVADO*\n\n";
            $msg .= "👤 Nombre: " . $usuario['nombre'] . "\n";
            $msg .= "🔢 ID: `" . $user_id . "`\n";
            $msg .= "📅 Fecha: " . date('d/m/Y H:i:s');
            
            sendMessage(ADMIN_CHAT_ID, $msg);
            
            // Notificar al usuario
            $user_msg = "✅ *CUENTA REACTIVADA*\n\n";
            $user_msg .= "Tu cuenta en 888Wallet ha sido reactivada.\n\n";
            $user_msg .= "🌐 URL: " . DOMINIO . "\n";
            $user_msg .= "🔑 Contraseña: `888team`\n\n";
            $user_msg .= "¡Bienvenido de nuevo!";
            
            sendMessage($user_id, $user_msg);
        } else {
            sendMessage(ADMIN_CHAT_ID, "❌ Usuario no encontrado o ya activo");
        }
        
    } catch (Exception $e) {
        sendMessage(ADMIN_CHAT_ID, "❌ Error: " . $e->getMessage());
    }
}

function desactivarUsuario($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE telegram_id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Obtener datos del usuario
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE telegram_id = ?");
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch();
            
            // Notificar al admin
            $msg = "❌ *USUARIO DESACTIVADO*\n\n";
            $msg .= "👤 Nombre: " . $usuario['nombre'] . "\n";
            $msg .= "🔢 ID: `" . $user_id . "`\n";
            $msg .= "📅 Fecha: " . date('d/m/Y H:i:s');
            
            sendMessage(ADMIN_CHAT_ID, $msg);
            
            // Notificar al usuario
            $user_msg = "❌ *CUENTA DESACTIVADA*\n\n";
            $user_msg .= "Tu cuenta en 888Wallet ha sido desactivada.\n\n";
            $user_msg .= "📋 *Motivo:* Decisión del administrador\n";
            $user_msg .= "📅 *Fecha:* " . date('d/m/Y H:i:s') . "\n\n";
            $user_msg .= "Para más información, contacta al administrador.";
            
            sendMessage($user_id, $user_msg);
        } else {
            sendMessage(ADMIN_CHAT_ID, "❌ Usuario no encontrado o ya inactivo");
        }
        
    } catch (Exception $e) {
        sendMessage(ADMIN_CHAT_ID, "❌ Error: " . $e->getMessage());
    }
}

function broadcastMensaje($mensaje) {
    global $pdo;
    
    if (empty($mensaje)) {
        sendMessage(ADMIN_CHAT_ID, "❌ Debes incluir un mensaje después de /broadcast");
        return;
    }
    
    // Obtener todos los usuarios activos
    $stmt = $pdo->prepare("SELECT telegram_id, nombre FROM usuarios WHERE activo = 1");
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
    
    if (empty($usuarios)) {
        sendMessage(ADMIN_CHAT_ID, "❌ No hay usuarios activos para enviar broadcast");
        return;
    }
    
    $total = count($usuarios);
    $enviados = 0;
    $fallidos = 0;
    
    $admin_msg = "📢 *INICIANDO BROADCAST*\n\n";
    $admin_msg .= "📝 Mensaje: " . substr($mensaje, 0, 100) . "...\n";
    $admin_msg .= "👥 Destinatarios: " . $total . " usuarios\n\n";
    $admin_msg .= "⏳ Enviando...";
    
    sendMessage(ADMIN_CHAT_ID, $admin_msg);
    
    foreach ($usuarios as $usuario) {
        try {
            $msg = "📢 *MENSAJE DEL ADMINISTRADOR*\n\n";
            $msg .= $mensaje . "\n\n";
            $msg .= "──────────────\n";
            $msg .= "👤 Para: " . $usuario['nombre'] . "\n";
            $msg .= "📅 " . date('d/m/Y H:i:s');
            
            sendMessage($usuario['telegram_id'], $msg);
            $enviados++;
            
            // Pequeña pausa para no sobrecargar la API de Telegram
            if ($enviados % 10 == 0) {
                sleep(1);
                
                // Actualizar progreso al admin cada 10 mensajes
                $progreso = "📊 *Progreso:* " . $enviados . "/" . $total . " (" . round(($enviados/$total)*100) . "%)";
                sendMessage(ADMIN_CHAT_ID, $progreso);
            }
            
        } catch (Exception $e) {
            $fallidos++;
            file_put_contents('broadcast_errors.log', date('Y-m-d H:i:s') . " - Error usuario " . $usuario['telegram_id'] . ": " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
    
    // Resultado final
    $resultado = "✅ *BROADCAST COMPLETADO*\n\n";
    $resultado .= "📝 Mensaje enviado: " . substr($mensaje, 0, 50) . "...\n";
    $resultado .= "👥 Total usuarios: " . $total . "\n";
    $resultado .= "✅ Enviados: " . $enviados . "\n";
    $resultado .= "❌ Fallidos: " . $fallidos . "\n";
    $resultado .= "📅 Fecha: " . date('d/m/Y H:i:s');
    
    sendMessage(ADMIN_CHAT_ID, $resultado);
    
    // Log
    file_put_contents('broadcast.log', date('Y-m-d H:i:s') . " | Mensaje: " . substr($mensaje, 0, 100) . " | Total: " . $total . " | Enviados: " . $enviados . " | Fallidos: " . $fallidos . PHP_EOL, FILE_APPEND);
}

function mostrarEstadisticas() {
    global $pdo;
    
    try {
        // Estadísticas de solicitudes
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as total_solicitudes,
            SUM(estado = 'pendiente') as pendientes,
            SUM(estado = 'aprobado') as aprobadas,
            SUM(estado = 'rechazado') as rechazadas
            FROM solicitudes_registro");
        $stmt->execute();
        $stats_solicitudes = $stmt->fetch();
        
        // Estadísticas de usuarios
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as total_usuarios,
            SUM(activo = 1) as activos,
            SUM(activo = 0) as inactivos,
            DATE(fecha_registro) as fecha,
            COUNT(*) as registros_hoy
            FROM usuarios 
            WHERE DATE(fecha_registro) = CURDATE()");
        $stmt->execute();
        $stats_usuarios = $stmt->fetch();
        
        // Usuarios por día (últimos 7 días)
        $stmt = $pdo->prepare("SELECT 
            DATE(fecha_registro) as fecha,
            COUNT(*) as registros
            FROM usuarios 
            WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(fecha_registro)
            ORDER BY fecha DESC");
        $stmt->execute();
        $registros_7dias = $stmt->fetchAll();
        
        $message = "📈 *ESTADÍSTICAS DEL SISTEMA*\n\n";
        
        $message .= "📋 *SOLICITUDES*\n";
        $message .= "⏳ Pendientes: " . $stats_solicitudes['pendientes'] . "\n";
        $message .= "✅ Aprobadas: " . $stats_solicitudes['aprobadas'] . "\n";
        $message .= "❌ Rechazadas: " . $stats_solicitudes['rechazadas'] . "\n";
        $message .= "📊 Total: " . $stats_solicitudes['total_solicitudes'] . "\n\n";
        
        $message .= "👥 *USUARIOS*\n";
        $message .= "✅ Activos: " . $stats_usuarios['activos'] . "\n";
        $message .= "❌ Inactivos: " . $stats_usuarios['inactivos'] . "\n";
        $message .= "📊 Total: " . $stats_usuarios['total_usuarios'] . "\n";
        $message .= "📅 Registros hoy: " . $stats_usuarios['registros_hoy'] . "\n\n";
        
        $message .= "📅 *REGISTROS ÚLTIMOS 7 DÍAS*\n";
        foreach ($registros_7dias as $dia) {
            $message .= "• " . $dia['fecha'] . ": " . $dia['registros'] . " usuarios\n";
        }
        
        if (empty($registros_7dias)) {
            $message .= "No hay registros en los últimos 7 días\n";
        }
        
        $message .= "\n🔄 *ACTUALIZADO:* " . date('d/m/Y H:i:s');
        
        sendMessage(ADMIN_CHAT_ID, $message);
        
    } catch (Exception $e) {
        sendMessage(ADMIN_CHAT_ID, "❌ Error al obtener estadísticas: " . $e->getMessage());
    }
}

// ==================== FUNCIÓN AUXILIAR ====================

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
    $result = @file_get_contents($url, false, $context);
    
    // Log de respuestas (solo errores)
    if ($result === FALSE) {
        file_put_contents('telegram_errors.log', date('Y-m-d H:i:s') . " - Error enviando a $chat_id: " . error_get_last()['message'] . PHP_EOL, FILE_APPEND);
    }
    
    return $result;
}
?>