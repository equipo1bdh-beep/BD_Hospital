<?php
require 'db.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    if (($_SESSION['rol'] ?? '') !== 'paciente') {
        resp(false, 'Sin sesión.');
    }

    $idPaciente = (int)$_SESSION['idPaciente'];
    $stmt = $pdo->prepare("SELECT 'CIT-' + RIGHT('000000' + CAST(c.id_cita AS VARCHAR(6)), 6) AS folio, c.id_cita, s.nom_especialidad AS especialidad, e.nombres_emp + ' ' + e.ap_paterno_emp AS doctor, CONVERT(VARCHAR(10), c.fecha_cita, 23) AS fecha, CONVERT(VARCHAR(5), c.hora_cita, 108) AS hora, ISNULL((SELECT TOP 1 o.num_sala + '-' + o.piso FROM Horario h INNER JOIN Horario_Empleado he ON he.id_horario = h.id_horario INNER JOIN Oficina o ON o.id_oficina = h.id_oficina WHERE he.id_empleado = d.id_empleado), 'Por definir') AS consultorio, ISNULL(tc.monto, 0) AS monto, CONVERT(VARCHAR(16), tc.fecha_limite, 120) AS fechaLimite, ec.descripcion AS estatus FROM Citas c INNER JOIN Doctor d ON d.id_doctor = c.id_doctor INNER JOIN Empleado e ON e.id_empleado = d.id_empleado INNER JOIN Especialidades s ON s.id_especialidad = d.id_especialidad INNER JOIN Estatus_Cita ec ON ec.id_estatusC = c.id_estatusC LEFT JOIN Ticket_Cita tc ON tc.id_cita = c.id_cita WHERE c.id_paciente = ? ORDER BY c.fecha_cita DESC, c.hora_cita DESC");
    $stmt->execute([$idPaciente]);
    resp(true, '', $stmt->fetchAll());
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al cargar citas: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
