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
            // Obtener todos los proveedores con sus productos
            if (isset($_GET['nit'])) {
                // Obtener un proveedor específico
                $stmt = $conn->prepare("
                    SELECT p.*, 
                           GROUP_CONCAT(pp.codigo_producto) as productos_ids
                    FROM proveedores p
                    LEFT JOIN proveedor_producto pp ON p.nit = pp.nit_proveedor
                    WHERE p.nit = ?
                    GROUP BY p.nit
                ");
                $stmt->execute([$_GET['nit']]);
                $proveedor = $stmt->fetch();
                
                if ($proveedor) {
                    // Convertir productos_ids a array
                    $proveedor['productos'] = $proveedor['productos_ids'] ? 
                        explode(',', $proveedor['productos_ids']) : [];
                    unset($proveedor['productos_ids']);
                    
                    sendResponse(true, $proveedor, 'Proveedor obtenido correctamente');
                } else {
                    sendResponse(false, null, 'Proveedor no encontrado');
                }
            } else {
                // Obtener todos los proveedores
                $stmt = $conn->prepare("
                    SELECT p.*, 
                           COUNT(pp.codigo_producto) as total_productos
                    FROM proveedores p
                    LEFT JOIN proveedor_producto pp ON p.nit = pp.nit_proveedor
                    GROUP BY p.nit
                    ORDER BY p.nombre_empresa
                ");
                $stmt->execute();
                $proveedores = $stmt->fetchAll();
                
                sendResponse(true, $proveedores, 'Proveedores obtenidos correctamente');
            }
            break;

        case 'POST':
            // Crear nuevo proveedor
            $data = getRequestData();
            
            $nit = $data['nit'] ?? '';
            $nombre_empresa = $data['nombre_empresa'] ?? '';
            $telefono = $data['telefono'] ?? '';
            $productos = $data['productos'] ?? [];
            
            // Validaciones
            if (empty($nit) || empty($nombre_empresa) || empty($telefono)) {
                sendResponse(false, null, 'Todos los campos obligatorios deben ser llenados');
            }
            
            // Verificar si el NIT ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM proveedores WHERE nit = ?");
            $stmt->execute([$nit]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe un proveedor con este NIT');
            }
            
            // Insertar proveedor
            $stmt = $conn->prepare("
                INSERT INTO proveedores (nit, nombre_empresa, telefono)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$nit, $nombre_empresa, $telefono]);
            
            // Insertar productos del proveedor
            if (!empty($productos)) {
                $stmt = $conn->prepare("
                    INSERT INTO proveedor_producto (nit_proveedor, codigo_producto)
                    VALUES (?, ?)
                ");
                foreach ($productos as $producto_id) {
                    $stmt->execute([$nit, $producto_id]);
                }
            }
            
            sendResponse(true, null, 'Proveedor creado exitosamente');
            break;

        case 'PUT':
            // Actualizar proveedor
            $data = getRequestData();
            
            $nit = $data['nit'] ?? '';
            $nombre_empresa = $data['nombre_empresa'] ?? '';
            $telefono = $data['telefono'] ?? '';
            $productos = $data['productos'] ?? [];
            
            if (empty($nit) || empty($nombre_empresa) || empty($telefono)) {
                sendResponse(false, null, 'Todos los campos obligatorios deben ser llenados');
            }
            
            // Verificar que el proveedor existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM proveedores WHERE nit = ?");
            $stmt->execute([$nit]);
            if ($stmt->fetchColumn() === 0) {
                sendResponse(false, null, 'Proveedor no encontrado');
            }
            
            // Actualizar proveedor
            $stmt = $conn->prepare("
                UPDATE proveedores 
                SET nombre_empresa = ?, telefono = ?
                WHERE nit = ?
            ");
            $stmt->execute([$nombre_empresa, $telefono, $nit]);
            
            // Actualizar productos: eliminar existentes y agregar nuevos
            $conn->beginTransaction();
            
            try {
                // Eliminar productos existentes
                $stmt = $conn->prepare("DELETE FROM proveedor_producto WHERE nit_proveedor = ?");
                $stmt->execute([$nit]);
                
                // Insertar nuevos productos
                if (!empty($productos)) {
                    $stmt = $conn->prepare("
                        INSERT INTO proveedor_producto (nit_proveedor, codigo_producto)
                        VALUES (?, ?)
                    ");
                    foreach ($productos as $producto_id) {
                        $stmt->execute([$nit, $producto_id]);
                    }
                }
                
                $conn->commit();
                sendResponse(true, null, 'Proveedor actualizado exitosamente');
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'DELETE':
            // Eliminar proveedor
            $data = getRequestData();
            $nit = $data['nit'] ?? '';
            
            if (empty($nit)) {
                sendResponse(false, null, 'NIT de proveedor es requerido');
            }
            
            // Verificar si hay productos asociados a este proveedor
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM productos p
                INNER JOIN proveedor_producto pp ON p.codigo_producto = pp.codigo_producto
                WHERE pp.nit_proveedor = ? AND p.eliminado = FALSE
            ");
            $stmt->execute([$nit]);
            $productos_count = $stmt->fetchColumn();
            
            if ($productos_count > 0) {
                sendResponse(false, null, "No se puede eliminar. Hay $productos_count producto(s) asociados a este proveedor");
            }
            
            // Eliminar proveedor (las relaciones en proveedor_producto se eliminarán por CASCADE)
            $stmt = $conn->prepare("DELETE FROM proveedores WHERE nit = ?");
            $stmt->execute([$nit]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Proveedor eliminado exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró el proveedor');
            }
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en proveedores.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>