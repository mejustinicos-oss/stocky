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

try {
    // Obtener categorías
    $stmt = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();

    // Obtener presentaciones
    $stmt = $conn->query("SELECT id, nombre FROM presentaciones ORDER BY nombre");
    $presentaciones = $stmt->fetchAll();

    sendResponse(true, [
        'categorias' => $categorias,
        'presentaciones' => $presentaciones
    ], 'Opciones obtenidas correctamente');

} catch (PDOException $e) {
    error_log("Error en opciones-productos: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>