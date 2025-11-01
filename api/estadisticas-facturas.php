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
    // Obtener estadísticas
    $hoy = date('Y-m-d');
    $inicioMes = date('Y-m-01');
    
    // Total de facturas
    $stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM facturas WHERE estado = 'Completada'");
    $stmtTotal->execute();
    $totalFacturas = $stmtTotal->fetchColumn();
    
    // Total en ventas
    $stmtVentas = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total FROM facturas WHERE estado = 'Completada'");
    $stmtVentas->execute();
    $totalVentas = $stmtVentas->fetchColumn();
    
    // Facturas de hoy
    $stmtHoy = $conn->prepare("SELECT COUNT(*) as total FROM facturas WHERE DATE(fecha_factura) = ? AND estado = 'Completada'");
    $stmtHoy->execute([$hoy]);
    $facturasHoy = $stmtHoy->fetchColumn();
    
    // Facturas del mes
    $stmtMes = $conn->prepare("SELECT COUNT(*) as total FROM facturas WHERE DATE(fecha_factura) >= ? AND estado = 'Completada'");
    $stmtMes->execute([$inicioMes]);
    $facturasMes = $stmtMes->fetchColumn();
    
    sendResponse(true, [
        'total_facturas' => intval($totalFacturas),
        'total_ventas' => floatval($totalVentas),
        'facturas_hoy' => intval($facturasHoy),
        'facturas_mes' => intval($facturasMes)
    ], 'Estadísticas obtenidas correctamente');
    
} catch (PDOException $e) {
    error_log("Error en estadisticas-facturas.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>