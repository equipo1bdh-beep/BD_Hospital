<?php
$host = "localhost";
$db   = "HospitalizAdo";

try {
    $pdo = new PDO("sqlsrv:Server=$host;Database=$db", null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(["ok" => false, "msg" => "DB: " . $e->getMessage()]));
}

// Helper: respuesta JSON y terminar
function resp(bool $ok, string $msg = "", mixed $data = null): never {
    header("Content-Type: application/json");
    echo json_encode(["ok" => $ok, "msg" => $msg, "data" => $data]);
    exit;
}
