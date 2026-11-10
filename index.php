<?php
/**
 * index.php
 * Punto de entrada principal: redirige al dashboard si la sesión está activa,
 * o a login.php si el usuario no ha iniciado sesión.
 * Sistema: Clínica Odontológica
 */

require_once __DIR__ . '/conexion.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
