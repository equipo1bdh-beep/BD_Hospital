```php
<?php

$serverName = "localhost";
$connectionOptions = array(
    "Database" => "HospitalizAdo",
    "TrustServerCertificate" => true
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

?>
```
