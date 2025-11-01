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
    // Total de clientes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clientes");
    $stmt->execute();
    $totalClientes = $stmt->fetchColumn();

    // Clientes activos (que tienen al menos una factura)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT cedula_cliente) as activos 
        FROM facturas 
        WHERE estado = 'Completada'
    ");
    $stmt->execute();
    $clientesActivos = $stmt->fetchColumn();

    // Total en compras (suma de todas las facturas)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as total 
        FROM facturas 
        WHERE estado = 'Completada'
    ");
    $stmt->execute();
    $totalCompras = $stmt->fetchColumn();

    sendResponse(true, [
        'totalClientes' => intval($totalClientes),
        'clientesActivos' => intval($clientesActivos),
        'totalCompras' => floatval($totalCompras)
    ], 'Estadísticas obtenidas correctamente');

} catch (PDOException $e) {
    error_log("Error en estadisticas-clientes.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>