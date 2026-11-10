<?php
/**
 * obtener_historia.php
 * Endpoint para consultar el historial clínico de un paciente específico.
 * Responde exclusivamente en formato JSON.
 * Sistema: Clínica Odontológica
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

// Limpiar buffers
ob_clean();

// 1. Validar sesión de usuario activa
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Sesión expirada o no autorizada.'
    ]);
    exit;
}

// 2. Obtener paciente_id (soporta GET, POST o REQUEST)
$pacienteId = 0;
if (!empty($_GET['paciente_id'])) {
    $pacienteId = (int)$_GET['paciente_id'];
} elseif (!empty($_POST['paciente_id'])) {
    $pacienteId = (int)$_POST['paciente_id'];
} elseif (!empty($_REQUEST['paciente_id'])) {
    $pacienteId = (int)$_REQUEST['paciente_id'];
}

if ($pacienteId <= 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Debe especificar un ID de paciente válido.'
    ]);
    exit;
}

try {
    // 3. Obtener información básica del paciente
    $stmtPaciente = $pdo->prepare("
        SELECT id, documento, nombre, apellido, telefono, email, fecha_nacimiento, created_at
        FROM pacientes
        WHERE id = ?
        LIMIT 1
    ");
    $stmtPaciente->execute([$pacienteId]);
    $paciente = $stmtPaciente->fetch();

    if (!$paciente) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'El paciente seleccionado no existe en el sistema.'
        ]);
        exit;
    }

    // 4. Obtener atenciones anteriores del paciente con datos del procedimiento
    $stmtHistoria = $pdo->prepare("
        SELECT 
            h.id,
            h.paciente_id,
            h.procedimiento_id,
            h.fecha_procedimiento,
            h.observaciones,
            h.costo_aplicado,
            p.nombre AS procedimiento_nombre,
            p.descripcion AS procedimiento_descripcion
        FROM historias_clinicas h
        INNER JOIN procedimientos p ON h.procedimiento_id = p.id
        WHERE h.paciente_id = ?
        ORDER BY h.fecha_procedimiento DESC, h.id DESC
    ");
    $stmtHistoria->execute([$pacienteId]);
    $atenciones = $stmtHistoria->fetchAll();

    // Calcular totales para estadísticas rápidas
    $totalConsultas = count($atenciones);
    $totalInvertido = 0.00;
    foreach ($atenciones as $at) {
        $totalInvertido += (float)$at['costo_aplicado'];
    }

    echo json_encode([
        'status'          => 'success',
        'paciente'        => $paciente,
        'total_consultas' => $totalConsultas,
        'total_invertido' => $totalInvertido,
        'data'            => $atenciones
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Error al consultar historia clínica: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al consultar la historia clínica del paciente.'
    ]);
    exit;
}
