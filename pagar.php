<?php
// api/pagar.php  ── POST: registrar pago de cita ─────────────
require "db.php";
session_start();
header("Content-Type: application/json");

if (($_SESSION["rol"] ?? "") !== "paciente") resp(false, "Sin sesión.");

$d       = json_decode(file_get_contents("php://input"), true);
$idCita  = (int)($d["idCita"]     ?? 0);
$montoPag= (float)($d["montoPagado"] ?? 0);

if (!$idCita || $montoPag <= 0) resp(false, "Datos incompletos.");

// Verificar que la cita pertenece al paciente
$chk = $pdo->prepare("SELECT id_cita FROM Citas WHERE id_cita = ? AND id_paciente = ?");
$chk->execute([$idCita, $_SESSION["idPaciente"]]);
if (!$chk->fetch()) resp(false, "Cita no encontrada.");

// Obtener ticket
$tk = $pdo->prepare("SELECT id_pago, monto FROM Ticket_Cita WHERE id_cita = ?");
$tk->execute([$idCita]);
$ticket = $tk->fetch();
if (!$ticket) resp(false, "Ticket no encontrado.");

$cambio = $montoPag - $ticket["monto"];
if ($cambio < 0) resp(false, "Monto insuficiente. Debes pagar $" . number_format($ticket["monto"], 2));

// Actualizar ticket
$pdo->prepare("
    UPDATE Ticket_Cita
    SET monto_pagado = ?, cambio = ?, fecha_pago = GETDATE(),
        id_estatusTKC = (SELECT TOP 1 id_estatusTKC FROM Estatus_Ticket_Cita WHERE desc_estatus = 'Pagado')
    WHERE id_pago = ?
")->execute([$montoPag, $cambio, $ticket["id_pago"]]);

// Actualizar estatus de cita a "Confirmada"
$pdo->prepare("
    UPDATE Citas SET id_estatusC =
        (SELECT TOP 1 id_estatusC FROM Estatus_Cita WHERE descripcion = 'Confirmada')
    WHERE id_cita = ?
")->execute([$idCita]);

// Bitácora
$pdo->prepare("
    INSERT INTO Bitacora_Estatus_Cita (id_cita, estatus_mov, fecha_mov, costo)
    VALUES (?, 'Confirmada', CAST(GETDATE() AS DATE), ?)
")->execute([$idCita, $ticket["monto"]]);

resp(true, "Pago registrado.", ["cambio" => $cambio]);
