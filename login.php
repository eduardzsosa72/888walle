<?php
session_start();
header("Content-Type: application/json");

// Configuración CORREGIDA
$DB_HOST = 'gokucheker.ceheeiow0knm.us-east-1.rds.amazonaws.com';
$DB_NAME = '888wallet_db';
$DB_USER = 'admin';  // <-- COMILAS SIMPLES CORRECTAS
$DB_PASS = 'gokucheker123';

// Contraseña fija para todos
$FIXED_PASSWORD = "888team";

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Opcional: Verificar que la conexión funcione
    // $pdo->query("SELECT 1");
    
} catch (PDOException $e) {
    // Para debugging, muestra el error real
    error_log("Error de conexión DB: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Error de conexión a la base de datos",
        "debug" => "Host: $DB_HOST, DB: $DB_NAME"  // Solo para debugging, quitar en producción
    ]);
    exit;
}

// Obtener datos
$input = json_decode(file_get_contents("php://input"), true);
$telegram_id = trim($input["telegram_id"] ?? "");
$password_input = trim($input["password"] ?? "");

// Validar datos
if (empty($telegram_id) || empty($password_input)) {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit;
}

// Verificar contraseña fija
if ($password_input !== $FIXED_PASSWORD) {
    echo json_encode(["success" => false, "message" => "Acceso denegado"]);
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

    // Login exitoso
    $_SESSION["auth"] = true;
    $_SESSION["user_id"] = $user['id'];
    $_SESSION["telegram_id"] = $user['telegram_id'];
    
    echo json_encode([
        "success" => true,
        "message" => "Acceso autorizado",
        "user" => [
            "id" => $user['id'],
            "telegram_id" => $user['telegram_id'],
            "nombre" => $user['nombre'] ?? $user['telegram_id']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error en consulta: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error del sistema"]);
}
?>