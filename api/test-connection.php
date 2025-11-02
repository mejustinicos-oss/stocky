<?php
require_once 'config.php';

echo "Probando conexión...\n";
echo "Host: " . DB_HOST . "\n";
echo "Port: " . DB_PORT . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";

try {
    $conn = getConnection();
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa a la base de datos'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>