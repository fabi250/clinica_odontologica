<?php
/**
 * login.php
 * Formulario de inicio de sesión centrado con Bootstrap 5 y FontAwesome.
 * Maneja el envío asíncrono con JavaScript Fetch hacia auth.php.
 * Sistema: Clínica Odontológica
 */

// Iniciar sesión para comprobar si ya existe sesión activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirigir al dashboard si ya está autenticado
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Clínica Odontológica</title>

    <!-- Bootstrap 5.3.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- FontAwesome 6.5.2 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <style>
        body {
            background: linear-gradient(135deg, #e0f2fe 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            max-width: 440px;
            width: 100%;
            overflow: hidden;
        }
        .login-header-icon {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-4">

    <div class="container d-flex justify-content-center">
        <div class="card login-card shadow-lg">
            <div class="card-body p-4 p-md-5">

                <!-- Logotipo / Encabezado -->
                <div class="text-center mb-4">
                    <div class="login-header-icon mb-3">
                        <i class="fa-solid fa-tooth fa-2x"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">DentalCare Pro</h3>
                    <p class="text-muted small">Ingresa tus credenciales para acceder al sistema</p>
                </div>

                <!-- Mensaje de Alerta Dinámico -->
                <div id="alertContainer"></div>

                <!-- Formulario de Acceso -->
                <form id="loginForm" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold text-secondary small">
                            <i class="fa-solid fa-envelope me-1"></i> Correo Electrónico
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-at"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@clinica.com" required autocomplete="email" autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold text-secondary small">
                            <i class="fa-solid fa-lock me-1"></i> Contraseña
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Mostrar/ocultar contraseña">
                                <i class="fa-regular fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label small text-muted" for="rememberMe">
                                Recordarme
                            </label>
                        </div>
                        <a href="#" class="small text-decoration-none text-primary" onclick="alert('Contacta al administrador del sistema para restablecer tu clave.'); return false;">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm fw-semibold" id="submitBtn">
                            <span id="btnText"><i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar Sesión</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>

                    <!-- Datos de prueba para el usuario -->
                    <div class="alert alert-info py-2 px-3 mb-0 small text-center rounded-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        <strong>Usuario Demo:</strong> <code>admin@clinica.com</code> | <strong>Clave:</strong> <code>admin123</code>
                    </div>
                </form>

            </div>
            <div class="card-footer bg-light py-3 text-center border-0">
                <small class="text-muted">&copy; <?php echo date('Y'); ?> DentalCare Pro &bull; Gestión Odontológica</small>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3.3 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Script de autenticación con Fetch -->
    <script>
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const alertContainer = document.getElementById('alertContainer');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const togglePassword = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eyeIcon');

        // Mostrar / Ocultar Contraseña
        togglePassword.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeIcon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });

        // Mostrar Alertas
        function showAlert(message, type = 'danger') {
            const iconClass = type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation';
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="fa-solid ${iconClass}"></i>
                    <div>${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }

        // Manejar el envío del formulario mediante Fetch
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertContainer.innerHTML = '';

            const email = emailInput.value.trim();
            const password = passwordInput.value;

            // Validación del lado del cliente
            if (!email || !password) {
                showAlert('Por favor ingresa tanto el correo como la contraseña.', 'warning');
                return;
            }

            // Cambiar estado del botón a carga
            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');

            try {
                // Envío asíncrono vía POST Fetch a auth.php
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: email, password: password })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showAlert('¡Acceso concedido! Redirigiendo al panel...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 800);
                } else {
                    showAlert(data.message || 'Error de autenticación.', 'danger');
                    submitBtn.disabled = false;
                    btnText.classList.remove('d-none');
                    btnSpinner.classList.add('d-none');
                }
            } catch (error) {
                showAlert('No se pudo establecer conexión con el servidor de autenticación.', 'danger');
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');
            }
        });
    </script>
</body>
</html>
