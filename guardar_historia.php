<?php
/**
 * guardar_historia.php
 * Endpoint para registrar una nueva atención odontológica en la historia clínica.
 * Responde exclusivamente en formato JSON.
 * Sistema: Clínica Odontológica
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

// Limpiar buffers
ob_clean();

// 1. Validar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Sesión expirada o no autorizada.'
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

// 3. Capturar datos (soporta FormData ($_POST) o JSON vía fetch)
$paciente_id        = 0;
$procedimiento_id   = 0;
$observaciones      = '';
$costo_aplicado     = null;
$fecha_procedimiento = null;

if (!empty($_POST)) {
    $paciente_id         = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
    $procedimiento_id    = isset($_POST['procedimiento_id']) ? (int)$_POST['procedimiento_id'] : 0;
    $observaciones       = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    $costo_aplicado      = isset($_POST['costo_aplicado']) && $_POST['costo_aplicado'] !== '' ? (float)$_POST['costo_aplicado'] : null;
    $fecha_procedimiento = !empty($_POST['fecha_procedimiento']) ? trim($_POST['fecha_procedimiento']) : null;
} else {
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput) && php_sapi_name() === 'cli') {
        $rawInput = stream_get_contents(STDIN);
    }
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $paciente_id         = isset($jsonData['paciente_id']) ? (int)$jsonData['paciente_id'] : 0;
        $procedimiento_id    = isset($jsonData['procedimiento_id']) ? (int)$jsonData['procedimiento_id'] : 0;
        $observaciones       = isset($jsonData['observaciones']) ? trim($jsonData['observaciones']) : '';
        $costo_aplicado      = isset($jsonData['costo_aplicado']) && $jsonData['costo_aplicado'] !== '' ? (float)$jsonData['costo_aplicado'] : null;
        $fecha_procedimiento = !empty($jsonData['fecha_procedimiento']) ? trim($jsonData['fecha_procedimiento']) : null;
    }
}

// 4. Validar campos requeridos
if ($paciente_id <= 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Debe seleccionar un paciente válido.'
    ]);
    exit;
}

if ($procedimiento_id <= 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Debe seleccionar un procedimiento odontológico.'
    ]);
    exit;
}

if ($costo_aplicado === null || $costo_aplicado < 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Debe especificar un costo aplicado válido (mayor o igual a 0).'
    ]);
    exit;
}

// Si no se proporcionó fecha de procedimiento, usar la fecha y hora actual
if (empty($fecha_procedimiento)) {
    $fecha_procedimiento = date('Y-m-d H:i:s');
}

try {
    // 5. Verificar que el paciente exista
    $stmtCheckPac = $pdo->prepare("SELECT id FROM pacientes WHERE id = ? LIMIT 1");
    $stmtCheckPac->execute([$paciente_id]);
    if (!$stmtCheckPac->fetch()) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'El paciente seleccionado no existe en el sistema.'
        ]);
        exit;
    }

    // 6. Verificar que el procedimiento exista
    $stmtCheckProc = $pdo->prepare("SELECT id, nombre FROM procedimientos WHERE id = ? LIMIT 1");
    $stmtCheckProc->execute([$procedimiento_id]);
    $procInfo = $stmtCheckProc->fetch();
    if (!$procInfo) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'El procedimiento seleccionado no existe.'
        ]);
        exit;
    }

    // 7. Insertar el registro en historias_clinicas
    $stmtInsert = $pdo->prepare("
        INSERT INTO historias_clinicas (paciente_id, procedimiento_id, fecha_procedimiento, observaciones, costo_aplicado)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([
        $paciente_id,
        $procedimiento_id,
        $fecha_procedimiento,
        $observaciones !== '' ? $observaciones : null,
        $costo_aplicado
    ]);

    $nuevaAtencionId = (int)$pdo->lastInsertId();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Atención odontológica registrada exitosamente en la historia clínica.',
        'data'    => [
            'id'                  => $nuevaAtencionId,
            'paciente_id'         => $paciente_id,
            'procedimiento_id'    => $procedimiento_id,
            'procedimiento_nombre'=> $procInfo['nombre'],
            'fecha_procedimiento' => $fecha_procedimiento,
            'observaciones'       => $observaciones,
            'costo_aplicado'      => $costo_aplicado
        ]
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Error al guardar atención en historia clínica: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al guardar la atención médica en la base de datos.'
    ]);
    exit;
}
