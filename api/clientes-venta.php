<?php
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$conn = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Buscar cliente por cédula
            $cedula = $_GET['cedula'] ?? '';
            
            if (empty($cedula)) {
                sendResponse(false, null, 'Cédula es requerida');
            }
            
            $stmt = $conn->prepare("
                SELECT cedula, nombre, telefono, email, direccion 
                FROM clientes 
                WHERE cedula = ?
            ");
            $stmt->execute([$cedula]);
            $cliente = $stmt->fetch();
            
            if ($cliente) {
                sendResponse(true, $cliente, 'Cliente encontrado');
            } else {
                sendResponse(false, null, 'Cliente no encontrado');
            }
            break;

        case 'POST':
            // Crear nuevo cliente
            $data = getRequestData();
            
            $cedula = $data['cedula'] ?? '';
            $nombre = $data['nombre'] ?? '';
            $telefono = $data['telefono'] ?? '';
            $email = $data['email'] ?? '';
            $direccion = $data['direccion'] ?? '';
            
            if (empty($cedula) || empty($nombre)) {
                sendResponse(false, null, 'Cédula y nombre son obligatorios');
            }
            
            // Verificar si el cliente ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE cedula = ?");
            $stmt->execute([$cedula]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe un cliente con esta cédula');
            }
            
            // Insertar cliente
            $stmt = $conn->prepare("
                INSERT INTO clientes (cedula, nombre, telefono, email, direccion)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$cedula, $nombre, $telefono, $email, $direccion]);
            
            sendResponse(true, ['cedula' => $cedula], 'Cliente creado exitosamente');
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en clientes-venta: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>