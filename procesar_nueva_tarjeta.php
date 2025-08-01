<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    die('No autorizado');
}

// Procesar creación de tarjeta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tarjeta'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
    $lista_id = intval($_POST['estado'] ?? 0);
    $responsable = intval($_POST['responsable'] ?? 0);
    $etiquetas_seleccionadas = $_POST['etiquetas'] ?? [];
    $creado_por = $_SESSION['usuario_id'] ?? null;
    
    $mensaje_actividad = '';
    
    if ($titulo && $lista_id && $creado_por) {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Insertar tarjeta
            $stmt = $conn->prepare('INSERT INTO tarjetas (titulo, descripcion, fecha_vencimiento, lista_id, creado_por, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->bind_param('sssii', $titulo, $descripcion, $fecha_vencimiento, $lista_id, $creado_por);
            
            if ($stmt->execute()) {
                $tarjeta_id = $conn->insert_id;
                
                // Asignar responsable si se seleccionó
                if ($responsable) {
                    $stmt = $conn->prepare('INSERT INTO tarjeta_usuarios (tarjeta_id, usuario_id) VALUES (?, ?)');
                    $stmt->bind_param('ii', $tarjeta_id, $responsable);
                    $stmt->execute();
                }
                
                // Asignar etiquetas si se seleccionaron
                if (!empty($etiquetas_seleccionadas)) {
                    $stmt = $conn->prepare('INSERT INTO tarjeta_etiquetas (tarjeta_id, etiqueta_id) VALUES (?, ?)');
                    foreach ($etiquetas_seleccionadas as $etiqueta_id) {
                        $stmt->bind_param('ii', $tarjeta_id, $etiqueta_id);
                        $stmt->execute();
                    }
                }
                
                $conn->commit();
                $mensaje_actividad = '<div class="mb-4 text-green-600 font-bold">Tarjeta creada correctamente.</div>';
                echo '<script>window.location.href="dashboard.php?view=actividades-view";</script>';
                exit;
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje_actividad = '<div class="mb-4 text-red-600 font-bold">Error al crear la tarjeta: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $mensaje_actividad = '<div class="mb-4 text-red-600 font-bold">Todos los campos obligatorios deben estar completos.</div>';
    }
    
    if ($mensaje_actividad) {
        echo $mensaje_actividad;
    }
}
?> 