<?php
// api/agendar.php  ── POST: crear cita + ticket_cita ──────────
require "db.php";
session_start();
header("Content-Type: application/json");

if (($_SESSION["rol"] ?? "") !== "paciente") resp(false, "Sesión inválida.");

$d         = json_decode(file_get_contents("php://input"), true);
$idPaciente = (int)$_SESSION["idPaciente"];
$idDoctor   = (int)($d["idDoctor"]   ?? 0);
$idEsp      = (int)($d["idEsp"]      ?? 0);
$fecha      = $d["fecha"] ?? "";
$hora       = $d["hora"]  ?? "";

if (!$idDoctor || !$idEsp || !$fecha || !$hora) resp(false, "Datos incompletos.");

// Verificar disponibilidad
$chk = $pdo->prepare("
    SELECT COUNT(*) AS n FROM Citas
    WHERE id_doctor = ? AND fecha_cita = ? AND hora_cita = ?
      AND id_estatusC IN (SELECT id_estatusC FROM Estatus_Cita WHERE descripcion IN ('Pendiente de pago','Confirmada'))
");
$chk->execute([$idDoctor, $fecha, $hora]);
if ($chk->fetch()["n"] > 0) resp(false, "Horario no disponible.");

// Obtener estatus "Pendiente de pago"
$estId = $pdo->query("SELECT TOP 1 id_estatusC FROM Estatus_Cita WHERE descripcion = 'Pendiente de pago'")->fetchColumn();
if (!$estId) {
    // Insertar si no existe
    $pdo->exec("INSERT INTO Estatus_Cita (descripcion) VALUES ('Pendiente de pago')");
    $estId = $pdo->lastInsertId();
}

$ahora = new DateTime();

// Crear cita
$pdo->prepare("
    INSERT INTO Citas (id_paciente, id_doctor, id_estatusC, fecha_asignacion, fecha_cita, hora_asignacion, hora_cita)
    VALUES (?, ?, ?, ?, ?, ?, ?)
")->execute([$idPaciente, $idDoctor, $estId, $ahora->format("Y-m-d"), $fecha, $ahora->format("H:i:s"), $hora . ":00"]);

$idCita = (int)$pdo->lastInsertId();

// Precio de consulta
$precio = (float)$pdo->prepare("SELECT precio_consulta FROM Especialidades WHERE id_especialidad = ?")
    ->execute([$idEsp]) ? $pdo->query("SELECT precio_consulta FROM Especialidades WHERE id_especialidad = $idEsp")->fetchColumn() : 0;

// Estatus ticket
$tkId = $pdo->query("SELECT TOP 1 id_estatusTKC FROM Estatus_Ticket_Cita WHERE desc_estatus = 'Pendiente'")->fetchColumn();
if (!$tkId) {
    $pdo->exec("INSERT INTO Estatus_Ticket_Cita (desc_estatus) VALUES ('Pendiente')");
    $tkId = $pdo->lastInsertId();
}

// Crear Ticket_Cita
$fechaLimite = (clone $ahora)->modify("+8 hours");
$pdo->prepare("
    INSERT INTO Ticket_Cita (id_cita, id_estatusTKC, fecha_pago, fecha_limite, monto, monto_pagado, cambio)
    VALUES (?, ?, ?, ?, ?, 0, 0)
")->execute([$idCita, $tkId, $ahora->format("Y-m-d"), $fechaLimite->format("Y-m-d"),$precio]);

// Bitácora
$pdo->prepare("
    INSERT INTO Bitacora_Estatus_Cita (id_cita, estatus_mov, fecha_mov, costo)
    VALUES (?, 'Pendiente de pago', ?, ?)
")->execute([$idCita, $ahora->format("Y-m-d"), $precio]);

resp(true, "Cita agendada.", [
    "idCita"      => $idCita,
    "folio"       => "CIT-" . str_pad($idCita, 6, "0", STR_PAD_LEFT),
    "monto"       => $precio,
    "fechaLimite" => $fechaLimite->format("d/m/Y H:i"),
]);
