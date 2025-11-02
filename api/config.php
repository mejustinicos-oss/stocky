<?php
// Permitir peticiones desde cualquier origen (para desarrollo local)
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Si es una petición OPTIONS, terminar aquí
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuración de la base de datos desde variables de entorno
define('DB_HOST', 'database-maicol');
define('DB_NAME', 'databasestocky');
define('DB_USER', 'admin');
define('DB_PASS', '3215556611xd');
define('DB_PORT', '3306');

// Crear conexión
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $conn = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5 // Timeout de 5 segundos
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        // Log más detallado para debugging
        error_log("Error de conexión DB: " . $e->getMessage());
        error_log("Host: " . DB_HOST . ", Port: " . DB_PORT . ", DB: " . DB_NAME . ", User: " . DB_USER);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de conexión: ' . $e->getMessage(),
            'debug' => [
                'host' => DB_HOST,
                'port' => DB_PORT,
                'database' => DB_NAME,
                'user' => DB_USER
            ]
        ]);
        exit();
    }
}

// Función para responder con JSON
function sendResponse($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit();
}

// Función para obtener datos del body
function getRequestData() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return [];
    }
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
    return $data;
}
?>