<?php
// Asegurar que se devuelva JSON
header('Content-Type: application/json');

// Manejo de errores para evitar que se generen errores de PHP
error_reporting(0);
ini_set('display_errors', 0);

try {
    session_start();
    require_once 'conexion.php';

    // Verificar si el usuario está logueado
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }

    // Función para verificar y corregir la estructura de la tabla eventos
    function verificarEstructuraTablaEventos($conn) {
        // Crear tabla eventos si no existe
        $sql_crear_tabla = "CREATE TABLE IF NOT EXISTS eventos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descripcion TEXT,
            fecha_hora DATETIME NOT NULL,
            tipo ENUM('reunion', 'tarea', 'recordatorio', 'evento') DEFAULT 'evento',
            creado_por INT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE CASCADE
        )";
        
        if (!$conn->query($sql_crear_tabla)) {
            return false;
        }
        
        // Verificar y agregar columnas faltantes
        $columnas_requeridas = [
            'fecha_hora' => "ALTER TABLE eventos ADD COLUMN fecha_hora DATETIME NOT NULL AFTER descripcion",
            'tipo' => "ALTER TABLE eventos ADD COLUMN tipo ENUM('reunion', 'tarea', 'recordatorio', 'evento') DEFAULT 'evento' AFTER fecha_hora",
            'creado_por' => "ALTER TABLE eventos ADD COLUMN creado_por INT NOT NULL AFTER tipo",
            'fecha_creacion' => "ALTER TABLE eventos ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER creado_por"
        ];
        
        foreach ($columnas_requeridas as $columna => $sql_alter) {
            $result = $conn->query("SHOW COLUMNS FROM eventos LIKE '$columna'");
            if ($result->num_rows == 0) {
                // La columna no existe, agregarla
                if (!$conn->query($sql_alter)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    // Procesar creación de evento
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_evento'])) {
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $tipo = $_POST['tipo'] ?? 'evento';
        $creado_por = $_SESSION['usuario_id'];
        
        if (empty($titulo) || empty($fecha) || empty($hora)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben estar completos']);
            exit;
        }
        
        // Verificar y corregir estructura de la tabla
        if (!verificarEstructuraTablaEventos($conn)) {
            echo json_encode(['success' => false, 'message' => 'Error al verificar la estructura de la tabla de eventos']);
            exit;
        }
        
        // Combinar fecha y hora
        $fecha_hora = $fecha . ' ' . $hora . ':00';
        
        // Insertar evento
        $stmt = $conn->prepare('INSERT INTO eventos (titulo, descripcion, fecha_hora, tipo, creado_por) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param('ssssi', $titulo, $descripcion, $fecha_hora, $tipo, $creado_por);
        
        if ($stmt->execute()) {
            $evento_id = $conn->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Evento creado correctamente',
                'evento_id' => $evento_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear el evento: ' . $stmt->error]);
        }
        
        $stmt->close();
        exit;
    }

    // Obtener eventos del mes
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['obtener_eventos'])) {
        $mes = intval($_GET['mes'] ?? date('n'));
        $año = intval($_GET['año'] ?? date('Y'));
        
        // Verificar y corregir estructura de la tabla
        if (!verificarEstructuraTablaEventos($conn)) {
            echo json_encode(['success' => true, 'eventos' => []]);
            exit;
        }
        
        $fecha_inicio = sprintf('%04d-%02d-01', $año, $mes);
        $fecha_fin = sprintf('%04d-%02d-%02d', $año, $mes, date('t', mktime(0, 0, 0, $mes, 1, $año)));
        
        $stmt = $conn->prepare('
            SELECT e.*, u.nombre as creador_nombre 
            FROM eventos e 
            JOIN usuarios u ON e.creado_por = u.id 
            WHERE DATE(e.fecha_hora) BETWEEN ? AND ? 
            ORDER BY e.fecha_hora ASC
        ');
        
        if ($stmt) {
            $stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $eventos = [];
            while ($row = $result->fetch_assoc()) {
                $fecha = date('Y-m-d', strtotime($row['fecha_hora']));
                if (!isset($eventos[$fecha])) {
                    $eventos[$fecha] = [];
                }
                $eventos[$fecha][] = $row;
            }
            
            echo json_encode(['success' => true, 'eventos' => $eventos]);
            $stmt->close();
        } else {
            echo json_encode(['success' => true, 'eventos' => []]);
        }
        exit;
    }

    // Obtener eventos de un día específico
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['obtener_eventos_dia'])) {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        
        // Verificar y corregir estructura de la tabla
        if (!verificarEstructuraTablaEventos($conn)) {
            echo json_encode(['success' => true, 'eventos' => []]);
            exit;
        }
        
        $stmt = $conn->prepare('
            SELECT e.*, u.nombre as creador_nombre 
            FROM eventos e 
            JOIN usuarios u ON e.creado_por = u.id 
            WHERE DATE(e.fecha_hora) = ? 
            ORDER BY e.fecha_hora ASC
        ');
        
        if ($stmt) {
            $stmt->bind_param('s', $fecha);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $eventos = [];
            while ($row = $result->fetch_assoc()) {
                $eventos[] = $row;
            }
            
            echo json_encode(['success' => true, 'eventos' => $eventos]);
            $stmt->close();
        } else {
            echo json_encode(['success' => true, 'eventos' => []]);
        }
        exit;
    }

         // Obtener próximos eventos
     if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['obtener_proximos_eventos'])) {
         $fecha = $_GET['fecha'] ?? date('Y-m-d');
         
         // Verificar y corregir estructura de la tabla
         if (!verificarEstructuraTablaEventos($conn)) {
             echo json_encode(['success' => true, 'eventos' => []]);
             exit;
         }
         
         $stmt = $conn->prepare('
             SELECT e.*, u.nombre as creador_nombre 
             FROM eventos e 
             JOIN usuarios u ON e.creado_por = u.id 
             WHERE DATE(e.fecha_hora) >= ? 
             ORDER BY e.fecha_hora ASC 
             LIMIT 10
         ');
         
         if ($stmt) {
             $stmt->bind_param('s', $fecha);
             $stmt->execute();
             $result = $stmt->get_result();
             
             $eventos = [];
             while ($row = $result->fetch_assoc()) {
                 $eventos[] = $row;
             }
             
             echo json_encode(['success' => true, 'eventos' => $eventos]);
             $stmt->close();
         } else {
             echo json_encode(['success' => true, 'eventos' => []]);
         }
         exit;
     }

     // Si no se procesó ninguna acción válida
     echo json_encode(['success' => false, 'message' => 'Acción no válida']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?> 