<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tarjeta_id']) && isset($_POST['contenido'])) {
    $tarjeta_id = intval($_POST['tarjeta_id']);
    $contenido = trim($_POST['contenido']);
    $usuario_id = $_SESSION['usuario_id'];
    
    if ($tarjeta_id && $contenido && $usuario_id) {
        // Verificar que la tarjeta existe
        $stmt = $conn->prepare('SELECT id FROM tarjetas WHERE id = ?');
        $stmt->bind_param('i', $tarjeta_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Insertar comentario
            $stmt = $conn->prepare('INSERT INTO comentarios (contenido, tarjeta_id, usuario_id, fecha_creacion) VALUES (?, ?, ?, NOW())');
            $stmt->bind_param('sii', $contenido, $tarjeta_id, $usuario_id);
            
            if ($stmt->execute()) {
                $_SESSION['mensaje_comentario'] = 'Comentario agregado correctamente.';
                $_SESSION['mensaje_tipo'] = 'exito';
            } else {
                $_SESSION['mensaje_comentario'] = 'Error al agregar el comentario.';
                $_SESSION['mensaje_tipo'] = 'error';
            }
        } else {
            $_SESSION['mensaje_comentario'] = 'Tarjeta no encontrada.';
            $_SESSION['mensaje_tipo'] = 'error';
        }
    } else {
        $_SESSION['mensaje_comentario'] = 'Datos incompletos.';
        $_SESSION['mensaje_tipo'] = 'error';
    }
}

// Redirigir de vuelta a la vista de actividades
header('Location: dashboard.php?view=actividades-view');
exit;
?> 