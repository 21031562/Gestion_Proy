<?php
// php/enviar_correo.php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']); exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$id_para_envio = $data['id'] ?? 0;
$email_destino = trim($data['email'] ?? '');

if (!$id_para_envio || empty($email_destino)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']); exit;
}

// 1. CARGAR LIBRERIAS Y PDF
require '../libs/phpmailer/Exception.php';
require '../libs/phpmailer/PHPMailer.php';
require '../libs/phpmailer/SMTP.php';

// TRUCO: Cambiamos directorio para que generar_pdf encuentre fpdf/conexion
$old_cwd = getcwd();
chdir('..'); // Vamos a la raíz
$modo_envio = true; // BANDERA CLAVE
ob_start(); // Buffer para capturar cualquier echo perdido
require 'generar_pdf.php'; // Esto generará la variable $contenido_pdf
ob_end_clean();
chdir($old_cwd); // Regresamos

if (empty($contenido_pdf)) {
    echo json_encode(['success' => false, 'message' => 'Error generando PDF']); exit;
}

// 2. OBTENER CREDENCIALES BD
require '../config/conexion.php';
$cred = $pdo->query("SELECT * FROM configuracion_empresa WHERE id=1")->fetch();

if (empty($cred['email_smtp_user'])) {
    echo json_encode(['success' => false, 'message' => 'Correo SMTP no configurado por Admin.']); exit;
}

// 3. ENVIAR
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Ojo: Ajusta si no es gmail
    $mail->SMTPAuth = true;
    $mail->Username = $cred['email_smtp_user'];
    $mail->Password = $cred['email_smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $cred['email_smtp_port'];

    $mail->setFrom($cred['email_smtp_user'], 'Maquinados Cardona');
    $mail->addAddress($email_destino);
    
    // Nombre del archivo
    $folio = 'MB-'.$id_para_envio; 
    $mail->addStringAttachment($contenido_pdf, "Cotizacion_$folio.pdf");

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = "Cotización $folio - Maquinados Cardona";
    $mail->Body    = "<h2>Hola,</h2><p>Adjuntamos la cotización solicitada.</p><p>Saludos,<br><b>Maquinados Cardona</b></p>";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente a '.$email_destino]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error Mailer: ' . $mail->ErrorInfo]);
}
?>