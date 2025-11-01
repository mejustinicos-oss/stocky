<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    sendResponse(true, [
        'user' => [
            'cedula' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_nombre'],
            'rol' => $_SESSION['user_rol'],
            'email' => $_SESSION['user_email']
        ]
    ], 'Sesión activa');
} else {
    sendResponse(false, null, 'No hay sesión activa');
}
?>