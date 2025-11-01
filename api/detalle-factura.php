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
            if (!isset($_GET['numero_factura'])) {
                sendResponse(false, null, 'Número de factura es requerido');
            }
            
            $numeroFactura = $_GET['numero_factura'];
            
            // Obtener información general de la factura
            $sqlFactura = "
                SELECT 
                    f.numero_factura,
                    f.fecha_factura,
                    f.subtotal,
                    f.iva,
                    f.total,
                    f.estado,
                    c.cedula as cliente_id,
                    c.nombre as cliente_nombre,
                    c.telefono as cliente_telefono,
                    c.email as cliente_email,
                    c.direccion as cliente_direccion,
                    u.cedula as vendedor_id,
                    u.nombre as vendedor_nombre,
                    u.telefono as vendedor_telefono,
                    u.email as vendedor_email,
                    mp.nombre as metodo_pago
                FROM facturas f
                INNER JOIN clientes c ON f.cedula_cliente = c.cedula
                INNER JOIN usuarios u ON f.cedula_vendedor = u.cedula
                INNER JOIN metodos_pago mp ON f.metodo_pago_id = mp.id
                WHERE f.numero_factura = ?
            ";
            
            $stmt = $conn->prepare($sqlFactura);
            $stmt->execute([$numeroFactura]);
            $factura = $stmt->fetch();
            
            if (!$factura) {
                sendResponse(false, null, 'Factura no encontrada');
            }
            
            // Obtener detalle de productos
            $sqlDetalle = "
                SELECT 
                    df.codigo_producto,
                    df.nombre_producto,
                    df.cantidad,
                    df.precio_unitario,
                    df.subtotal as total_producto,
                    p.presentacion_id,
                    pres.nombre as presentacion
                FROM detalle_factura df
                LEFT JOIN productos p ON df.codigo_producto = p.codigo_producto
                LEFT JOIN presentaciones pres ON p.presentacion_id = pres.id
                WHERE df.numero_factura = ?
            ";
            
            $stmtDetalle = $conn->prepare($sqlDetalle);
            $stmtDetalle->execute([$numeroFactura]);
            $detalles = $stmtDetalle->fetchAll();
            
            // Formatear respuesta
            $facturaCompleta = [
                'numero' => $factura['numero_factura'],
                'fecha' => $factura['fecha_factura'],
                'cliente' => [
                    'id' => $factura['cliente_id'],
                    'nombre' => $factura['cliente_nombre'],
                    'telefono' => $factura['cliente_telefono'],
                    'email' => $factura['cliente_email'],
                    'direccion' => $factura['cliente_direccion']
                ],
                'vendedor' => [
                    'documento' => $factura['vendedor_id'],
                    'nombre' => $factura['vendedor_nombre'],
                    'telefono' => $factura['vendedor_telefono'],
                    'email' => $factura['vendedor_email']
                ],
                'metodoPago' => $factura['metodo_pago'],
                'productos' => array_map(function($detalle) {
                    return [
                        'codigo' => $detalle['codigo_producto'],
                        'nombre' => $detalle['nombre_producto'],
                        'cantidad' => intval($detalle['cantidad']),
                        'precio' => floatval($detalle['precio_unitario']),
                        'total' => floatval($detalle['total_producto']),
                        'presentacion' => $detalle['presentacion']
                    ];
                }, $detalles),
                'subtotal' => floatval($factura['subtotal']),
                'iva' => floatval($factura['iva']),
                'total' => floatval($factura['total']),
                'estado' => $factura['estado']
            ];
            
            sendResponse(true, $facturaCompleta, 'Detalle de factura obtenido correctamente');
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en detalle-factura.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>