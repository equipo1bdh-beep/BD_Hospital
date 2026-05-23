<?php
require 'db.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol'])) {
    resp(false, 'Sin sesión.');
}

resp(true, 'Sesión activa', [
    'rol' => $_SESSION['rol'],
    'idPaciente' => $_SESSION['idPaciente'] ?? null,
    'idEmpleado' => $_SESSION['idEmpleado'] ?? null,
    'nombre' => $_SESSION['nombre'] ?? '',
    'correo' => $_SESSION['correo'] ?? ''
]);
