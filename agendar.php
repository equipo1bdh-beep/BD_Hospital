<?php
require 'db.php';
require 'config_demo.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

try {
    if (($_SESSION['rol'] ?? '') !== 'paciente') {
        resp(false, 'Sesión inválida.');
    }

    $d          = json_decode(file_get_contents('php://input'), true) ?? [];
    $idPaciente = (int)($_SESSION['idPaciente'] ?? 0);
    $idDoctor   = (int)($d['idDoctor'] ?? 0);
    $idEsp      = (int)($d['idEsp'] ?? 0);
    $fecha      = trim($d['fecha'] ?? '');
    $hora       = trim($d['hora'] ?? '');

    if (!$idPaciente || !$idDoctor || !$idEsp || $fecha === '' || $hora === '') {
        resp(false, 'Datos incompletos.');
    }

    $fechaHoraCita = DateTime::createFromFormat('Y-m-d H:i', $fecha . ' ' . $hora);
    if (!$fechaHoraCita) {
        resp(false, 'Fecha u hora inválida.');
    }

    $ahora = new DateTime();

    
    if (MODO_DEMO) {
        $minima = (clone $ahora)->modify('+' . AGENDA_DEMO_MINUTOS . ' minutes');
        if ($fechaHoraCita < $minima) {
            resp(false, 'En modo demo la cita debe ser al menos con ' . AGENDA_DEMO_MINUTOS . ' minutos de anticipación.');
        }
    } else {
        $minima = (clone $ahora)->modify('+' . AGENDA_REAL_HORAS . ' hours');
        if ($fechaHoraCita < $minima) {
            resp(false, 'No es posible agendar una cita con menos de 48 horas de anticipación.');
        }
    }

    
    $maxima = (clone $ahora)->modify('+3 months');
    if ($fechaHoraCita > $maxima) {
        resp(false, 'No es posible agendar una cita con más de 3 meses de anticipación.');
    }

    $pdo->beginTransaction();

    
    $stmtDoc = $pdo->prepare("
        SELECT d.id_doctor
        FROM Doctor d
        WHERE d.id_doctor = ? AND d.id_especialidad = ?
    ");
    $stmtDoc->execute([$idDoctor, $idEsp]);
    if (!$stmtDoc->fetch()) {
        $pdo->rollBack();
        resp(false, 'El doctor no corresponde a la especialidad seleccionada.');
    }

    
    $stmtDup = $pdo->prepare("
        SELECT COUNT(*) AS n
        FROM Citas c
        JOIN Estatus_Cita ec ON ec.id_estatusC = c.id_estatusC
        WHERE c.id_paciente = ? AND c.id_doctor = ?
          AND c.fecha_cita = ? AND c.hora_cita = ?
          AND ec.descripcion IN ('Pendiente de pago', 'Confirmada')
    ");
    $stmtDup->execute([$idPaciente, $idDoctor, $fecha, $hora . ':00']);
    if ((int)$stmtDup->fetch()['n'] > 0) {
        $pdo->rollBack();
        resp(false, 'Ya tienes una cita activa con este doctor en ese horario.');
    }

    
    $chk = $pdo->prepare("
        SELECT COUNT(*) AS n
        FROM Citas c
        JOIN Estatus_Cita ec ON ec.id_estatusC = c.id_estatusC
        WHERE c.id_doctor = ? AND c.fecha_cita = ? AND c.hora_cita = ?
          AND ec.descripcion IN ('Pendiente de pago', 'Confirmada')
    ");
    $chk->execute([$idDoctor, $fecha, $hora . ':00']);
    if ((int)$chk->fetch()['n'] > 0) {
        $pdo->rollBack();
        resp(false, 'Horario no disponible.');
    }

    
    $estId = $pdo->query("
        SELECT TOP 1 id_estatusC
        FROM Estatus_Cita
        WHERE descripcion = 'Pendiente de pago'
    ")->fetchColumn();
    if (!$estId) {
        $pdo->rollBack();
        resp(false, 'No existe el estatus Pendiente de pago.');
    }

    
    $stmtPrecio = $pdo->prepare("
        SELECT precio_consulta
        FROM Especialidades
        WHERE id_especialidad = ?
    ");
    $stmtPrecio->execute([$idEsp]);
    $precio = (float)$stmtPrecio->fetchColumn();

    
    $tkId = $pdo->query("
        SELECT TOP 1 id_estatusTKC
        FROM Estatus_Ticket_Cita
        WHERE desc_estatus = 'Pendiente'
    ")->fetchColumn();
    if (!$tkId) {
        $pdo->rollBack();
        resp(false, 'No existe el estatus Pendiente del ticket.');
    }

    
    $fechaLimite = MODO_DEMO
        ? (clone $ahora)->modify('+' . PAGO_DEMO_MINUTOS . ' minutes')
        : (clone $ahora)->modify('+' . PAGO_REAL_HORAS . ' hours');

    /
    $stmt = $pdo->prepare("
        INSERT INTO Citas
        (id_paciente, id_doctor, id_estatusC, fecha_asignacion, fecha_cita, hora_asignacion, hora_cita)
        OUTPUT INSERTED.id_cita
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $idPaciente,
        $idDoctor,
        $estId,
        $ahora->format('Y-m-d'),
        $fecha,
        $ahora->format('H:i:s'),
        $hora . ':00'
    ]);
    $idCita = (int)$stmt->fetchColumn();

    
    $stmt2 = $pdo->prepare("
        INSERT INTO Ticket_Cita
        (id_cita, id_estatusTKC, fecha_pago, fecha_limite, monto, monto_pagado, cambio)
        OUTPUT INSERTED.id_pago
        VALUES (?, ?, ?, ?, ?, 0, 0)
    ");
    $stmt2->execute([
        $idCita,
        $tkId,
        $ahora->format('Y-m-d'),
        $fechaLimite->format('Y-m-d H:i:s'), 
        $precio
    ]);

    
    $pdo->prepare("
        INSERT INTO Bitacora_Estatus_Cita (id_cita, estatus_mov, fecha_mov, costo)
        VALUES (?, 'Pendiente de pago', CAST(GETDATE() AS DATE), ?)
    ")->execute([$idCita, $precio]);

    
    $stmtInfo = $pdo->prepare("
        SELECT
            s.nom_especialidad AS especialidad,
            e.nombres_emp + ' ' + e.ap_paterno_emp AS doctor,
            ISNULL(
                (
                    SELECT TOP 1 o.num_sala + '-' + o.piso
                    FROM Horario h
                    INNER JOIN Horario_Empleado he ON he.id_horario = h.id_horario
                    INNER JOIN Oficina o ON o.id_oficina = h.id_oficina
                    WHERE he.id_empleado = d.id_empleado
                ),
                'Por definir'
            ) AS consultorio
        FROM Doctor d
        INNER JOIN Empleado e ON e.id_empleado = d.id_empleado
        INNER JOIN Especialidades s ON s.id_especialidad = d.id_especialidad
        WHERE d.id_doctor = ?
    ");
    $stmtInfo->execute([$idDoctor]);

    $info = $stmtInfo->fetch() ?: [
        'especialidad' => '',
        'doctor'       => '',
        'consultorio'  => 'Por definir'
    ];

    $pdo->commit();

    resp(true, 'Cita agendada.', [
        'idCita'      => $idCita,
        'folio'       => 'CIT-' . str_pad((string)$idCita, 6, '0', STR_PAD_LEFT),
        'paciente'    => $_SESSION['nombre'] ?? 'Paciente',
        'especialidad'=> $info['especialidad'],
        'doctor'      => $info['doctor'],
        'consultorio' => $info['consultorio'],
        'fecha'       => $fecha,
        'hora'        => $hora,
        'monto'       => $precio,
        'fechaLimite' => $fechaLimite->format('d/m/Y H:i'),
        'modoDemo'    => MODO_DEMO
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(
        ['ok' => false, 'msg' => 'Error al agendar: ' . $e->getMessage()],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}