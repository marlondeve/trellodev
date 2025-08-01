<?php
// Script para inicializar la base de datos con datos necesarios
// Ejecutar este script una vez después de crear las tablas

require_once __DIR__ . '/conexion.php';

echo "<h2>Inicializando base de datos...</h2>";

// Verificar si ya existe un tablero
$stmt = $conn->prepare('SELECT id FROM tableros LIMIT 1');
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p>Creando tablero principal...</p>";
    
    // Crear tablero principal
    $stmt = $conn->prepare('INSERT INTO tableros (nombre, descripcion, creado_por) VALUES (?, ?, ?)');
    $nombre_tablero = 'Tablero Principal';
    $descripcion = 'Tablero principal del proyecto';
    $creado_por = 1; // Asumiendo que el admin tiene ID 1
    $stmt->bind_param('ssi', $nombre_tablero, $descripcion, $creado_por);
    
    if ($stmt->execute()) {
        $tablero_id = $conn->insert_id;
        echo "<p>✅ Tablero creado con ID: $tablero_id</p>";
        
        // Crear listas por defecto
        $listas = [
            ['Pendiente', 1],
            ['En Proceso', 2],
            ['En Aprobación', 3],
            ['Detenido', 4],
            ['Finalizado', 5]
        ];
        
        $stmt = $conn->prepare('INSERT INTO listas (nombre, orden, tablero_id) VALUES (?, ?, ?)');
        
        foreach ($listas as $lista) {
            $stmt->bind_param('sii', $lista[0], $lista[1], $tablero_id);
            if ($stmt->execute()) {
                echo "<p>✅ Lista '{$lista[0]}' creada</p>";
            } else {
                echo "<p>❌ Error al crear lista '{$lista[0]}': " . $stmt->error . "</p>";
            }
        }
        
        // Crear etiquetas por defecto
        $etiquetas = [
            ['Urgente', '#EF4444'],
            ['Bug', '#DC2626'],
            ['Mejora', '#10B981'],
            ['Documentación', '#F59E0B'],
            ['Diseño', '#8B5CF6']
        ];
        
        $stmt = $conn->prepare('INSERT INTO etiquetas (nombre, color, tablero_id) VALUES (?, ?, ?)');
        
        foreach ($etiquetas as $etiqueta) {
            $stmt->bind_param('ssi', $etiqueta[0], $etiqueta[1], $tablero_id);
            if ($stmt->execute()) {
                echo "<p>✅ Etiqueta '{$etiqueta[0]}' creada</p>";
            } else {
                echo "<p>❌ Error al crear etiqueta '{$etiqueta[0]}': " . $stmt->error . "</p>";
            }
        }
        
    } else {
        echo "<p>❌ Error al crear tablero: " . $stmt->error . "</p>";
    }
} else {
    echo "<p>✅ La base de datos ya está inicializada</p>";
}

$stmt->close();
$conn->close();

echo "<h3>Inicialización completada</h3>";
echo "<p><a href='dashboard.php'>Ir al Dashboard</a></p>";
?> 