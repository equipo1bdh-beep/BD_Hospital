```php
<?php

include("conexion.php");

$nombres = $_POST['nombres'];
$ap_paterno = $_POST['ap_paterno'];
$ap_materno = $_POST['ap_materno'];
$tipo_sangre = $_POST['tipo_sangre'];
$fecha_nacimiento = $_POST['fecha_nacimiento'];
$curp = $_POST['curp'];
$genero = $_POST['genero'];
$contrasena = $_POST['contrasena'];

$sql = "INSERT INTO Paciente
(nombres, ap_paterno, ap_materno, tipo_sangre, fecha_nacimiento, curp, genero, contraseña)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$params = array(
    $nombres,
    $ap_paterno,
    $ap_materno,
    $tipo_sangre,
    $fecha_nacimiento,
    $curp,
    $genero,
    $contrasena
);

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    echo "Registro exitoso";
} else {
    die(print_r(sqlsrv_errors(), true));
}

?>
```
