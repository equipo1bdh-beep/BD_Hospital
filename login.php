```php id="dk4hks"
<?php

session_start();

include("conexion.php");

$curp = $_POST['curp'];
$contrasena = $_POST['contrasena'];

$sql = "SELECT * FROM Paciente
WHERE curp = ? AND contraseña = ?";

$params = array($curp, $contrasena);

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$usuario = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($usuario) {

    $_SESSION['paciente'] = $usuario['id_paciente'];

    echo json_encode([
        "success" => true,
        "nombre" => $usuario['nombres'],
        "id" => $usuario['id_paciente']
    ]);

} else {

    echo json_encode([
        "success" => false
    ]);
}

?>
```
