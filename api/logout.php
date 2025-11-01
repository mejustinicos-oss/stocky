<?php
session_start();
require_once 'config.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destruir la sesión
session_destroy();

sendResponse(true, null, 'Sesión cerrada exitosamente');
?>