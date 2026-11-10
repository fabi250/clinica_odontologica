<?php
/**
 * dashboard.php
 * Panel de administración principal protegido por sesión.
 * Sistema: Clínica Odontológica
 */

// 1. Conexión a la base de datos y sesión segura
require_once __DIR__ . '/conexion.php';

// 2. Proteger la vista: verificar si existe una sesión de usuario activa
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario autenticado
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuarioEmail  = $_SESSION['usuario_email'] ?? '';

// 3. Consultar las 3 métricas contadoras requeridas desde la base de datos
try {
    // Tarjeta 1: Total Pacientes
    $stmtPacientes = $pdo->query("SELECT COUNT(*) FROM pacientes");
    $totalPacientes = (int)$stmtPacientes->fetchColumn();

    // Tarjeta 2: Procedimientos disponibles
    $stmtProcedimientos = $pdo->query("SELECT COUNT(*) FROM procedimientos");
    $totalProcedimientos = (int)$stmtProcedimientos->fetchColumn();

    // Tarjeta 3: Total Ingresos (suma de costo_aplicado en historias clínicas)
    $stmtIngresos = $pdo->query("SELECT COALESCE(SUM(costo_aplicado), 0) FROM historias_clinicas");
    $totalIngresos = (float)$stmtIngresos->fetchColumn();

    // Lista de procedimientos para visualización complementaria
    $stmtListaProc = $pdo->query("SELECT id, nombre, descripcion, costo FROM procedimientos ORDER BY id ASC");
    $listaProcedimientos = $stmtListaProc->fetchAll();
} catch (PDOException $e) {
    error_log("Error al consultar métricas del dashboard: " . $e->getMessage());
    $totalPacientes = 0;
    $totalProcedimientos = 0;
    $totalIngresos = 0.00;
    $listaProcedimientos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control | Clínica Odontológica</title>

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
        .metric-card {
            border: none;
            border-radius: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.08) !important;
        }
        .metric-icon-box {
            width: 58px;
            height: 58px;
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <!-- Barra de Navegación Principal del Dashboard -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="dashboard.php">
                <i class="fa-solid fa-tooth text-info fa-lg"></i>
                <span>DentalCare Pro</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardNavbar" aria-controls="dashboardNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="dashboardNavbar">
                <!-- Menú requerido: Inicio, Pacientes, Procedimientos, Historia Clínica, Salir -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" aria-current="page" href="dashboard.php">
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
                        <a class="nav-link" href="historia_clinica.php">
                            <i class="fa-solid fa-clipboard-user me-1"></i> Historia Clínica
                        </a>
                    </li>
                </ul>

                <!-- Usuario y Opción de Salir -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-circle-user fa-xl text-info"></i>
                            <span class="fw-semibold"><?php echo htmlspecialchars($usuarioNombre); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li class="px-3 py-1">
                                <small class="text-muted d-block">Sesión iniciada como:</small>
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

            <!-- Saludo al usuario autenticado -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Panel Principal</h2>
                    <p class="text-muted mb-0">Bienvenido de nuevo, <strong><?php echo htmlspecialchars($usuarioNombre); ?></strong></p>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-2 px-3 rounded-pill">
                        <i class="fa-solid fa-shield-halved me-1"></i> Sesión Segura Verificada
                    </span>
                </div>
            </div>

            <!-- Las 3 Tarjetas Contadoras Requeridas -->
            <div class="row g-4 mb-4">
                
                <!-- Tarjeta 1: Total Pacientes -->
                <div class="col-md-4">
                    <div class="card metric-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="metric-icon-box bg-primary bg-opacity-10 text-primary me-3 flex-shrink-0">
                                <i class="fa-solid fa-hospital-user fa-2x"></i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-muted text-uppercase small fw-bold">Total Pacientes</span>
                                <h2 class="fw-bold text-dark my-1"><?php echo number_format($totalPacientes); ?></h2>
                                <small class="text-primary fw-semibold">
                                    <i class="fa-solid fa-user-check me-1"></i> Registrados en sistema
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 2: Procedimientos -->
                <div class="col-md-4">
                    <div class="card metric-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="metric-icon-box bg-info bg-opacity-10 text-info me-3 flex-shrink-0">
                                <i class="fa-solid fa-teeth fa-2x"></i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-muted text-uppercase small fw-bold">Procedimientos</span>
                                <h2 class="fw-bold text-dark my-1"><?php echo number_format($totalProcedimientos); ?></h2>
                                <small class="text-info fw-semibold">
                                    <i class="fa-solid fa-stethoscope me-1"></i> Servicios disponibles
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 3: Ingresos -->
                <div class="col-md-4">
                    <div class="card metric-card shadow-sm h-100">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="metric-icon-box bg-success bg-opacity-10 text-success me-3 flex-shrink-0">
                                <i class="fa-solid fa-sack-dollar fa-2x"></i>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-muted text-uppercase small fw-bold">Ingresos Totales</span>
                                <h2 class="fw-bold text-dark my-1">$<?php echo number_format($totalIngresos, 2); ?></h2>
                                <small class="text-success fw-semibold">
                                    <i class="fa-solid fa-chart-line me-1"></i> Historias clínicas cobradas
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tabla de Procedimientos Registrados en la Base de Datos -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-list-check me-2 text-primary"></i>Catálogo de Procedimientos Odontológicos
                    </h5>
                    <span class="badge bg-secondary"><?php echo count($listaProcedimientos); ?> disponibles</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 80px;"># ID</th>
                                    <th>Procedimiento</th>
                                    <th>Descripción</th>
                                    <th class="text-end pe-4">Costo Estimado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($listaProcedimientos)): ?>
                                    <?php foreach ($listaProcedimientos as $proc): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-muted"><?php echo htmlspecialchars($proc['id']); ?></td>
                                            <td class="fw-semibold text-primary">
                                                <i class="fa-solid fa-tooth me-1 text-secondary"></i>
                                                <?php echo htmlspecialchars($proc['nombre']); ?>
                                            </td>
                                            <td class="text-muted"><?php echo htmlspecialchars($proc['descripcion']); ?></td>
                                            <td class="text-end pe-4 fw-bold text-success">
                                                $<?php echo number_format($proc['costo'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No se encontraron procedimientos registrados.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Pie de Página -->
    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-center text-muted small">
            <span>&copy; <?php echo date('Y'); ?> <strong>DentalCare Pro</strong> &bull; Panel Administrativo Odontológico</span>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>
