<?php
// debug_login.php
require 'conexion.php';

// CAMBIA ESTO POR LOS DATOS QUE ESTÁS INTENTANDO USAR:
$email_a_probar = 'admin@maquinadoscardona.com'; 
$password_a_probar = 'admin123';

echo "<h2>Diagnóstico de Login</h2>";
echo "Intentando con: <b>$email_a_probar</b> / Pass: <b>$password_a_probar</b><br><hr>";

// 1. Verificar si existe el usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo_electronico = :email");
$stmt->execute([':email' => $email_a_probar]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<span style='color:red'>❌ Error: El correo no existe en la base de datos.</span>";
} else {
    echo "<span style='color:green'>✅ Usuario encontrado (ID: " . $user['id'] . ")</span><br>";
    
    // 2. Verificar estado
    if ($user['estado'] != 1) {
        echo "<span style='color:red'>❌ Error: El usuario está INACTIVO (estado = 0).</span>";
    } else {
        echo "<span style='color:green'>✅ Estado Activo.</span><br>";
        
        // 3. Verificar Contraseña
        echo "Hash en Base de Datos: <input type='text' value='" . $user['password_hash'] . "' style='width:400px'><br>";
        echo "Longitud del Hash: " . strlen($user['password_hash']) . " caracteres (Debe ser 60)<br>";
        
        if (password_verify($password_a_probar, $user['password_hash'])) {
            echo "<span style='color:green; font-size:20px; font-weight:bold'>✅ ¡CONTRASEÑA CORRECTA! El login debería funcionar.</span>";
        } else {
            echo "<span style='color:red; font-size:20px; font-weight:bold'>❌ CONTRASEÑA INCORRECTA.</span><br>";
            echo "El hash en la BD no coincide con '$password_a_probar'.<br>";
            echo "Probablemente se cortó o se guardó mal.";
            
            // Generar nuevo hash para corregir
            $nuevo_hash = password_hash($password_a_probar, PASSWORD_BCRYPT);
            echo "<br><br><b>Solución rápida:</b> Ejecuta este SQL en PHPMyAdmin:<br>";
            echo "<code style='background:#eee; padding:5px; display:block'>UPDATE usuarios SET password_hash = '$nuevo_hash' WHERE id = " . $user['id'] . ";</code>";
        }
    }
}
?>