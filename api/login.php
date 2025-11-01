<?php
session_start();
require_once 'config.php';

try {
    $data = getRequestData();
    
    $cedula = $data['cedula'] ?? '';
    $password = $data['password'] ?? '';
    
    // Validar que los campos no estén vacíos
    if (empty($cedula) || empty($password)) {
        sendResponse(false, null, 'Todos los campos son obligatorios');
    }
    
    $conn = getConnection();
    
    // Buscar usuario en la base de datos
    $stmt = $conn->prepare("
        SELECT cedula, nombre, email, rol, estado, password 
        FROM usuarios 
        WHERE cedula = ?
    ");
    $stmt->execute([$cedula]);
    $usuario = $stmt->fetch();
    
    // Verificar si el usuario existe
    if (!$usuario) {
        sendResponse(false, null, 'Cédula o contraseña incorrectos');
    }
    
    // Verificar si el usuario está activo
    if ($usuario['estado'] !== 'Activo') {
        sendResponse(false, null, 'Usuario inactivo. Contacte al administrador');
    }
    
    // VERIFICACIÓN EN TEXTO PLANO
    if ($password !== $usuario['password']) {
        sendResponse(false, null, 'Cédula o contraseña incorrectos');
    }
    
    // Login exitoso - Crear sesión
    $_SESSION['user_id'] = $usuario['cedula'];
    $_SESSION['user_nombre'] = $usuario['nombre'];
    $_SESSION['user_rol'] = $usuario['rol'];
    $_SESSION['user_email'] = $usuario['email'];
    $_SESSION['logged_in'] = true;
    
    // Remover la contraseña antes de enviar la respuesta
    unset($usuario['password']);
    
    sendResponse(true, [
        'user' => $usuario,
        'redirect' => 'dashboard.html'
    ], 'Inicio de sesión exitoso');
    
} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    sendResponse(false, null, 'Error en el servidor: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    sendResponse(false, null, 'Error: ' . $e->getMessage());
}
?>