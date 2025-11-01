<?php
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $conn = getConnection();
    
    // =====================================================
    // ESTADÍSTICAS PRINCIPALES
    // =====================================================
    
    // 1. Ventas de hoy
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as ventas_hoy 
        FROM facturas 
        WHERE estado = 'Completada' 
        AND DATE(fecha_factura) = CURDATE()
    ");
    $stmt->execute();
    $ventasHoy = $stmt->fetchColumn();

    // 2. Ventas del mes actual
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as ventas_mes 
        FROM facturas 
        WHERE estado = 'Completada' 
        AND YEAR(fecha_factura) = YEAR(CURDATE()) 
        AND MONTH(fecha_factura) = MONTH(CURDATE())
    ");
    $stmt->execute();
    $ventasMes = $stmt->fetchColumn();

    // 3. Producto más vendido (usando la vista)
    $stmt = $conn->prepare("
        SELECT codigo_producto, nombre, total_vendido 
        FROM vista_productos_mas_vendidos 
        ORDER BY total_vendido DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $productoMasVendido = $stmt->fetch();
    
    if (!$productoMasVendido) {
        $productoMasVendido = [
            'nombre' => 'No hay ventas registradas',
            'total_vendido' => 0
        ];
    }

    // 4. Total de productos activos
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_productos 
        FROM productos 
        WHERE eliminado = FALSE AND estado = 'Activo'
    ");
    $stmt->execute();
    $totalProductos = $stmt->fetchColumn();

    // 5. Total de clientes
    $stmt = $conn->prepare("SELECT COUNT(*) as total_clientes FROM clientes");
    $stmt->execute();
    $totalClientes = $stmt->fetchColumn();

    // 6. Productos con stock bajo (para "pedidos pendientes")
    $stmt = $conn->prepare("
        SELECT COUNT(*) as productos_bajo_stock 
        FROM productos 
        WHERE cantidad <= stock_minimo 
        AND eliminado = FALSE 
        AND estado = 'Activo'
    ");
    $stmt->execute();
    $productosBajoStock = $stmt->fetchColumn();

    // 7. Ventas del día anterior (para comparación)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as ventas_ayer 
        FROM facturas 
        WHERE estado = 'Completada' 
        AND DATE(fecha_factura) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ");
    $stmt->execute();
    $ventasAyer = $stmt->fetchColumn();

    // 8. Ventas del mes anterior (para comparación)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total), 0) as ventas_mes_anterior 
        FROM facturas 
        WHERE estado = 'Completada' 
        AND YEAR(fecha_factura) = YEAR(CURDATE() - INTERVAL 1 MONTH)
        AND MONTH(fecha_factura) = MONTH(CURDATE() - INTERVAL 1 MONTH)
    ");
    $stmt->execute();
    $ventasMesAnterior = $stmt->fetchColumn();

    // =====================================================
    // CÁLCULO DE TENDENCIAS
    // =====================================================
    
    $tendenciaHoy = 0;
    if ($ventasAyer > 0) {
        $tendenciaHoy = (($ventasHoy - $ventasAyer) / $ventasAyer) * 100;
    }

    $tendenciaMes = 0;
    if ($ventasMesAnterior > 0) {
        $tendenciaMes = (($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100;
    }

    // =====================================================
    // RESPUESTA FINAL
    // =====================================================
    
    $estadisticas = [
        'ventasHoy' => (float) $ventasHoy,
        'ventasMes' => (float) $ventasMes,
        'productoMasVendido' => $productoMasVendido['nombre'],
        'ventasProducto' => (int) $productoMasVendido['total_vendido'],
        'totalProductos' => (int) $totalProductos,
        'totalClientes' => (int) $totalClientes,
        'productosBajoStock' => (int) $productosBajoStock,
        'tendencias' => [
            'hoy' => round($tendenciaHoy, 1),
            'mes' => round($tendenciaMes, 1)
        ]
    ];

    sendResponse(true, $estadisticas, 'Estadísticas obtenidas correctamente');

} catch (PDOException $e) {
    error_log("Error en estadísticas: " . $e->getMessage());
    sendResponse(false, null, 'Error al obtener estadísticas: ' . $e->getMessage());
}
?>