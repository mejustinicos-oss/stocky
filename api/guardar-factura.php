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
$data = getRequestData();

try {
    // Validar que el usuario tenga permisos de vendedor o administrador
    if ($_SESSION['user_rol'] !== 'Administrador' && $_SESSION['user_rol'] !== 'Vendedor') {
        sendResponse(false, null, 'No tiene permisos para realizar ventas');
    }

    // Datos de la factura
    $cliente = $data['cliente'] ?? null;
    $metodo_pago = $data['metodo_pago'] ?? null;
    $productos = $data['productos'] ?? [];
    $subtotal = $data['subtotal'] ?? 0;
    $iva = $data['iva'] ?? 0;
    $total = $data['total'] ?? 0;

    // Validaciones
    if (!$cliente || !$metodo_pago || empty($productos)) {
        sendResponse(false, null, 'Datos incompletos: cliente, método de pago y productos son requeridos');
    }

    // Verificar stock disponible
    foreach ($productos as $producto) {
        $stmt = $conn->prepare("SELECT cantidad, nombre FROM productos WHERE codigo_producto = ? AND eliminado = FALSE");
        $stmt->execute([$producto['id']]);
        $productoDB = $stmt->fetch();
        
        if (!$productoDB) {
            sendResponse(false, null, "El producto {$producto['nombre']} no existe o fue eliminado");
        }
        
        if ($productoDB['cantidad'] < $producto['cantidad']) {
            sendResponse(false, null, "Stock insuficiente para {$productoDB['nombre']}. Stock disponible: {$productoDB['cantidad']}");
        }
    }

    // Iniciar transacción
    $conn->beginTransaction();

    try {
        // 1. Verificar/crear cliente
        $stmt = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE cedula = ?");
        $stmt->execute([$cliente['id']]);
        $clienteExiste = $stmt->fetchColumn() > 0;

        if (!$clienteExiste) {
            $stmt = $conn->prepare("
                INSERT INTO clientes (cedula, nombre, telefono, email, direccion)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $cliente['id'],
                $cliente['nombre'],
                $cliente['telefono'] ?? '',
                $cliente['email'] ?? '',
                $cliente['direccion'] ?? ''
            ]);
        }

        // 2. Generar número de factura
        $stmt = $conn->query("SELECT MAX(CAST(numero_factura AS UNSIGNED)) as ultimo_numero FROM facturas");
        $result = $stmt->fetch();
        $numero_factura = str_pad(($result['ultimo_numero'] ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        // 3. Obtener ID del método de pago
        $stmt = $conn->prepare("SELECT id FROM metodos_pago WHERE nombre = ?");
        $stmt->execute([$metodo_pago]);
        $metodo_pago_row = $stmt->fetch();
        
        if (!$metodo_pago_row) {
            throw new Exception("Método de pago no válido");
        }
        $metodo_pago_id = $metodo_pago_row['id'];

        // 4. Guardar factura
        $stmt = $conn->prepare("
            INSERT INTO facturas (numero_factura, cedula_cliente, cedula_vendedor, metodo_pago_id, subtotal, iva, total, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Completada')
        ");
        $stmt->execute([
            $numero_factura,
            $cliente['id'],
            $_SESSION['user_id'],
            $metodo_pago_id,
            $subtotal,
            $iva,
            $total
        ]);

        // 5. Guardar detalle de factura y actualizar stock
        $stmt_detalle = $conn->prepare("
            INSERT INTO detalle_factura (numero_factura, codigo_producto, nombre_producto, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt_update_stock = $conn->prepare("
            UPDATE productos SET cantidad = cantidad - ? WHERE codigo_producto = ?
        ");

        foreach ($productos as $producto) {
            // Insertar detalle
            $stmt_detalle->execute([
                $numero_factura,
                $producto['id'],
                $producto['nombre'],
                $producto['cantidad'],
                $producto['precio'],
                $producto['precio'] * $producto['cantidad']
            ]);

            // Actualizar stock
            $stmt_update_stock->execute([
                $producto['cantidad'],
                $producto['id']
            ]);

            // Registrar en historial (se hará automáticamente con el trigger)
        }

        // Confirmar transacción
        $conn->commit();

        sendResponse(true, [
            'numero_factura' => $numero_factura,
            'cliente' => $cliente,
            'total' => $total,
            'fecha' => date('Y-m-d H:i:s')
        ], 'Factura guardada correctamente');

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Error en guardar-factura: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error en guardar-factura: " . $e->getMessage());
    sendResponse(false, null, $e->getMessage());
}
?>