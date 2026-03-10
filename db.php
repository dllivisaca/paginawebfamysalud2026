<?php

$host = "localhost";
$dbname = "pagina_web_famysalud";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname, 3307);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");