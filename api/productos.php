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
            // Obtener todos los productos
            if (isset($_GET['codigo'])) {
                // Obtener un producto específico
                $stmt = $conn->prepare("
                    SELECT p.*, c.nombre as categoria_nombre, pr.nombre as presentacion_nombre
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN presentaciones pr ON p.presentacion_id = pr.id
                    WHERE p.codigo_producto = ? AND p.eliminado = FALSE
                ");
                $stmt->execute([$_GET['codigo']]);
                $producto = $stmt->fetch();
                
                if ($producto) {
                    sendResponse(true, $producto, 'Producto obtenido correctamente');
                } else {
                    sendResponse(false, null, 'Producto no encontrado');
                }
            } else {
                // Obtener todos los productos
                $stmt = $conn->prepare("
                    SELECT 
                        p.codigo_producto,
                        p.nombre,
                        p.cantidad,
                        p.precio_venta as valor,
                        c.nombre as categoria,
                        pr.nombre as presentacion,
                        p.precio_compra,
                        p.stock_minimo,
                        p.estado
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN presentaciones pr ON p.presentacion_id = pr.id
                    WHERE p.eliminado = FALSE
                    ORDER BY p.nombre
                ");
                $stmt->execute();
                $productos = $stmt->fetchAll();
                
                sendResponse(true, $productos, 'Productos obtenidos correctamente');
            }
            break;

        case 'POST':
            // Crear nuevo producto
            $data = getRequestData();
            
            $codigo_producto = $data['codigo_producto'] ?? '';
            $nombre = $data['nombre'] ?? '';
            $categoria_id = $data['categoria_id'] ?? '';
            $presentacion_id = $data['presentacion_id'] ?? '';
            $cantidad = $data['cantidad'] ?? 0;
            $precio_compra = $data['precio_compra'] ?? 0;
            $precio_venta = $data['precio_venta'] ?? 0;
            $stock_minimo = $data['stock_minimo'] ?? 5;
            
            // Validaciones
            if (empty($codigo_producto) || empty($nombre) || empty($categoria_id) || empty($presentacion_id)) {
                sendResponse(false, null, 'Todos los campos obligatorios deben ser llenados');
            }
            
            // Verificar si el código ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM productos WHERE codigo_producto = ?");
            $stmt->execute([$codigo_producto]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'El código del producto ya existe');
            }
            
            // Insertar producto
            $stmt = $conn->prepare("
                INSERT INTO productos 
                (codigo_producto, nombre, categoria_id, presentacion_id, cantidad, precio_compra, precio_venta, stock_minimo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $codigo_producto, $nombre, $categoria_id, $presentacion_id, 
                $cantidad, $precio_compra, $precio_venta, $stock_minimo
            ]);
            
            // Registrar en historial
            $stmt = $conn->prepare("
                INSERT INTO historial_productos (codigo_producto, accion, cantidad_anterior, cantidad_nueva, cedula_usuario, descripcion)
                VALUES (?, 'CREADO', 0, ?, ?, 'Producto creado')
            ");
            $stmt->execute([$codigo_producto, $cantidad, $_SESSION['user_id']]);
            
            sendResponse(true, null, 'Producto creado exitosamente');
            break;

        case 'PUT':
    // Actualizar producto
    $data = getRequestData();
    
    $codigo_producto = $data['codigo_producto'] ?? '';
    $nombre = $data['nombre'] ?? '';
    $categoria_id = $data['categoria_id'] ?? '';
    $presentacion_id = $data['presentacion_id'] ?? '';
    $cantidad = $data['cantidad'] ?? 0;
    $precio_compra = $data['precio_compra'] ?? 0;
    $precio_venta = $data['precio_venta'] ?? 0;
    $stock_minimo = $data['stock_minimo'] ?? 5;
    
    if (empty($codigo_producto)) {
        sendResponse(false, null, 'Código de producto es requerido para actualizar');
    }
    
    // Verificar que el producto existe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM productos WHERE codigo_producto = ? AND eliminado = FALSE");
    $stmt->execute([$codigo_producto]);
    if ($stmt->fetchColumn() === 0) {
        sendResponse(false, null, 'Producto no encontrado');
    }
    
    // Obtener cantidad anterior para el historial
    $stmt = $conn->prepare("SELECT cantidad FROM productos WHERE codigo_producto = ?");
    $stmt->execute([$codigo_producto]);
    $cantidad_anterior = $stmt->fetchColumn();
    
    // Actualizar producto
    $stmt = $conn->prepare("
        UPDATE productos 
        SET nombre = ?, categoria_id = ?, presentacion_id = ?, cantidad = ?, 
            precio_compra = ?, precio_venta = ?, stock_minimo = ?
        WHERE codigo_producto = ?
    ");
    
    $success = $stmt->execute([
        $nombre, $categoria_id, $presentacion_id, $cantidad,
        $precio_compra, $precio_venta, $stock_minimo, $codigo_producto
    ]);
    
    if ($success) {
        // Registrar en historial si cambió la cantidad
        if ($cantidad != $cantidad_anterior) {
            $stmt = $conn->prepare("
                INSERT INTO historial_productos (codigo_producto, accion, cantidad_anterior, cantidad_nueva, cedula_usuario, descripcion)
                VALUES (?, 'AJUSTE_INVENTARIO', ?, ?, ?, 'Actualización de inventario')
            ");
            $stmt->execute([$codigo_producto, $cantidad_anterior, $cantidad, $_SESSION['user_id']]);
        }
        
        sendResponse(true, null, 'Producto actualizado exitosamente');
    } else {
        sendResponse(false, null, 'Error al actualizar el producto');
    }
    break;

        case 'DELETE':
            // Eliminación lógica de producto
            $data = getRequestData();
            $codigo_producto = $data['codigo_producto'] ?? '';
            
            if (empty($codigo_producto)) {
                sendResponse(false, null, 'Código de producto es requerido');
            }
            
            $stmt = $conn->prepare("UPDATE productos SET eliminado = TRUE WHERE codigo_producto = ?");
            $stmt->execute([$codigo_producto]);
            
            sendResponse(true, null, 'Producto eliminado exitosamente');
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en productos: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>