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
    // Obtener productos activos con información de categoría
    $stmt = $conn->prepare("
        SELECT 
            p.codigo_producto,
            p.nombre,
            p.precio_venta,
            p.cantidad,
            c.nombre as categoria_nombre,
            c.id as categoria_id
        FROM productos p
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE p.eliminado = FALSE AND p.estado = 'Activo' AND p.cantidad > 0
        ORDER BY c.nombre, p.nombre
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll();

    // Agrupar productos por categoría
    $productosPorCategoria = [];
    foreach ($productos as $producto) {
        $categoria = $producto['categoria_nombre'];
        if (!isset($productosPorCategoria[$categoria])) {
            $productosPorCategoria[$categoria] = [];
        }
        
        $productosPorCategoria[$categoria][] = [
            'id' => $producto['codigo_producto'],
            'nombre' => $producto['nombre'],
            'precio' => floatval($producto['precio_venta']),
            'cantidad_stock' => intval($producto['cantidad']),
            'categoria_id' => $producto['categoria_id']
        ];
    }

    sendResponse(true, $productosPorCategoria, 'Productos obtenidos correctamente');

} catch (PDOException $e) {
    error_log("Error en productos-venta: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>