<?php
require 'db.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    $d = json_decode(file_get_contents('php://input'), true) ?? [];
    $correo = trim($d['correo'] ?? '');
    $pass = $d['contrasena'] ?? '';
    $rol = strtolower(trim($d['rol'] ?? ''));

    if ($correo === '' || $pass === '' || $rol === '') {
        resp(false, 'Datos incompletos.');
    }

    if ($rol === 'paciente') {
        $stmt = $pdo->prepare("SELECT p.id_paciente, p.nombres, p.ap_paterno, p.ap_materno, p.contraseña, c.correo FROM Paciente p JOIN Cor_Paciente c ON c.id_paciente = p.id_paciente WHERE c.correo = ?");
        $stmt->execute([$correo]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($pass, $u['contraseña'])) {
            resp(false, 'Correo o contraseña incorrectos.');
        }

        $_SESSION['rol'] = 'paciente';
        $_SESSION['idPaciente'] = (int)$u['id_paciente'];
        $_SESSION['nombre'] = trim($u['nombres'] . ' ' . $u['ap_paterno'] . ' ' . $u['ap_materno']);
        $_SESSION['correo'] = $u['correo'];

        resp(true, 'Bienvenido', [
            'rol' => 'paciente',
            'idPaciente' => $_SESSION['idPaciente'],
            'nombre' => $_SESSION['nombre'],
            'correo' => $_SESSION['correo'],
        ]);
    }

    if (in_array($rol, ['doctor', 'recepcionista'], true)) {
        $stmt = $pdo->prepare("SELECT e.id_empleado, e.nombres_emp, e.ap_paterno_emp, e.ap_materno_emp, e.contraseña, ce.correo, te.cargo FROM Empleado e JOIN Correo_Empleado ce ON ce.id_empleado = e.id_empleado JOIN Tipo_Empleado te ON te.id_tipo_empleado = e.id_tipo_empleado WHERE ce.correo = ?");
        $stmt->execute([$correo]);
        $u = $stmt->fetch();
        if (!$u || strtolower($u['cargo']) !== $rol || !password_verify($pass, $u['contraseña'])) {
            resp(false, 'Correo o contraseña incorrectos.');
        }

        $_SESSION['rol'] = strtolower($u['cargo']);
        $_SESSION['idEmpleado'] = (int)$u['id_empleado'];
        $_SESSION['nombre'] = trim($u['nombres_emp'] . ' ' . $u['ap_paterno_emp'] . ' ' . $u['ap_materno_emp']);
        $_SESSION['correo'] = $u['correo'];

        resp(true, 'Bienvenido', [
            'rol' => $_SESSION['rol'],
            'idEmpleado' => $_SESSION['idEmpleado'],
            'nombre' => $_SESSION['nombre'],
            'correo' => $_SESSION['correo'],
        ]);
    }

    resp(false, 'Rol no reconocido.');
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error en login: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
