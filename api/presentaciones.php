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
            // Obtener todas las presentaciones
            if (isset($_GET['id'])) {
                // Obtener una presentación específica
                $stmt = $conn->prepare("
                    SELECT id, nombre, fecha_creacion
                    FROM presentaciones
                    WHERE id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $presentacion = $stmt->fetch();
                
                if ($presentacion) {
                    sendResponse(true, $presentacion, 'Presentación obtenida correctamente');
                } else {
                    sendResponse(false, null, 'Presentación no encontrada');
                }
            } else {
                // Obtener todas las presentaciones
                $stmt = $conn->query("
                    SELECT 
                        id,
                        nombre,
                        fecha_creacion
                    FROM presentaciones
                    ORDER BY nombre ASC
                ");
                $presentaciones = $stmt->fetchAll();
                
                sendResponse(true, $presentaciones, 'Presentaciones obtenidas correctamente');
            }
            break;

        case 'POST':
            // Crear nueva presentación
            $data = getRequestData();
            
            $nombre = $data['nombre'] ?? '';
            
            // Validaciones
            if (empty($nombre)) {
                sendResponse(false, null, 'El nombre de la presentación es obligatorio');
            }
            
            // Verificar si el nombre ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM presentaciones WHERE nombre = ?");
            $stmt->execute([$nombre]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe una presentación con ese nombre');
            }
            
            // Insertar presentación
            $stmt = $conn->prepare("
                INSERT INTO presentaciones (nombre)
                VALUES (?)
            ");
            $stmt->execute([$nombre]);
            
            sendResponse(true, ['id' => $conn->lastInsertId()], 'Presentación creada exitosamente');
            break;

        case 'PUT':
            // Actualizar presentación
            $data = getRequestData();
            
            $id = $data['id'] ?? '';
            $nombre = $data['nombre'] ?? '';
            
            if (empty($id) || empty($nombre)) {
                sendResponse(false, null, 'ID y nombre son requeridos');
            }
            
            // Verificar si existe otra presentación con el mismo nombre
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM presentaciones 
                WHERE nombre = ? AND id != ?
            ");
            $stmt->execute([$nombre, $id]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe otra presentación con ese nombre');
            }
            
            // Actualizar presentación
            $stmt = $conn->prepare("
                UPDATE presentaciones 
                SET nombre = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $id]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Presentación actualizada exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró la presentación o no hubo cambios');
            }
            break;

        case 'DELETE':
            // Eliminar presentación
            $data = getRequestData();
            $id = $data['id'] ?? '';
            
            if (empty($id)) {
                sendResponse(false, null, 'ID de presentación es requerido');
            }
            
            // Verificar si hay productos usando esta presentación
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM productos 
                WHERE presentacion_id = ? AND eliminado = FALSE
            ");
            $stmt->execute([$id]);
            $productos_count = $stmt->fetchColumn();
            
            if ($productos_count > 0) {
                sendResponse(false, null, "No se puede eliminar. Hay $productos_count producto(s) usando esta presentación");
            }
            
            // Eliminar presentación
            $stmt = $conn->prepare("DELETE FROM presentaciones WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Presentación eliminada exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró la presentación');
            }
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en presentaciones.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>