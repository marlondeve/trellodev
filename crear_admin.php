<?php
// Script para crear un usuario admin en la base de datos
// Elimina este archivo después de usarlo por seguridad

$host = '193.203.166.161';
$user = 'u990790165_spacedev';
$pass = 'Platino5..';
$dbname = 'u990790165_spacedev';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Error de conexión a la base de datos: ' . $conn->connect_error);
}

$nombre = 'Admin MeritumDev';
$email = 'admin@meritumdev.com';
$password = 'Platino5..'; // Cambia esta contraseña después de crear el usuario
$rol = 'admin';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "El usuario admin ya existe.";
} else {
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, 1)');
    $stmt->bind_param('ssss', $nombre, $email, $password_hash, $rol);
    if ($stmt->execute()) {
        echo "Usuario admin creado correctamente.<br>Email: $email<br>Contraseña: $password";
    } else {
        echo "Error al crear el usuario: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
?> 