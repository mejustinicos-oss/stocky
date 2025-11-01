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
    if (!isset($_GET['cedula'])) {
        sendResponse(false, null, 'Cédula es requerida');
    }

    $cedula = $_GET['cedula'];

    $stmt = $conn->prepare("
        SELECT 
            f.numero_factura,
            f.fecha_factura as fecha,
            f.total as monto
        FROM facturas f
        WHERE f.cedula_cliente = ? AND f.estado = 'Completada'
        ORDER BY f.fecha_factura DESC
    ");
    $stmt->execute([$cedula]);
    $compras = $stmt->fetchAll();

    sendResponse(true, $compras, 'Historial de compras obtenido correctamente');

} catch (PDOException $e) {
    error_log("Error en historial-compras.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>