<?php
// api/catalogo.php  ── GET: especialidades y doctores ─────────
require "db.php";
header("Content-Type: application/json");

$tipo = $_GET["tipo"] ?? "";

// ── Especialidades ────────────────────────────────────────────
if ($tipo === "especialidades") {
    $rows = $pdo->query("
        SELECT id_especialidad AS id, nom_especialidad AS nombre,
               precio_consulta AS precio
        FROM Especialidades
    ")->fetchAll();
    resp(true, "", $rows);
}

// ── Doctores por especialidad ─────────────────────────────────
if ($tipo === "doctores") {
    $espId = (int)($_GET["especialidad"] ?? 0);
    $stmt  = $pdo->prepare("
        SELECT d.id_doctor AS id,
               e.nombres_emp + ' ' + e.ap_paterno_emp AS nombre,
               o.num_sala + '-' + o.piso AS consultorio
        FROM Doctor d
        JOIN Empleado e     ON e.id_empleado   = d.id_empleado
        JOIN Especialidades s ON s.id_especialidad = d.id_especialidad
        LEFT JOIN Oficina o ON o.id_oficina = (
            SELECT TOP 1 h.id_oficina FROM Horario h
            JOIN Horario_Empleado he ON he.id_horario = h.id_horario
            WHERE he.id_empleado = d.id_empleado
        )
        WHERE d.id_especialidad = ?
    ");
    $stmt->execute([$espId]);
    resp(true, "", $stmt->fetchAll());
}

// ── Horarios disponibles ──────────────────────────────────────
if ($tipo === "horarios") {
    $doctorId = (int)($_GET["doctor"] ?? 0);
    $fecha    = $_GET["fecha"] ?? "";

    // Horas base fijas
    $base = ["09:00","10:00","11:00","12:00","16:00","17:00"];

    // Horas ya ocupadas ese día
    $stmt = $pdo->prepare("
        SELECT CONVERT(VARCHAR(5), hora_cita, 108) AS hora
        FROM Citas
        WHERE id_doctor = ? AND fecha_cita = ?
          AND id_estatusC IN (
              SELECT id_estatusC FROM Estatus_Cita
              WHERE descripcion IN ('Pendiente de pago','Confirmada')
          )
    ");
    $stmt->execute([$doctorId, $fecha]);
    $ocupadas = array_column($stmt->fetchAll(), "hora");

    $disponibles = array_values(array_diff($base, $ocupadas));
    resp(true, "", $disponibles);
}

resp(false, "tipo no válido");
