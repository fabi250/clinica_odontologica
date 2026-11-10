<?php
/**
 * guardar_paciente.php
 * Endpoint para registrar o actualizar pacientes en la base de datos MySQL vía AJAX.
 * Responde exclusivamente en formato JSON.
 * Sistema: Clínica Odontológica
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

// Limpiar buffers para garantizar salida JSON limpia
ob_clean();

// 1. Validar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Sesión expirada o no autorizada. Por favor inicia sesión nuevamente.'
    ]);
    exit;
}

// 2. Validar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Método no permitido. Se requiere POST.'
    ]);
    exit;
}

// 3. Capturar datos recibidos (soporta FormData ($_POST) o JSON vía fetch)
$id               = null;
$documento        = '';
$nombre           = '';
$apellido         = '';
$telefono         = '';
$email            = '';
$fecha_nacimiento = null;

if (!empty($_POST)) {
    $id               = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $documento        = isset($_POST['documento']) ? trim($_POST['documento']) : '';
    $nombre           = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellido         = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
    $telefono         = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $email            = isset($_POST['email']) ? trim($_POST['email']) : '';
    $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? trim($_POST['fecha_nacimiento']) : null;
} else {
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput) && php_sapi_name() === 'cli') {
        $rawInput = stream_get_contents(STDIN);
    }
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $id               = !empty($jsonData['id']) ? (int)$jsonData['id'] : null;
        $documento        = isset($jsonData['documento']) ? trim($jsonData['documento']) : '';
        $nombre           = isset($jsonData['nombre']) ? trim($jsonData['nombre']) : '';
        $apellido         = isset($jsonData['apellido']) ? trim($jsonData['apellido']) : '';
        $telefono         = isset($jsonData['telefono']) ? trim($jsonData['telefono']) : '';
        $email            = isset($jsonData['email']) ? trim($jsonData['email']) : '';
        $fecha_nacimiento = !empty($jsonData['fecha_nacimiento']) ? trim($jsonData['fecha_nacimiento']) : null;
    }
}

// 4. Validaciones de campos obligatorios
if ($documento === '' || $nombre === '' || $apellido === '') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Los campos Documento, Nombre y Apellido son obligatorios.'
    ]);
    exit;
}

// Validar formato de email si fue ingresado
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'El formato del correo electrónico es inválido.'
    ]);
    exit;
}

// Validar formato de fecha si fue ingresada
if ($fecha_nacimiento !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.'
    ]);
    exit;
}

try {
    // 5. Verificar si el documento ya se encuentra registrado
    if ($id === null) {
        // Inserción: verificar si existe
        $stmtCheck = $pdo->prepare("SELECT id FROM pacientes WHERE documento = ? LIMIT 1");
        $stmtCheck->execute([$documento]);
    } else {
        // Actualización: verificar si existe en otro registro diferente
        $stmtCheck = $pdo->prepare("SELECT id FROM pacientes WHERE documento = ? AND id != ? LIMIT 1");
        $stmtCheck->execute([$documento, $id]);
    }

    if ($stmtCheck->fetch()) {
        echo json_encode([
            'status'  => 'error',
            'message' => "El documento '{$documento}' ya se encuentra registrado para otro paciente."
        ]);
        exit;
    }

    // 6. Ejecutar inserción o actualización
    if ($id === null) {
        $stmt = $pdo->prepare("
            INSERT INTO pacientes (documento, nombre, apellido, telefono, email, fecha_nacimiento)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $documento,
            $nombre,
            $apellido,
            $telefono !== '' ? $telefono : null,
            $email !== '' ? $email : null,
            $fecha_nacimiento
        ]);

        $nuevoId = (int)$pdo->lastInsertId();

        echo json_encode([
            'status'   => 'success',
            'message'  => 'Paciente registrado exitosamente.',
            'paciente' => [
                'id'        => $nuevoId,
                'documento' => $documento,
                'nombre'    => $nombre,
                'apellido'  => $apellido
            ]
        ]);
        exit;
    } else {
        $stmt = $pdo->prepare("
            UPDATE pacientes 
            SET documento = ?, nombre = ?, apellido = ?, telefono = ?, email = ?, fecha_nacimiento = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $documento,
            $nombre,
            $apellido,
            $telefono !== '' ? $telefono : null,
            $email !== '' ? $email : null,
            $fecha_nacimiento,
            $id
        ]);

        echo json_encode([
            'status'  => 'success',
            'message' => 'Datos del paciente actualizados exitosamente.'
        ]);
        exit;
    }

} catch (PDOException $e) {
    error_log("Error al guardar paciente: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Ocurrió un error al procesar la solicitud en la base de datos.'
    ]);
    exit;
}
