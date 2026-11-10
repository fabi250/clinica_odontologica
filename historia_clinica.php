<?php
/**
 * historia_clinica.php
 * Módulo de Historia Clínica Odontológica con selector de pacientes,
 * consulta de atenciones previas y registro de nuevas consultas vía AJAX.
 * Sistema: Clínica Odontológica
 */

require_once __DIR__ . '/conexion.php';

// Validar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuarioEmail  = $_SESSION['usuario_email'] ?? '';

// Obtener lista de pacientes registrados para el selector
$pacientesList = [];
try {
    $stmtPac = $pdo->query("SELECT id, documento, nombre, apellido, telefono, email FROM pacientes ORDER BY nombre ASC, apellido ASC");
    $pacientesList = $stmtPac->fetchAll();
} catch (PDOException $e) {
    error_log("Error al cargar pacientes para historia clínica: " . $e->getMessage());
}

// Obtener lista de procedimientos activos para el formulario
$procedimientosList = [];
try {
    $stmtProc = $pdo->query("SELECT id, nombre, descripcion, costo FROM procedimientos ORDER BY nombre ASC");
    $procedimientosList = $stmtProc->fetchAll();
} catch (PDOException $e) {
    error_log("Error al cargar procedimientos: " . $e->getMessage());
}

// ID de paciente preseleccionado vía GET (si viene de otra página)
$preSelectedId = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica | Clínica Odontológica</title>

    <!-- Bootstrap 5.3.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- FontAwesome 6.5.2 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <style>
        body {
            background-color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-wrapper {
            flex: 1;
        }
        .card-custom {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
        }
        .paciente-badge-info {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
        }
    </style>
</head>
<body>

    <!-- Barra de Navegación Principal -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="dashboard.php">
                <i class="fa-solid fa-tooth text-info fa-lg"></i>
                <span>DentalCare Pro</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarHistoria" aria-controls="navbarHistoria" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarHistoria">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fa-solid fa-gauge-high me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pacientes.php">
                            <i class="fa-solid fa-hospital-user me-1"></i> Pacientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fa-solid fa-teeth me-1"></i> Procedimientos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" aria-current="page" href="historia_clinica.php">
                            <i class="fa-solid fa-clipboard-user me-1"></i> Historia Clínica
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-circle-user fa-xl text-info"></i>
                            <span class="fw-semibold"><?php echo htmlspecialchars($usuarioNombre); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li class="px-3 py-1">
                                <small class="text-muted d-block">Usuario actual:</small>
                                <span class="fw-bold small text-dark"><?php echo htmlspecialchars($usuarioEmail); ?></span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger fw-semibold d-flex align-items-center gap-2" href="logout.php">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    <span>Salir</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item ms-lg-2 d-none d-lg-block">
                        <a href="logout.php" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1" title="Cerrar sesión">
                            <i class="fa-solid fa-power-off"></i>
                            <span>Salir</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <main class="main-wrapper py-4">
        <div class="container">

            <!-- Encabezado de la Sección -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div>
                    <h2 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-clipboard-user text-primary me-2"></i>Historia Clínica Odontológica
                    </h2>
                    <p class="text-muted mb-0">Registro y seguimiento de atenciones, procedimientos y tratamientos</p>
                </div>
                <div>
                    <a href="pacientes.php" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-user-plus me-1"></i> Ir a Pacientes
                    </a>
                </div>
            </div>

            <!-- Contenedor de Alertas Dinámicas -->
            <div id="alertaHistoria"></div>

            <!-- Tarjeta 1: Selector de Paciente -->
            <div class="card card-custom mb-4">
                <div class="card-body p-4">
                    <label for="selectPaciente" class="form-label fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-user-check text-primary"></i> Seleccionar Paciente:
                    </label>
                    <div class="row align-items-center g-3">
                        <div class="col-lg-8">
                            <select id="selectPaciente" class="form-select form-select-lg shadow-sm">
                                <option value="">-- Elige un paciente de la lista --</option>
                                <?php foreach ($pacientesList as $pac): ?>
                                    <option value="<?php echo $pac['id']; ?>" <?php echo ($preSelectedId === (int)$pac['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pac['nombre'] . ' ' . $pac['apellido'] . ' - [' . $pac['documento'] . ']'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-4 text-lg-end text-muted small">
                            <i class="fa-solid fa-info-circle me-1"></i> Selecciona un paciente para habilitar la carga y registro de consultas.
                        </div>
                    </div>

                    <!-- Ficha Resumen del Paciente Seleccionado (Visible al seleccionar) -->
                    <div id="fichaPaciente" class="paciente-badge-info p-3 mt-3 d-none">
                        <div class="row align-items-center gy-2">
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Paciente:</span>
                                <strong class="text-primary fs-5" id="fichaNombreCompleto">-</strong>
                                <div class="small text-muted"><i class="fa-solid fa-id-card me-1"></i><span id="fichaDocumento">-</span></div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small d-block">Contacto:</span>
                                <div class="small text-dark"><i class="fa-solid fa-phone me-1 text-secondary"></i><span id="fichaTelefono">-</span></div>
                                <div class="small text-dark"><i class="fa-solid fa-envelope me-1 text-secondary"></i><span id="fichaEmail">-</span></div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <span class="badge bg-primary px-3 py-2 mb-1 d-inline-block">
                                    <i class="fa-solid fa-calendar-check me-1"></i> <span id="fichaTotalConsultas">0</span> atenciones
                                </span>
                                <div class="small text-muted mb-2">
                                    Total invertido: <strong class="text-success" id="fichaTotalInvertido">$0.00</strong>
                                </div>
                                <a href="#" target="_blank" class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 shadow-sm" id="btnExportarPdf" title="Generar e imprimir historia clínica en PDF">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    <span>Exportar a PDF</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Trabajo: Formulario y Tabla de Atenciones (Se habilita al seleccionar paciente) -->
            <div id="contenedorDetalleHistoria" class="d-none">
                <div class="row g-4">

                    <!-- Columna Izquierda: Formulario para registrar una nueva atención -->
                    <div class="col-lg-5">
                        <div class="card card-custom h-100">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-file-medical text-primary"></i> Registrar Nueva Atención
                                </h5>
                            </div>
                            <div class="card-body p-4 pt-2">
                                <form id="formNuevaAtencion" novalidate>
                                    <input type="hidden" id="atencionPacienteId" name="paciente_id" value="">

                                    <!-- Selector de Procedimiento -->
                                    <div class="mb-3">
                                        <label for="procedimiento_id" class="form-label fw-semibold small text-secondary">
                                            <i class="fa-solid fa-teeth me-1 text-primary"></i> Procedimiento Odontológico <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="procedimiento_id" name="procedimiento_id" required>
                                            <option value="">-- Selecciona el procedimiento --</option>
                                            <?php foreach ($procedimientosList as $proc): ?>
                                                <option value="<?php echo $proc['id']; ?>" data-costo="<?php echo htmlspecialchars($proc['costo']); ?>">
                                                    <?php echo htmlspecialchars($proc['nombre']); ?> ($<?php echo number_format($proc['costo'], 2); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Selecciona un procedimiento.</div>
                                    </div>

                                    <!-- Costo Aplicado -->
                                    <div class="mb-3">
                                        <label for="costo_aplicado" class="form-label fw-semibold small text-secondary">
                                            <i class="fa-solid fa-dollar-sign me-1 text-success"></i> Costo Aplicado ($) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" id="costo_aplicado" name="costo_aplicado" placeholder="0.00" required>
                                        </div>
                                        <small class="text-muted">Se autocompleta con el costo sugerido, pero puede modificarse.</small>
                                    </div>

                                    <!-- Observaciones Médicas -->
                                    <div class="mb-3">
                                        <label for="observaciones" class="form-label fw-semibold small text-secondary">
                                            <i class="fa-solid fa-notes-medical me-1 text-primary"></i> Observaciones Médicas / Tratamiento
                                        </label>
                                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4" placeholder="Diagnóstico, pieza dental tratada, evolución clínica, indicaciones o recetas..."></textarea>
                                    </div>

                                    <!-- Botón de Guardar -->
                                    <div class="d-grid pt-2">
                                        <button type="submit" class="btn btn-primary btn-lg fw-semibold shadow-sm" id="btnGuardarAtencion">
                                            <span id="btnGuardarAtencionTexto">
                                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Atención
                                            </span>
                                            <span id="btnGuardarAtencionSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Tabla de atenciones anteriores -->
                    <div class="col-lg-7">
                        <div class="card card-custom h-100">
                            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-clock-rotate-left text-primary"></i> Atenciones Anteriores
                                </h5>
                                <span class="badge bg-secondary rounded-pill px-3 py-1" id="contadorAtenciones">0 registros</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="tablaHistoria">
                                        <thead class="table-light text-uppercase small text-muted">
                                            <tr>
                                                <th class="ps-3" style="width: 50px;">#</th>
                                                <th style="width: 140px;">Fecha</th>
                                                <th>Procedimiento</th>
                                                <th>Observaciones</th>
                                                <th class="text-end pe-3" style="width: 110px;">Costo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaHistoriaBody">
                                            <!-- Filas generadas vía AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Estado Vacío Inicial cuando no se ha seleccionado paciente -->
            <div id="estadoSinPaciente" class="card card-custom text-center py-5">
                <div class="card-body">
                    <i class="fa-solid fa-folder-open fa-3x text-secondary opacity-50 mb-3"></i>
                    <h5 class="fw-semibold text-secondary">Ningún paciente seleccionado</h5>
                    <p class="text-muted small mb-0">Selecciona un paciente en el menú superior para ver su historial clínico y registrar atenciones.</p>
                </div>
            </div>

        </div>
    </main>

    <!-- Pie de Página -->
    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-center text-muted small">
            <span>&copy; <?php echo date('Y'); ?> <strong>DentalCare Pro</strong> &bull; Módulo de Historia Clínica</span>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Script AJAX para Historia Clínica -->
    <script src="js/historia_clinica.js"></script>

</body>
</html>
