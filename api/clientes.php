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
            // Obtener todos los clientes con información de compras
            $stmt = $conn->prepare("
                SELECT 
                    c.cedula,
                    c.nombre,
                    c.telefono,
                    c.email,
                    c.direccion,
                    COUNT(f.numero_factura) as total_compras,
                    COALESCE(SUM(f.total), 0) as total_gastado
                FROM clientes c
                LEFT JOIN facturas f ON c.cedula = f.cedula_cliente AND f.estado = 'Completada'
                GROUP BY c.cedula
                ORDER BY c.nombre ASC
            ");
            $stmt->execute();
            $clientes = $stmt->fetchAll();
            
            sendResponse(true, $clientes, 'Clientes obtenidos correctamente');
            break;

        case 'PUT':
            // Actualizar cliente
            $data = getRequestData();
            
            $cedula = $data['cedula'] ?? '';
            $nombre = $data['nombre'] ?? '';
            $telefono = $data['telefono'] ?? '';
            $email = $data['email'] ?? '';
            $direccion = $data['direccion'] ?? '';
            
            if (empty($cedula) || empty($nombre)) {
                sendResponse(false, null, 'Cédula y nombre son requeridos');
            }
            
            // Actualizar cliente
            $stmt = $conn->prepare("
                UPDATE clientes 
                SET nombre = ?, telefono = ?, email = ?, direccion = ?
                WHERE cedula = ?
            ");
            $stmt->execute([$nombre, $telefono, $email, $direccion, $cedula]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Cliente actualizado exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró el cliente o no hubo cambios');
            }
            break;

        case 'DELETE':
            // Eliminar cliente
            $data = getRequestData();
            $cedula = $data['cedula'] ?? '';
            
            if (empty($cedula)) {
                sendResponse(false, null, 'Cédula de cliente es requerida');
            }
            
            // Verificar si el cliente tiene facturas
            $stmt = $conn->prepare("SELECT COUNT(*) FROM facturas WHERE cedula_cliente = ?");
            $stmt->execute([$cedula]);
            $facturas_count = $stmt->fetchColumn();
            
            if ($facturas_count > 0) {
                sendResponse(false, null, "No se puede eliminar. El cliente tiene $facturas_count factura(s) asociada(s)");
            }
            
            // Eliminar cliente
            $stmt = $conn->prepare("DELETE FROM clientes WHERE cedula = ?");
            $stmt->execute([$cedula]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Cliente eliminado exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró el cliente');
            }
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en clientes.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>