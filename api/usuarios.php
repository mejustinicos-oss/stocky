<?php
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    sendResponse(false, null, 'No autorizado');
    exit;
}

// VERIFICAR QUE SOLO ADMINISTRADORES PUEDAN ACCEDER
if ($_SESSION['user_rol'] !== 'Administrador') {
    http_response_code(403);
    sendResponse(false, null, 'Acceso denegado. Solo administradores pueden gestionar usuarios.');
    exit;
}

$conn = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener todos los usuarios
            $stmt = $conn->prepare("
                SELECT 
                    cedula,
                    nombre,
                    email,
                    telefono,
                    password,
                    rol,
                    estado,
                    fecha_creacion,
                    fecha_actualizacion
                FROM usuarios
                ORDER BY nombre ASC
            ");
            $stmt->execute();
            $usuarios = $stmt->fetchAll();
            
            sendResponse(true, $usuarios, 'Usuarios obtenidos correctamente');
            break;

        case 'POST':
            // Crear nuevo usuario
            $data = getRequestData();
            
            $cedula = trim($data['cedula'] ?? '');
            $nombre = trim($data['nombre'] ?? '');
            $email = trim($data['email'] ?? '');
            $telefono = trim($data['telefono'] ?? '');
            $password = $data['password'] ?? '';
            $rol = $data['rol'] ?? 'Vendedor';
            $estado = $data['estado'] ?? 'Activo';
            
            // Validaciones
            if (empty($cedula) || empty($nombre) || empty($email) || empty($telefono) || empty($password)) {
                sendResponse(false, null, 'Todos los campos son obligatorios');
            }
            
            // Validar longitud de contraseña
            if (strlen($password) < 6) {
                sendResponse(false, null, 'La contraseña debe tener al menos 6 caracteres');
            }
            
            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendResponse(false, null, 'El formato del email no es válido');
            }
            
            // Verificar si la cédula ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE cedula = ?");
            $stmt->execute([$cedula]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe un usuario con esta cédula');
            }
            
            // Verificar si el email ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe un usuario con este email');
            }
            
            // INSERTAR CON CONTRASEÑA EN TEXTO PLANO
            $stmt = $conn->prepare("
                INSERT INTO usuarios (cedula, nombre, email, telefono, password, rol, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$cedula, $nombre, $email, $telefono, $password, $rol, $estado]);
            
            sendResponse(true, ['cedula' => $cedula], 'Usuario creado exitosamente');
            break;

        case 'PUT':
            // Actualizar usuario
            $data = getRequestData();
            
            $cedula = trim($data['cedula'] ?? '');
            $nombre = trim($data['nombre'] ?? '');
            $email = trim($data['email'] ?? '');
            $telefono = trim($data['telefono'] ?? '');
            $password = $data['password'] ?? '';
            $rol = $data['rol'] ?? '';
            $estado = $data['estado'] ?? '';
            
            if (empty($cedula) || empty($nombre) || empty($email) || empty($telefono)) {
                sendResponse(false, null, 'Todos los campos son obligatorios excepto contraseña');
            }
            
            // Verificar si el email ya existe en otro usuario
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM usuarios 
                WHERE email = ? AND cedula != ?
            ");
            $stmt->execute([$email, $cedula]);
            if ($stmt->fetchColumn() > 0) {
                sendResponse(false, null, 'Ya existe otro usuario con este email');
            }
            
            // Verificar si estamos intentando desactivar al último admin activo
            if ($rol === 'Administrador' && $estado === 'Inactivo') {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) FROM usuarios 
                    WHERE rol = 'Administrador' 
                    AND estado = 'Activo' 
                    AND cedula != ?
                ");
                $stmt->execute([$cedula]);
                if ($stmt->fetchColumn() == 0) {
                    sendResponse(false, null, 'No puede desactivar el único administrador activo del sistema');
                }
            }
            
            // ACTUALIZAR CON CONTRASEÑA EN TEXTO PLANO
            if (!empty($password)) {
                // Si se proporciona contraseña, actualizarla
                $stmt = $conn->prepare("
                    UPDATE usuarios 
                    SET nombre = ?, email = ?, telefono = ?, password = ?, rol = ?, estado = ?
                    WHERE cedula = ?
                ");
                $stmt->execute([$nombre, $email, $telefono, $password, $rol, $estado, $cedula]);
            } else {
                // Si no se proporciona contraseña, mantener la actual
                $stmt = $conn->prepare("
                    UPDATE usuarios 
                    SET nombre = ?, email = ?, telefono = ?, rol = ?, estado = ?
                    WHERE cedula = ?
                ");
                $stmt->execute([$nombre, $email, $telefono, $rol, $estado, $cedula]);
            }
            
            if ($stmt->rowCount() === 0) {
                sendResponse(false, null, 'Usuario no encontrado');
            }
            
            sendResponse(true, null, 'Usuario actualizado exitosamente');
            break;

        case 'DELETE':
            // Eliminar usuario
            $data = getRequestData();
            $cedula = $data['cedula'] ?? null;
            
            if (empty($cedula)) {
                sendResponse(false, null, 'Cédula de usuario es requerida');
            }
            
            // Obtener datos del usuario a eliminar
            $stmt = $conn->prepare("SELECT rol, estado FROM usuarios WHERE cedula = ?");
            $stmt->execute([$cedula]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                sendResponse(false, null, 'Usuario no encontrado');
            }
            
            // Verificar si es el último administrador activo
            if ($usuario['rol'] === 'Administrador' && $usuario['estado'] === 'Activo') {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) FROM usuarios 
                    WHERE rol = 'Administrador' 
                    AND estado = 'Activo' 
                    AND cedula != ?
                ");
                $stmt->execute([$cedula]);
                if ($stmt->fetchColumn() == 0) {
                    sendResponse(false, null, 'No se puede eliminar el único administrador activo del sistema');
                }
            }
            
            // Verificar si el usuario ha generado facturas
            $stmt = $conn->prepare("SELECT COUNT(*) FROM facturas WHERE cedula_vendedor = ?");
            $stmt->execute([$cedula]);
            $facturasGeneradas = $stmt->fetchColumn();
            
            if ($facturasGeneradas > 0) {
                sendResponse(false, null, "No se puede eliminar el usuario porque ha generado {$facturasGeneradas} factura(s). Por seguridad, desactívelo en lugar de eliminarlo.");
            }
            
            // Eliminar usuario
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE cedula = ?");
            $stmt->execute([$cedula]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse(true, null, 'Usuario eliminado exitosamente');
            } else {
                sendResponse(false, null, 'No se encontró el usuario');
            }
            break;

        default:
            sendResponse(false, null, 'Método no permitido');
            break;
    }
} catch (PDOException $e) {
    error_log("Error en usuarios.php: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
}
?>