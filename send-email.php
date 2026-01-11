<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Cargar configuracion desde .env
function loadEnv($path) {
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

$env = loadEnv(__DIR__ . '/.env');

// Configuracion
$smtp_host = $env['SMTP_HOST'] ?? 'smtp.gmail.com';
$smtp_port = $env['SMTP_PORT'] ?? 587;
$smtp_user = $env['SMTP_USER'] ?? '';
$smtp_pass = $env['SMTP_PASSWORD'] ?? '';
$to_email = $env['CONTACT_EMAIL'] ?? 'crcarreno@gmail.com';
$site_name = $env['SITE_NAME'] ?? 'PulseConnector';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Obtener datos del formulario
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$number = isset($_POST['number']) ? trim($_POST['number']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validacion basica
$errors = [];

if (empty($name)) {
    $errors[] = 'El nombre es requerido';
}

if (empty($email)) {
    $errors[] = 'El email es requerido';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'El email no es valido';
}

if (empty($message)) {
    $errors[] = 'El mensaje es requerido';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Sanitizar datos
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$number = htmlspecialchars($number, ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Preparar el asunto del email
$email_subject = !empty($subject) ? "[$site_name] $subject" : "[$site_name] Nuevo mensaje de contacto";

// Preparar el cuerpo del email
$email_body = "Has recibido un nuevo mensaje desde el formulario de contacto de $site_name.\n\n";
$email_body .= "===========================================\n";
$email_body .= "Nombre: $name\n";
$email_body .= "Email: $email\n";
if (!empty($number)) {
    $email_body .= "Telefono: $number\n";
}
$email_body .= "Asunto: $subject\n";
$email_body .= "===========================================\n\n";
$email_body .= "Mensaje:\n$message\n";
$email_body .= "\n===========================================\n";
$email_body .= "Fecha: " . date('Y-m-d H:i:s') . "\n";

// Enviar con PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp_port;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($smtp_user, $site_name);
    $mail->addAddress($to_email);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(false);
    $mail->Subject = $email_subject;
    $mail->Body    = $email_body;

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente. Nos pondremos en contacto pronto.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar el mensaje. Por favor, intenta de nuevo mas tarde.'
    ]);
}
?>
