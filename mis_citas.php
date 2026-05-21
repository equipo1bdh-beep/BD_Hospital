<?php
// api/mis_citas.php  ── GET: citas del paciente en sesión ─────
require "db.php";
session_start();
header("Content-Type: application/json");

if (($_SESSION["rol"] ?? "") !== "paciente") resp(false, "Sin sesión.");

$idPaciente = (int)$_SESSION["idPaciente"];

$stmt = $pdo->prepare("
    SELECT
        'CIT-' + RIGHT('000000' + CAST(c.id_cita AS VARCHAR), 6) AS folio,
        c.id_cita,
        s.nom_especialidad  AS especialidad,
        e.nombres_emp + ' ' + e.ap_paterno_emp AS doctor,
        CONVERT(VARCHAR(10), c.fecha_cita, 23) AS fecha,
        CONVERT(VARCHAR(5),  c.hora_cita,  108) AS hora,
        ISNULL(o.num_sala + '-' + o.piso, 'N/A') AS consultorio,
        tc.monto,
        ec.descripcion AS estatus
    FROM Citas c
    JOIN Doctor d         ON d.id_doctor        = c.id_doctor
    JOIN Empleado e       ON e.id_empleado       = d.id_empleado
    JOIN Especialidades s ON s.id_especialidad   = d.id_especialidad
    JOIN Estatus_Cita ec  ON ec.id_estatusC      = c.id_estatusC
    LEFT JOIN Ticket_Cita tc ON tc.id_cita       = c.id_cita
    LEFT JOIN Oficina o ON o.id_oficina = (
        SELECT TOP 1 h.id_oficina FROM Horario h
        JOIN Horario_Empleado he ON he.id_horario = h.id_horario
        WHERE he.id_empleado = d.id_empleado
    )
    WHERE c.id_paciente = ?
    ORDER BY c.fecha_cita DESC, c.hora_cita DESC
");
$stmt->execute([$idPaciente]);
resp(true, "", $stmt->fetchAll());
