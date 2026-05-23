<?php
require 'db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $d = json_decode(file_get_contents('php://input'), true) ?? [];
    foreach (['nombres','apPaterno','apMaterno','fechaNac','curp','genero','tipoSangre','telefono','correo','contrasena'] as $k) {
        if (empty($d[$k])) resp(false, "Campo requerido: $k");
    }

    $correo = trim($d['correo']);
    $curp = strtoupper(trim($d['curp']));
    $pdo->beginTransaction();

    $dup = $pdo->prepare('SELECT id_paciente FROM Paciente WHERE curp = ?');
    $dup->execute([$curp]);
    if ($dup->fetch()) { $pdo->rollBack(); resp(false, 'CURP ya registrada.'); }

    $dupCorreo = $pdo->prepare('SELECT id_correo FROM Cor_Paciente WHERE correo = ?');
    $dupCorreo->execute([$correo]);
    if ($dupCorreo->fetch()) { $pdo->rollBack(); resp(false, 'Correo ya registrado.'); }

    $stmt = $pdo->prepare("INSERT INTO Paciente (nombres, ap_paterno, ap_materno, tipo_sangre, fecha_nacimiento, curp, genero, contraseña) OUTPUT INSERTED.id_paciente VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([trim($d['nombres']), trim($d['apPaterno']), trim($d['apMaterno']), trim($d['tipoSangre']), $d['fechaNac'], $curp, trim($d['genero']), password_hash($d['contrasena'], PASSWORD_DEFAULT)]);
    $idPaciente = (int)$stmt->fetchColumn();

    $pdo->prepare('INSERT INTO Tel_Paciente (id_paciente, telefono) VALUES (?, ?)')->execute([$idPaciente, trim($d['telefono'])]);
    $pdo->prepare('INSERT INTO Cor_Paciente (id_paciente, correo) VALUES (?, ?)')->execute([$idPaciente, $correo]);

    $pdo->commit();
    resp(true, 'Paciente registrado.', ['idPaciente' => $idPaciente]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al registrar: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
