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


// Configuración de la base de datos
// Configuración de la base de datos con valores por defecto
define('DB_HOST', getenv('DB_HOST') ?: 'database-maicol');
define('DB_NAME', getenv('DB_NAME') ?: 'default');
define('DB_USER', getenv('DB_USER') ?: 'mysql');
define('DB_PASS', getenv('DB_PASS') ?: 'jjoWDxth4tPDbTprsecJ5WjC4HBycyaQGafcsUuI7djDWPOZT9w85sRWB04dHA9Z');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
// Crear conexión
function getConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error de conexión: ' . $e->getMessage()
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
