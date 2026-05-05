<?php
require 'libs/phpmailer/Exception.php';
require 'libs/phpmailer/PHPMailer.php';
require 'libs/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->SMTPDebug = 2; // <-- Muestra el diálogo completo
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'TU_CORREO@gmail.com';   // <-- Pon tu correo real aquí
$mail->Password   = 'abcdefghijklmnop';       // <-- Contraseña de app de 16 caracteres
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;

$mail->setFrom('TU_CORREO@gmail.com', 'Test');
$mail->addAddress('TU_CORREO@gmail.com'); // Envíate a ti mismo
$mail->Subject = 'Test SMTP';
$mail->Body    = 'Si ves esto, funciona.';

try {
    $mail->send();
    echo "<h2 style='color:green'>✓ CORREO ENVIADO CORRECTAMENTE</h2>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>✗ ERROR: " . $mail->ErrorInfo . "</h2>";
}
?>