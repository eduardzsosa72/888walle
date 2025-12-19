<?php
session_start();
header("Content-Type: application/json");

// Configuración de la base de datos
$DB_HOST = 'gokucheker.ceheeiow0knm.us-east-1.rds.amazonaws.com';
$DB_NAME = '888wallet_db';
$DB_USER = 'admin';
$DB_PASS = 'gokucheker123';

// Configuración del Bot de Telegram
define('TELEGRAM_BOT_TOKEN', ''); // Reemplaza con tu token
define('TELEGRAM_ADMIN_ID', '6319087504');    // Reemplaza con tu chat ID

// Contraseña fija
$FIXED_PASSWORD = "888team";

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Error de conexión DB: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Error de conexión"
    ]);
    exit;
}

// Obtener datos
$input = json_decode(file_get_contents("php://input"), true);
$telegram_id = trim($input["telegram_id"] ?? "");
$password_input = trim($input["password"] ?? "");
$verification_code = trim($input["verification_code"] ?? "");

// =============================
// PASO 1: Verificar credenciales iniciales
// =============================
if (!empty($telegram_id) && !empty($password_input) && empty($verification_code)) {
    
    if ($password_input !== $FIXED_PASSWORD) {
        echo json_encode(["success" => false, "message" => "Contraseña incorrecta"]);
        exit;
    }

    // Verificar ID de Telegram en la base de datos
    try {
        $stmt = $pdo->prepare("SELECT id, telegram_id, nombre FROM usuarios WHERE telegram_id = ? AND activo = 1");
        $stmt->execute([$telegram_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(["success" => false, "message" => "Usuario no autorizado"]);
            exit;
        }

        // Generar código de verificación (6 dígitos)
        $code = rand(100000, 999999);
        $expires = time() + 300; // 5 minutos de validez
        
        // Guardar código en sesión temporal
        $_SESSION['verification_temp'] = [
            'user_id' => $user['id'],
            'telegram_id' => $user['telegram_id'],
            'nombre' => $user['nombre'],
            'code' => $code,
            'expires' => $expires
        ];
        
        // Guardar también en base de datos por seguridad
        $stmt = $pdo->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE code = ?, expires_at = ?");
        $stmt->execute([$user['id'], $code, date('Y-m-d H:i:s', $expires), $code, date('Y-m-d H:i:s', $expires)]);
        
        // Enviar código por Telegram
        $telegram_message = "🔐 *Código de Verificación 888Wallet*\n\n";
        $telegram_message .= "🆔 Usuario: " . $user['nombre'] . "\n";
        $telegram_message .= "📋 Telegram ID: `" . $user['telegram_id'] . "`\n";
        $telegram_message .= "🕐 Fecha: " . date('d/m/Y H:i:s') . "\n";
        $telegram_message .= "🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
        $telegram_message .= "➡️ *CÓDIGO DE VERIFICACIÓN:* \n`" . $code . "`\n\n";
        $telegram_message .= "_Este código expira en 5 minutos_";
        
        // Enviar mensaje a Telegram
        sendTelegramMessage(TELEGRAM_ADMIN_ID, $telegram_message);
        
        echo json_encode([
            "success" => true,
            "message" => "Código de verificación enviado a Telegram",
            "step" => "verification",
            "telegram_id" => $telegram_id
        ]);

    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error del sistema"]);
    }
    exit;
}

// =============================
// PASO 2: Verificar código
// =============================
if (!empty($verification_code) && isset($_SESSION['verification_temp'])) {
    
    $temp_data = $_SESSION['verification_temp'];
    
    // Verificar expiración
    if (time() > $temp_data['expires']) {
        unset($_SESSION['verification_temp']);
        echo json_encode(["success" => false, "message" => "Código expirado"]);
        exit;
    }
    
    // Verificar código en base de datos
    try {
        $stmt = $pdo->prepare("SELECT * FROM verification_codes WHERE user_id = ? AND code = ? AND expires_at > NOW()");
        $stmt->execute([$temp_data['user_id'], $verification_code]);
        $code_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$code_data) {
            echo json_encode(["success" => false, "message" => "Código incorrecto"]);
            exit;
        }
        
        // Login exitoso
        $_SESSION["auth"] = true;
        $_SESSION["user_id"] = $temp_data['user_id'];
        $_SESSION["telegram_id"] = $temp_data['telegram_id'];
        $_SESSION["user_name"] = $temp_data['nombre'];
        
        // Limpiar datos temporales
        unset($_SESSION['verification_temp']);
        
        // Eliminar código usado
        $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE user_id = ?");
        $stmt->execute([$temp_data['user_id']]);
        
        // Enviar notificación de acceso exitoso
        $telegram_message = "✅ *Acceso Autorizado 888Wallet*\n\n";
        $telegram_message .= "👤 Usuario: " . $temp_data['nombre'] . "\n";
        $telegram_message .= "🆔 Telegram ID: `" . $temp_data['telegram_id'] . "`\n";
        $telegram_message .= "📅 Fecha: " . date('d/m/Y H:i:s') . "\n";
        $telegram_message .= "🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $telegram_message .= "🔒 Estado: ACCESO PERMITIDO";
        
        sendTelegramMessage(TELEGRAM_ADMIN_ID, $telegram_message);
        
        echo json_encode([
            "success" => true,
            "message" => "Acceso autorizado",
            "user" => [
                "id" => $temp_data['user_id'],
                "telegram_id" => $temp_data['telegram_id'],
                "nombre" => $temp_data['nombre']
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Error en verificación: " . e->getMessage());
        echo json_encode(["success" => false, "message" => "Error en verificación"]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Datos incompletos"]);

// =============================
// FUNCIÓN PARA ENVIAR A TELEGRAM
// =============================
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
}
?>