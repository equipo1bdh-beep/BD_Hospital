<?php
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $tipo = $_GET['tipo'] ?? '';

    if ($tipo === 'especialidades') {
        $rows = $pdo->query("SELECT id_especialidad AS id, nom_especialidad AS nombre, precio_consulta AS precio FROM Especialidades ORDER BY nom_especialidad")->fetchAll();
        resp(true, '', $rows);
    }

    if ($tipo === 'doctores') {
        $espId = (int)($_GET['especialidad'] ?? 0);
        if ($espId <= 0) resp(false, 'Especialidad inválida.');
        $stmt = $pdo->prepare("SELECT d.id_doctor AS id, e.nombres_emp + ' ' + e.ap_paterno_emp AS nombre, d.id_especialidad AS especialidadId, ISNULL((SELECT TOP 1 o.num_sala + '-' + o.piso FROM Horario h JOIN Horario_Empleado he ON he.id_horario = h.id_horario JOIN Oficina o ON o.id_oficina = h.id_oficina WHERE he.id_empleado = d.id_empleado), 'Por definir') AS consultorio FROM Doctor d JOIN Empleado e ON e.id_empleado = d.id_empleado WHERE d.id_especialidad = ? ORDER BY e.nombres_emp, e.ap_paterno_emp");
        $stmt->execute([$espId]);
        resp(true, '', $stmt->fetchAll());
    }

    if ($tipo === 'horarios') {
        $doctorId = (int)($_GET['doctor'] ?? 0);
        $fecha = trim($_GET['fecha'] ?? '');
        if ($doctorId <= 0 || $fecha === '') resp(false, 'Datos incompletos.');
        $base = ['09:00', '10:00', '11:00', '12:00', '16:00', '17:00'];
        $stmt = $pdo->prepare("SELECT CONVERT(VARCHAR(5), hora_cita, 108) AS hora FROM Citas WHERE id_doctor = ? AND fecha_cita = ? AND id_estatusC IN (SELECT id_estatusC FROM Estatus_Cita WHERE descripcion IN ('Pendiente de pago', 'Confirmada'))");
        $stmt->execute([$doctorId, $fecha]);
        $ocupadas = array_column($stmt->fetchAll(), 'hora');
        $disponibles = array_values(array_diff($base, $ocupadas));
        resp(true, '', $disponibles);
    }

    resp(false, 'tipo no válido');
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error en catálogo: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
