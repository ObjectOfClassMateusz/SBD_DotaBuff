<?php
$user    = "C##dota_app";
$pass    = "qwerty123";
$host    = "localhost/ORCL";
$db_conn = oci_connect($user, $pass, $host);

if (!$db_conn) {
    $oci_err = oci_error();
    $db_error = $oci_err['message'];
} else {
    $db_error = null;
}

/**
 * Helper: run a SELECT and return all rows as assoc array.
 */
function db_query($conn, $sql, $binds = []) 
{
    $stmt = oci_parse($conn, $sql);
    foreach ($binds as $key => $val) 
    {
        oci_bind_by_name($stmt, $key, $binds[$key]);
    }
    oci_execute($stmt);
    $rows = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Helper: return single scalar value from query.
 */
function db_scalar($conn, $sql) 
{
    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);
    $row = oci_fetch_array($stmt, OCI_NUM);
    return $row ? $row[0] : 0;
}
?>
