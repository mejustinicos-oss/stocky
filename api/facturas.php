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
            // Obtener facturas con filtros y paginación
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $offset = ($page - 1) * $limit;
            
            // Construir consulta base con filtros
            $whereConditions = ["f.estado = 'Completada'"];
            $params = [];
            
            // Filtro por búsqueda
            if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
                $busqueda = '%' . $_GET['busqueda'] . '%';
                $whereConditions[] = "(f.numero_factura LIKE ? OR c.nombre LIKE ? OR c.cedula LIKE ?)";
                $params[] = $busqueda;
                $params[] = $busqueda;
                $params[] = $busqueda;
            }
            
            // Filtro por fecha desde
            if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
                $whereConditions[] = "DATE(f.fecha_factura) >= ?";
                $params[] = $_GET['fecha_desde'];
            }
            
            // Filtro por fecha hasta
            if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
                $whereConditions[] = "DATE(f.fecha_factura) <= ?";
                $params[] = $_GET['fecha_hasta'];
            }
            
            $whereClause = "";
            if (!empty($whereConditions)) {
                $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            }
            
            // Consulta para obtener facturas
            $sql = "
                SELECT 
                    f.numero_factura,
                    f.fecha_factura,
                    f.subtotal,
                    f.iva,
                    f.total,
                    f.estado,
                    c.cedula as cliente_id,
                    c.nombre as cliente_nombre,
                    u.cedula as vendedor_id,
                    u.nombre as vendedor_nombre,
                    mp.nombre as metodo_pago
                FROM facturas f
                INNER JOIN clientes c ON f.cedula_cliente = c.cedula
                INNER JOIN usuarios u ON f.cedula_vendedor = u.cedula
                INNER JOIN metodos_pago mp ON f.metodo_pago_id = mp.id
                $whereClause
                ORDER BY f.fecha_factura DESC
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $facturas = $stmt->fetchAll();
            
            // Consulta para el total de registros (para paginación)
            $countSql = "
                SELECT COUNT(*) as total
                FROM facturas f
                INNER JOIN clientes c ON f.cedula_cliente = c.cedula
                $whereClause
            ";
            
            $countStmt = $conn->prepare($countSql);
            $countParams = array_slice($params, 0, count($params) - 2); // Remover limit y offset
            $countStmt->execute($countParams);
            $totalRegistros = $countStmt->fetchColumn();
            
            // Formatear respuesta
            $facturasFormateadas = array_map(function($factura) {
                return [
                    'numero' => $factura['numero_factura'],
                    'fecha' => $factura['fecha_factura'],
                    'cliente' => [
                        'id' => $factura['cliente_id'],
                        'nombre' => $factura['cliente_nombre']
                    ],
                    'vendedor' => [
                        'documento' => $factura['vendedor_id'],
                        'nombre' => $factura['vendedor_nombre']
                    ],
                    'metodoPago' => $factura['metodo_pago'],
                    'subtotal' => floatval($factura['subtotal']),
                    'iva' => floatval($factura['iva']),
                    'total' => floatval($factura['total']),
                    'estado' => $factura['estado']
                ];
            }, $facturas);
            
            sendResponse(true, [
                'facturas' => $facturasFormateadas,
                'paginacion' => [
                    'pagina_actual' => $page,
                    'total_paginas' => ceil($totalRegistros / $limit),
                    'total_registros' => $totalRegistros,
                    'limite' => $limit
                ]
            ], 'Facturas obtenidas correctamente');
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en facturas.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>