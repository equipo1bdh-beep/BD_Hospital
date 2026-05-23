<?php
require 'db.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    
    if (($_SESSION['rol'] ?? '') !== 'paciente') {
        resp(false, 'Sin sesión.');
    }

    
    $d        = json_decode(file_get_contents('php://input'), true) ?? [];
    $idCita   = (int)($d['idCita']      ?? 0);
    $montoPag = (float)($d['montoPagado'] ?? 0);

    if (!$idCita || $montoPag <= 0) {
        resp(false, 'Datos incompletos.');
    }

    $pdo->beginTransaction();

   
    $chk = $pdo->prepare("
        SELECT id_cita
        FROM Citas
        WHERE id_cita = ? AND id_paciente = ?
    ");
    $chk->execute([$idCita, $_SESSION['idPaciente']]);
    if (!$chk->fetch()) {
        $pdo->rollBack();
        resp(false, 'Cita no encontrada.');
    }

    
    $tk = $pdo->prepare("
        SELECT id_pago, monto, fecha_limite
        FROM Ticket_Cita
        WHERE id_cita = ?
    ");
    $tk->execute([$idCita]);
    $ticket = $tk->fetch();

    if (!$ticket) {
        $pdo->rollBack();
        resp(false, 'Ticket no encontrado.');
    }

    
    $estatusCita = $pdo->prepare("
        SELECT ec.descripcion
        FROM Citas c
        JOIN Estatus_Cita ec ON ec.id_estatusC = c.id_estatusC
        WHERE c.id_cita = ?
    ");
    $estatusCita->execute([$idCita]);
    $estatusActual = $estatusCita->fetchColumn();

    if ($estatusActual === 'Confirmada') {
        $pdo->rollBack();
        resp(false, 'Esta cita ya fue pagada.');
    }

    
    if (!empty($ticket['fecha_limite'])) {
        $raw = (string)$ticket['fecha_limite']; 

        
        if (strlen($raw) === 10) { 
            $raw .= ' 23:59:59';
        }

        $limiteTs = strtotime($raw);
        $ahoraTs  = time();

        if ($limiteTs !== false && $ahoraTs > $limiteTs) {
            $pdo->rollBack();
            resp(false, 'La cita venció por falta de pago en el plazo permitido.');
        }
    }

    
    $cambio = $montoPag - (float)$ticket['monto'];
    if ($cambio < 0) {
        $pdo->rollBack();
        resp(false, 'Monto insuficiente. Debes pagar $' . number_format((float)$ticket['monto'], 2));
    }

    
    $pdo->prepare("
        UPDATE Ticket_Cita
        SET
            monto_pagado  = ?,
            cambio        = ?,
            fecha_pago    = CAST(GETDATE() AS DATE),
            id_estatusTKC = (
                SELECT TOP 1 id_estatusTKC
                FROM Estatus_Ticket_Cita
                WHERE desc_estatus = 'Pagado'
            )
        WHERE id_pago = ?
    ")->execute([$montoPag, $cambio, $ticket['id_pago']]);

    
    $pdo->prepare("
        UPDATE Citas
        SET id_estatusC = (
            SELECT TOP 1 id_estatusC
            FROM Estatus_Cita
            WHERE descripcion = 'Confirmada'
        )
        WHERE id_cita = ?
    ")->execute([$idCita]);

    
    $pdo->prepare("
        INSERT INTO Bitacora_Estatus_Cita (id_cita, estatus_mov, fecha_mov, costo)
        VALUES (?, 'Confirmada', CAST(GETDATE() AS DATE), ?)
    ")->execute([$idCita, $ticket['monto']]);

    $pdo->commit();
    resp(true, 'Pago registrado.', ['cambio' => $cambio]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(
        ['ok' => false, 'msg' => 'Error al pagar: ' . $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}