<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/conexion.php';

$tarjeta_id = intval($_POST['tarjeta_id'] ?? 0);
$nueva_lista_id = intval($_POST['nueva_lista_id'] ?? 0);

if (!$tarjeta_id || !$nueva_lista_id) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    // Verificar que la tarjeta existe
    $stmt = $conn->prepare('SELECT id FROM tarjetas WHERE id = ?');
    $stmt->bind_param('i', $tarjeta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Tarjeta no encontrada']);
        exit;
    }
    $stmt->close();
    
    // Verificar que la lista existe
    $stmt = $conn->prepare('SELECT id FROM listas WHERE id = ?');
    $stmt->bind_param('i', $nueva_lista_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lista no encontrada']);
        exit;
    }
    $stmt->close();
    
    // Actualizar la tarjeta
    $stmt = $conn->prepare('UPDATE tarjetas SET lista_id = ? WHERE id = ?');
    $stmt->bind_param('ii', $nueva_lista_id, $tarjeta_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tarjeta movida correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la tarjeta']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?> 