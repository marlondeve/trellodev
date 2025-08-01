<?php
$host = '193.203.166.161';
$user = 'u990790165_spacedev';
$pass = 'Platino5..';
$dbname = 'u990790165_spacedev';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Error de conexión a la base de datos: ' . $conn->connect_error);
} 