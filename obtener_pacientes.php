<?php
/**
 * obtener_pacientes.php
 * Endpoint para consultar la lista de pacientes o un paciente específico desde la base de datos.
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

try {
    // Si se envía un ID específico mediante parámetro GET
    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("
            SELECT id, documento, nombre, apellido, telefono, email, fecha_nacimiento, created_at
            FROM pacientes
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $paciente = $stmt->fetch();

        if ($paciente) {
            echo json_encode([
                'status' => 'success',
                'data'   => $paciente
            ]);
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Paciente no encontrado.'
            ]);
        }
        exit;
    }

    // Consulta general de todos los pacientes ordenados por el más reciente
    $stmt = $pdo->query("
        SELECT id, documento, nombre, apellido, telefono, email, fecha_nacimiento, created_at
        FROM pacientes
        ORDER BY id DESC
    ");
    $pacientes = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'total'  => count($pacientes),
        'data'   => $pacientes
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Error al consultar pacientes: " . $e->getMessage());
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al obtener la lista de pacientes.'
    ]);
    exit;
}
