<?php
require "db.php";
header("Content-Type: application/json");

$d = json_decode(file_get_contents("php://input"), true);

foreach (["nombres","apPaterno","apMaterno","fechaNac","curp","genero","tipoSangre","telefono","correo","contrasena"] as $k)
    if (empty($d[$k])) resp(false, "Campo requerido: $k");

// CURP única
$dup = $pdo->prepare("SELECT id_paciente FROM Paciente WHERE curp = ?");
$dup->execute([$d["curp"]]);
if ($dup->fetch()) resp(false, "CURP ya registrada.");

// Insertar paciente y obtener ID con OUTPUT
$stmt = $pdo->prepare("
    INSERT INTO Paciente (nombres, ap_paterno, ap_materno, tipo_sangre, fecha_nacimiento, curp, genero, contraseña)
    OUTPUT INSERTED.id_paciente
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $d["nombres"], $d["apPaterno"], $d["apMaterno"],
    $d["tipoSangre"], $d["fechaNac"], strtoupper($d["curp"]),
    $d["genero"], password_hash($d["contrasena"], PASSWORD_DEFAULT)
]);
$idPaciente = (int)$stmt->fetchColumn();

// Teléfono
$pdo->prepare("INSERT INTO Tel_Paciente (id_paciente, telefono) VALUES (?, ?)")
    ->execute([$idPaciente, $d["telefono"]]);

// Correo
$pdo->prepare("INSERT INTO Cor_Paciente (id_paciente, correo) VALUES (?, ?)")
    ->execute([$idPaciente, $d["correo"]]);

resp(true, "Paciente registrado.", ["idPaciente" => $idPaciente]);
