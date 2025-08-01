<?php
session_start();
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $email_usuario = trim($_POST['email_usuario'] ?? '');
    $password_usuario = $_POST['password_usuario'] ?? '';
    $rol_usuario = $_POST['rol_usuario'] ?? 'miembro';
    // Validaciones
    if (!$nombre_usuario || !$email_usuario || !$password_usuario) {
        $_SESSION['mensaje_usuario'] = 'Todos los campos son obligatorios.';
        $_SESSION['mensaje_tipo'] = 'error';
    } elseif (!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje_usuario'] = 'El email no es válido.';
        $_SESSION['mensaje_tipo'] = 'error';
    } elseif (strlen($password_usuario) < 6) {
        $_SESSION['mensaje_usuario'] = 'La contraseña debe tener al menos 6 caracteres.';
        $_SESSION['mensaje_tipo'] = 'error';
    } else {
        // Validar que el email no exista
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email_usuario);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION['mensaje_usuario'] = 'El email ya está registrado. Usa otro email.';
            $_SESSION['mensaje_tipo'] = 'error';
        } else {
            $password_hash = password_hash($password_usuario, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, 1)');
            $stmt->bind_param('ssss', $nombre_usuario, $email_usuario, $password_hash, $rol_usuario);
            if ($stmt->execute()) {
                $_SESSION['mensaje_usuario'] = 'Usuario creado correctamente.';
                $_SESSION['mensaje_tipo'] = 'exito';
            } else {
                $_SESSION['mensaje_usuario'] = 'Error al crear el usuario: ' . htmlspecialchars($stmt->error);
                $_SESSION['mensaje_tipo'] = 'error';
            }
        }
    }
}
header('Location: dashboard.php?view=usuarios-view');
exit; 