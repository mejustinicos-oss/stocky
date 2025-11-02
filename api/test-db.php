<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnóstico de Conexión</h2>";

// Test 1: Variables de entorno
echo "<h3>1. Variables de Entorno:</h3>";
echo "DB_HOST: " . getenv('DB_HOST') . " (variable) o database-maicol (default)<br>";
echo "DB_NAME: " . getenv('DB_NAME') . " (variable) o databasestocky (default)<br>";
echo "DB_USER: " . getenv('DB_USER') . " (variable) o admin (default)<br>";
echo "DB_PORT: " . getenv('DB_PORT') . " (variable) o 3306 (default)<br>";
echo "DB_PASS: " . (getenv('DB_PASS') ? '***SET***' : '***NOT SET***') . "<br>";

// Test 2: Extensión PDO
echo "<h3>2. Extensión PDO MySQL:</h3>";
if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL está instalado<br>";
} else {
    echo "❌ PDO MySQL NO está instalado<br>";
}

// Test 3: Intentar conexión
echo "<h3>3. Prueba de Conexión:</h3>";

$hosts = [
    'database-maicol',
    'localhost',
    '127.0.0.1',
    getenv('DB_HOST') ?: 'database-maicol'
];

foreach ($hosts as $host) {
    echo "<br><strong>Probando host: $host</strong><br>";
    
    try {
        $dsn = "mysql:host=$host;port=3306;dbname=databasestocky;charset=utf8mb4";
        $conn = new PDO($dsn, 'admin', '3215556611xd', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo "✅ CONEXIÓN EXITOSA con $host<br>";
        
        // Test de query
        $stmt = $conn->query("SELECT DATABASE() as db, NOW() as now");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Base de datos actual: " . $result['db'] . "<br>";
        echo "Hora del servidor: " . $result['now'] . "<br>";
        break;
        
    } catch (PDOException $e) {
        echo "❌ Error con $host: " . $e->getMessage() . "<br>";
    }
}

// Test 4: Verificar tabla usuarios
echo "<h3>4. Verificar tabla usuarios:</h3>";
if (isset($conn)) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabla 'usuarios' existe<br>";
            
            // Verificar estructura
            $stmt = $conn->query("DESCRIBE usuarios");
            echo "<strong>Columnas:</strong><br>";
            while ($row = $stmt->fetch()) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
            }
            
            // Contar registros
            $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios");
            $count = $stmt->fetch();
            echo "<strong>Total de usuarios:</strong> " . $count['total'] . "<br>";
            
        } else {
            echo "❌ Tabla 'usuarios' NO existe<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Error verificando tabla: " . $e->getMessage() . "<br>";
    }
}

// Test 5: Información del servidor
echo "<h3>5. Información del Servidor:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' . "<br>";
?>