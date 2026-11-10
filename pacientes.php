<?php
/**
 * pacientes.php
 * Módulo de gestión y listado de pacientes con interfaz Bootstrap 5 y CRUD AJAX.
 * Sistema: Clínica Odontológica
 */

// 1. Conexión y sesión segura
require_once __DIR__ . '/conexion.php';

// 2. Proteger la vista: requerir sesión de usuario
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuarioEmail  = $_SESSION['usuario_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pacientes | Clínica Odontológica</title>

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
        .table-hover tbody tr:hover {
            background-color: #f8fafc;
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

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPacientes" aria-controls="navbarPacientes" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarPacientes">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fa-solid fa-gauge-high me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" aria-current="page" href="pacientes.php">
                            <i class="fa-solid fa-hospital-user me-1"></i> Pacientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fa-solid fa-teeth me-1"></i> Procedimientos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="historia_clinica.php">
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
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-hospital-user text-primary me-2"></i>Gestión de Pacientes
                    </h2>
                    <p class="text-muted mb-0">Directorio y registro de pacientes en tiempo real</p>
                </div>
                <div>
                    <!-- Botón para abrir el Modal de Nuevo Paciente -->
                    <button type="button" class="btn btn-primary btn-lg shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalPaciente" id="btnNuevoPaciente">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Nuevo Paciente</span>
                    </button>
                </div>
            </div>

            <!-- Contenedor de Notificaciones Globales -->
            <div id="notificacionGlobal"></div>

            <!-- Tarjeta con Buscador y Tabla de Pacientes -->
            <div class="card card-custom">
                <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold mb-0 text-dark">Listado de Pacientes</h5>
                        <span class="badge bg-primary rounded-pill px-3 py-1" id="contadorPacientes">0 pacientes</span>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 bg-light" id="inputBuscarPaciente" placeholder="Buscar por documento, nombre o apellido...">
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaPacientes">
                            <thead class="table-light text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-4" style="width: 70px;">#</th>
                                    <th>Documento</th>
                                    <th>Paciente</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Fecha Nacimiento</th>
                                    <th class="text-center pe-4" style="width: 130px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaPacientesBody">
                                <!-- Filas cargadas dinámicamente vía AJAX -->
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        <span>Cargando pacientes desde la base de datos...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal para Registrar / Editar Paciente -->
    <div class="modal fade" id="modalPaciente" tabindex="-1" aria-labelledby="modalPacienteLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalPacienteLabel">
                        <i class="fa-solid fa-user-plus"></i>
                        <span id="modalTituloTexto">Nuevo Paciente</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form id="formPaciente" novalidate>
                    <div class="modal-body p-4">

                        <!-- Contenedor de alertas dentro del modal -->
                        <div id="modalAlert"></div>

                        <!-- ID oculto para edición -->
                        <input type="hidden" id="pacienteId" name="id" value="">

                        <div class="row g-3">
                            <!-- Documento -->
                            <div class="col-md-6">
                                <label for="documento" class="form-label fw-semibold small text-secondary">
                                    <i class="fa-solid fa-id-card me-1 text-primary"></i> Documento de Identidad <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="documento" name="documento" placeholder="Ej: 12345678, V-12345678" required autocomplete="off">
                                <div class="invalid-feedback">El documento es obligatorio.</div>
                            </div>

                            <!-- Teléfono -->
                            <div class="col-md-6">
                                <label for="telefono" class="form-label fw-semibold small text-secondary">
                                    <i class="fa-solid fa-phone me-1 text-primary"></i> Teléfono
                                </label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="Ej: +58 412 1234567" autocomplete="tel">
                            </div>

                            <!-- Nombre -->
                            <div class="col-md-6">
                                <label for="nombre" class="form-label fw-semibold small text-secondary">
                                    <i class="fa-solid fa-user me-1 text-primary"></i> Nombres <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Carlos Alberto" required autocomplete="given-name">
                                <div class="invalid-feedback">El nombre es obligatorio.</div>
                            </div>

                            <!-- Apellido -->
                            <div class="col-md-6">
                                <label for="apellido" class="form-label fw-semibold small text-secondary">
                                    <i class="fa-solid fa-user-tag me-1 text-primary"></i> Apellidos <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Ej: González Pérez" required autocomplete="family-name">
                                <div class="invalid-feedback">El apellido es obligatorio.</div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold small text-secondary">
                                    <i class="fa-solid fa-envelope me-1 text-primary"></i> Correo Electrónico
                                </label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@correo.com" autocomplete="email">
                            </div>

                            <!-- Fecha de Nacimiento -->
                            <div class="col-md-6">
                                <label for="fecha_nacimiento" class="form-label fw-semibold small text-secondary">
                                    <i class="fa-solid fa-cake-candles me-1 text-primary"></i> Fecha de Nacimiento
                                </label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer bg-light py-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary fw-semibold" id="btnGuardarPaciente">
                            <span id="btnGuardarTexto"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar Paciente</span>
                            <span id="btnGuardarSpinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Pie de Página -->
    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-center text-muted small">
            <span>&copy; <?php echo date('Y'); ?> <strong>DentalCare Pro</strong> &bull; Sistema Odontológico</span>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Script AJAX para Pacientes -->
    <script src="js/pacientes.js"></script>

</body>
</html>
