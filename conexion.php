<?php
/**
 * conexion.php
 * Conexión segura a la base de datos MySQL mediante PDO y gestión de sesión.
 * Sistema: Clínica Odontológica
 */

// 1. Activar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Parámetros de configuración de la base de datos
$host     = 'localhost';
$db       = 'odontologia_db';
$user     = 'root';
$pass     = '';
$charset  = 'utf8mb4';

// 3. Data Source Name (DSN)
$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

// 4. Opciones de configuración de PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en caso de error SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve resultados como arreglos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactiva emulación para usar consultas preparadas nativas
];

// 5. Instancia de conexión protegida con bloque try-catch
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $conexion = $pdo; // Alias para compatibilidad
} catch (PDOException $e) {
    // Registro del error en logs del servidor
    error_log("Error de conexión PDO: " . $e->getMessage());
    
    // Mensaje descriptivo controlado para el usuario o desarrollador
    die("<h3>Error de conexión a la base de datos</h3>" .
        "<p>No se pudo conectar a <strong>{$db}</strong> en <strong>{$host}</strong>.</p>" .
        "<p>Detalle técnico: " . htmlspecialchars($e->getMessage()) . "</p>" .
        "<p><em>Asegúrate de que el servicio MySQL esté iniciado en XAMPP y que la base de datos '{$db}' haya sido creada.</em></p>");
}
