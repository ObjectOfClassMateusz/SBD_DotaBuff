<?php
$user = "C##dota_app";
$pass = "qwerty123";
$host = "localhost/ORCL";

$db_conn = oci_connect($user, $pass, $host);

if (!$db_conn) {
    $e = oci_error();
    echo "Connection failed: " . $e['message'];
} else {
    echo "Connected to Oracle Database successfully!<br>";

    // Dodaj nazwę schematu przed tabelą: SYS.Hero
    $stmt = oci_parse($db_conn, "SELECT * FROM SYS.Hero");
    oci_execute($stmt);

    while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
        echo "ID: " . $row['ID'] . 
             ", Name: " . $row['NAME'] . 
             ", primary_attribute: " . $row['PRIMARY_ATTRIBUTE'] . "<br>";
    }
}
?>