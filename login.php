<?php
// api/login.php  ── POST: inicio de sesión ───────────────────
require "db.php";
session_start();
header("Content-Type: application/json");

$d   = json_decode(file_get_contents("php://input"), true);
$rol = $d["rol"]    ?? "";
$correo = $d["correo"] ?? "";
$pass   = $d["contrasena"] ?? "";

if (!$rol || !$correo || !$pass) resp(false, "Datos incompletos.");

// ── Paciente ─────────────────────────────────────────────────
if ($rol === "paciente") {
    $stmt = $pdo->prepare("
        SELECT p.id_paciente, p.nombres, p.ap_paterno, p.ap_materno, p.contraseña,
               c.correo
        FROM Paciente p
        JOIN Cor_Paciente c ON c.id_paciente = p.id_paciente
        WHERE c.correo = ?
    ");
    $stmt->execute([$correo]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($pass, $u["contraseña"]))
        resp(false, "Correo o contraseña incorrectos.");

    $_SESSION["rol"]        = "paciente";
    $_SESSION["idPaciente"] = $u["id_paciente"];
    $_SESSION["nombre"]     = "{$u["nombres"]} {$u["ap_paterno"]} {$u["ap_materno"]}";
    $_SESSION["correo"]     = $u["correo"];

    resp(true, "Bienvenido", [
        "rol"       => "paciente",
        "idPaciente"=> $u["id_paciente"],
        "nombre"    => $_SESSION["nombre"],
        "correo"    => $u["correo"],
    ]);
}

// ── Doctor / Recepcionista (via Empleado + Correo_Empleado) ──
if (in_array($rol, ["doctor", "recepcionista"])) {
    $stmt = $pdo->prepare("
        SELECT e.id_empleado, e.nombres_emp, e.ap_paterno_emp, e.contraseña,
               ce.correo, te.cargo
        FROM Empleado e
        JOIN Correo_Empleado ce ON ce.id_empleado = e.id_empleado
        JOIN Tipo_Empleado te   ON te.id_tipo_empleado = e.id_tipo_empleado
        WHERE ce.correo = ?
    ");
    $stmt->execute([$correo]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($pass, $u["contraseña"]))
        resp(false, "Correo o contraseña incorrectos.");

    $_SESSION["rol"]         = strtolower($u["cargo"]);
    $_SESSION["idEmpleado"]  = $u["id_empleado"];
    $_SESSION["nombre"]      = "{$u["nombres_emp"]} {$u["ap_paterno_emp"]}";
    $_SESSION["correo"]      = $u["correo"];

    resp(true, "Bienvenido", [
        "rol"      => $_SESSION["rol"],
        "nombre"   => $_SESSION["nombre"],
        "correo"   => $_SESSION["correo"],
    ]);
}

resp(false, "Rol no reconocido.");
