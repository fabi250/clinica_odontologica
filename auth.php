<?php
/**
 * auth.php
 * Endpoint de autenticación seguro para el inicio de sesión.
 * Responde EXCLUSIVAMENTE en formato JSON:
 * {status: 'success'} o {status: 'error', message: '...'}
 */

// Establecer cabecera exclusiva JSON y prevenir salida previa
ob_start();
header('Content-Type: application/json; charset=utf-8');

// Incluir conexión a base de datos y sesión segura
require_once __DIR__ . '/conexion.php';

// Limpiar cualquier posible salida generada accidentalmente
ob_clean();

// Verificar que la petición sea mediante método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Método de solicitud no permitido.'
    ]);
    exit;
}

// Obtener datos recibidos ya sea por FormData ($_POST) o JSON vía fetch (php://input)
$email = '';
$password = '';

if (!empty($_POST['email']) || !empty($_POST['password'])) {
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
} else {
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput) && php_sapi_name() === 'cli') {
        $rawInput = stream_get_contents(STDIN);
    }
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $email    = isset($jsonData['email']) ? trim($jsonData['email']) : '';
        $password = isset($jsonData['password']) ? (string)$jsonData['password'] : '';
    }
}

// Validar que los campos requeridos no estén vacíos
if ($email === '' || $password === '') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Por favor, ingresa el correo electrónico y la contraseña.'
    ]);
    exit;
}

// Validar formato básico de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'El formato del correo electrónico no es válido.'
    ]);
    exit;
}

try {
    // Consultar usuario en la base de datos mediante consulta preparada
    $stmt = $pdo->prepare('SELECT id, nombre, email, password FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    // Validar existencia de usuario y comprobar contraseña con password_verify
    if ($usuario && password_verify($password, $usuario['password'])) {
        // Regenerar ID de sesión por seguridad contra Session Fixation
        session_regenerate_id(true);

        // Almacenar datos esenciales del usuario en la sesión
        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['login_time']     = time();

        echo json_encode([
            'status' => 'success'
        ]);
        exit;
    } else {
        // Mensaje genérico de seguridad para no filtrar si el correo existe o no
        echo json_encode([
            'status'  => 'error',
            'message' => 'Credenciales incorrectas. Verifica tu correo y contraseña.'
        ]);
        exit;
    }
} catch (PDOException $e) {
    error_log("Error en autenticación: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Ocurrió un error en el servidor al intentar autenticar.'
    ]);
    exit;
}
