<?php
// php/cambiar_estado.php
session_start();
require '../config/conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'message'=>'Sesión expirada']); exit; }

$id = $_POST['id'];
$pass = $_POST['password'];
$uid = $_SESSION['user_id'];

// 1. Verificar contraseña del usuario actual
$stmtUser = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = :uid");
$stmtUser->execute([':uid' => $uid]);
$user = $stmtUser->fetch();

if (!$user || !password_verify($pass, $user['password_hash'])) {
    echo json_encode(['success'=>false, 'message'=>'Contraseña incorrecta']); exit;
}

// 2. Actualizar estado
$stmtUpd = $pdo->prepare("UPDATE cotizaciones SET estado_cierre = 'completada', fecha_cierre = NOW() WHERE id = :id");
$stmtUpd->execute([':id' => $id]);

// AQUI PODRÍAS LLAMAR AL ENVÍO DE CORREO DE ALERTA SI LO DESEAS
// include 'enviar_alerta_cierre.php'; 

echo json_encode(['success'=>true]);
?>