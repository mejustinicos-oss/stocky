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
            // Obtener todas las categorías
            if (isset($_GET['id'])) {
                // Obtener una categoría específica
                $stmt = $conn->prepare("
                    SELECT id AS id, nombre, descripcion, fecha_creacion
                    FROM categorias
                    WHERE id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $categoria = $stmt->fetch();
                
                if ($categoria) {
                    sendResponse(true, $categoria, 'Categoría obtenida correctamente');
                } else {
                    sendResponse(false, null, 'Categoría no encontrada');
                }
            } else {
                // Obtener todas las categorías
                $stmt = $conn->query("
                    SELECT 
                        id AS id,
                        nombre,
                        descripcion,
                        fecha_creacion
                    FROM categorias
                    ORDER BY nombre ASC
                ");
                $categorias = $stmt->fetchAll();
                
                sendResponse(true, $categorias, 'Categorías obtenidas correctamente');
            }
            break;

        case 'POST':
            // Crear nueva categoría
            $data = getRequestData();
            
            $nombre = $data['nombre'] ?? '';
            $descripcion = $data['descripcion'] ?? '';
            
            // Validaciones
            if (empty($nombre)) {
                sendResponse(false, null, 'El nombre de la categoría es obligatorio');
            }
            
            // Verificar si el nombre ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM categorias WHERE nombre = ?");
            $stmt->execute([$nombre]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe una categoría con ese nombre');
            }
            
            // Insertar categoría
            $stmt = $conn->prepare("
                INSERT INTO categorias (nombre, descripcion)
                VALUES (?, ?)
            ");
            $stmt->execute([$nombre, $descripcion]);
            
            sendResponse(true, ['id' => $conn->lastInsertId()], 'Categoría creada exitosamente');
            break;

        case 'PUT':
            // Actualizar categoría
            $data = getRequestData();
            
            $id = $data['id'] ?? '';
            $nombre = $data['nombre'] ?? '';
            $descripcion = $data['descripcion'] ?? '';
            
            if (empty($id) || empty($nombre)) {
                sendResponse(false, null, 'ID y nombre son requeridos');
            }
            
            // Verificar si existe otra categoría con el mismo nombre
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM categorias 
                WHERE nombre = ? AND id != ?
            ");
            $stmt->execute([$nombre, $id]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe otra categoría con ese nombre');
            }
            
            // Actualizar categoría
            $stmt = $conn->prepare("
                UPDATE categorias 
                SET nombre = ?, descripcion = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $descripcion, $id]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Categoría actualizada exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró la categoría o no hubo cambios');
            }
            break;

        case 'DELETE':
            // Eliminar categoría
            $data = getRequestData();
            $id = $data['id'] ?? '';
            
            if (empty($id)) {
                sendResponse(false, null, 'ID de categoría es requerido');
            }
            
            // Verificar si hay productos usando esta categoría
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM productos 
                WHERE categoria_id = ? AND eliminado = FALSE
            ");
            $stmt->execute([$id]);
            $productos_count = $stmt->fetchColumn();
            
            if ($productos_count > 0) {
                sendResponse(false, null, "No se puede eliminar. Hay $productos_count producto(s) usando esta categoría");
            }
            
            // Eliminar categoría
            $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Categoría eliminada exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró la categoría');
            }
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en categorias.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>