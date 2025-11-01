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
            // Obtener todos los métodos de pago
            if (isset($_GET['id'])) {
                // Obtener un método de pago específico
                $stmt = $conn->prepare("
                    SELECT id, nombre, fecha_creacion
                    FROM metodos_pago
                    WHERE id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $metodo = $stmt->fetch();
                
                if ($metodo) {
                    sendResponse(true, $metodo, 'Método de pago obtenido correctamente');
                } else {
                    sendResponse(false, null, 'Método de pago no encontrado');
                }
            } else {
                // Obtener todos los métodos de pago
                $stmt = $conn->query("
                    SELECT 
                        id,
                        nombre,
                        fecha_creacion
                    FROM metodos_pago
                    ORDER BY nombre ASC
                ");
                $metodos = $stmt->fetchAll();
                
                sendResponse(true, $metodos, 'Métodos de pago obtenidos correctamente');
            }
            break;

        case 'POST':
            // Crear nuevo método de pago
            $data = getRequestData();
            
            $nombre = $data['nombre'] ?? '';
            
            // Validaciones
            if (empty($nombre)) {
                sendResponse(false, null, 'El nombre del método de pago es obligatorio');
            }
            
            // Verificar si el nombre ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM metodos_pago WHERE nombre = ?");
            $stmt->execute([$nombre]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe un método de pago con ese nombre');
            }
            
            // Insertar método de pago
            $stmt = $conn->prepare("
                INSERT INTO metodos_pago (nombre)
                VALUES (?)
            ");
            $stmt->execute([$nombre]);
            
            sendResponse(true, ['id' => $conn->lastInsertId()], 'Método de pago creado exitosamente');
            break;

        case 'PUT':
            // Actualizar método de pago
            $data = getRequestData();
            
            $id = $data['id'] ?? '';
            $nombre = $data['nombre'] ?? '';
            
            if (empty($id) || empty($nombre)) {
                sendResponse(false, null, 'ID y nombre son requeridos');
            }
            
            // Verificar si existe otro método de pago con el mismo nombre
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM metodos_pago 
                WHERE nombre = ? AND id != ?
            ");
            $stmt->execute([$nombre, $id]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe otro método de pago con ese nombre');
            }
            
            // Actualizar método de pago
            $stmt = $conn->prepare("
                UPDATE metodos_pago 
                SET nombre = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $id]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Método de pago actualizado exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró el método de pago o no hubo cambios');
            }
            break;

        case 'DELETE':
            // Eliminar método de pago
            $data = getRequestData();
            $id = $data['id'] ?? '';
            
            if (empty($id)) {
                sendResponse(false, null, 'ID de método de pago es requerido');
            }
            
            // Verificar si hay facturas usando este método de pago
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM facturas 
                WHERE metodo_pago_id = ?
            ");
            $stmt->execute([$id]);
            $facturas_count = $stmt->fetchColumn();
            
            if ($facturas_count > 0) {
                sendResponse(false, null, "No se puede eliminar. Hay $facturas_count factura(s) usando este método de pago");
            }
            
            // Eliminar método de pago
            $stmt = $conn->prepare("DELETE FROM metodos_pago WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Método de pago eliminado exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró el método de pago');
            }
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en metodos-pago.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>