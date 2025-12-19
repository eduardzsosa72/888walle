<?php
session_start();
header("Content-Type: application/json");

// Configuración de la base de datos
$DB_HOST = 'gokucheker.ceheeiow0knm.us-east-1.rds.amazonaws.com';
$DB_NAME = '888wallet_db';
$DB_USER = 'admin';
$DB_PASS = 'gokucheker123';

// Configuración del Bot de Telegram
define('TELEGRAM_BOT_TOKEN', '8454388731:AAF8GHffHrsaSB8uAy8WEZLhsHcPptAIDFk'); // Reemplaza con tu token
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
        $stmt = $pdo->prepare("SELECT id, telegram_id, nombre, username FROM usuarios WHERE telegram_id = ? AND activo = 1");
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
        // Primero crea la tabla si no existe
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS verification_codes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                code VARCHAR(10) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user (user_id)
            )");
        } catch (Exception $e) {
            // La tabla ya existe o no podemos crearla, continuamos
        }
        
        $stmt = $pdo->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE code = ?, expires_at = ?");
        $stmt->execute([$user['id'], $code, date('Y-m-d H:i:s', $expires), $code, date('Y-m-d H:i:s', $expires)]);
        
        // Enviar código por Telegram AL USUARIO
        $telegram_message = "🔐 *Código de Verificación 888Wallet*\n\n";
        $telegram_message .= "👤 Usuario: " . $user['nombre'] . "\n";
        $telegram_message .= "🆔 Tu ID: `" . $user['telegram_id'] . "`\n";
        $telegram_message .= "🕐 Fecha: " . date('d/m/Y H:i:s') . "\n";
        $telegram_message .= "🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
        $telegram_message .= "➡️ *TU CÓDIGO DE VERIFICACIÓN:* \n`" . $code . "`\n\n";
        $telegram_message .= "📝 *Instrucciones:*\n";
        $telegram_message .= "1. Ingresa este código en la página web\n";
        $telegram_message .= "2. Tienes 5 minutos para usarlo\n";
        $telegram_message .= "3. No compartas este código con nadie\n\n";
        $telegram_message .= "_⚠️ Si no fuiste tú, contacta al administrador inmediatamente._";
        
        // Enviar mensaje al USUARIO
        $user_sent = sendTelegramMessage($user['telegram_id'], $telegram_message);
        
        // También notificar al ADMIN
        $admin_message = "🔐 *NUEVA SOLICITUD DE VERIFICACIÓN*\n\n";
        $admin_message .= "👤 Usuario: " . $user['nombre'] . "\n";
        $admin_message .= "📛 Username: " . ($user['username'] ? "@" . $user['username'] : "No tiene") . "\n";
        $admin_message .= "🆔 Telegram ID: `" . $user['telegram_id'] . "`\n";
        $admin_message .= "🕐 Fecha: " . date('d/m/Y H:i:s') . "\n";
        $admin_message .= "🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $admin_message .= "🔢 Código: `" . $code . "`\n";
        $admin_message .= "📤 Enviado al usuario: " . ($user_sent ? "✅" : "❌");
        
        sendTelegramMessage(TELEGRAM_ADMIN_ID, $admin_message);
        
        echo json_encode([
            "success" => true,
            "message" => "Código de verificación enviado a tu Telegram",
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
        
        // Enviar notificación de acceso exitoso AL USUARIO
        $telegram_message = "✅ *Acceso Autorizado 888Wallet*\n\n";
        $telegram_message .= "👤 Bienvenido: " . $temp_data['nombre'] . "\n";
        $telegram_message .= "🆔 Tu ID: `" . $temp_data['telegram_id'] . "`\n";
        $telegram_message .= "📅 Fecha: " . date('d/m/Y H:i:s') . "\n";
        $telegram_message .= "🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $telegram_message .= "🔒 Estado: ACCESO PERMITIDO\n\n";
        $telegram_message .= "🎉 ¡Acceso exitoso al sistema!";
        
        sendTelegramMessage($temp_data['telegram_id'], $telegram_message);
        
        // También notificar al ADMIN
        $admin_message = "✅ *ACCESO AUTORIZADO 888Wallet*\n\n";
        $admin_message .= "👤 Usuario: " . $temp_data['nombre'] . "\n";
        $admin_message .= "🆔 Telegram ID: `" . $temp_data['telegram_id'] . "`\n";
        $admin_message .= "📅 Fecha: " . date('d/m/Y H:i:s') . "\n";
        $admin_message .= "🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $admin_message .= "🔒 Estado: ACCESO CONCEDIDO";
        
        sendTelegramMessage(TELEGRAM_ADMIN_ID, $admin_message);
        
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
        error_log("Error en verificación: " . $e->getMessage());
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
    $result = @file_get_contents($url, false, $context);
    
    return $result !== false;
}
?>