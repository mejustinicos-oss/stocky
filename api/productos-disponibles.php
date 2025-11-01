<?php
// archivo: productos-disponibles.php
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$conn = getConnection();

try {
    // Obtener productos activos
    $stmt = $conn->prepare("
        SELECT codigo_producto, nombre 
        FROM productos 
        WHERE eliminado = FALSE AND estado = 'Activo'
        ORDER BY nombre
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll();
    
    sendResponse(true, $productos, 'Productos obtenidos correctamente');

} catch (PDOException $e) {
    error_log("Error en productos-disponibles.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>  