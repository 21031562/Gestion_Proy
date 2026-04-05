<?php
// conexion.php
$host = "localhost";
$db   = "maquinados_cardona_db";
$user = "root";
$pass = ""; // XAMPP por defecto no tiene contraseña
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En producción no se debe mostrar el error real al usuario, pero para desarrollo ayuda:
    die("Error de conexión a la Base de Datos: " . $e->getMessage());
}
?>