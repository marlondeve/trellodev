<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit('No autorizado');
}

require_once __DIR__ . '/conexion.php';

$tarjeta_id = intval($_GET['id'] ?? 0);
if (!$tarjeta_id) {
    exit('ID de tarjeta no válido');
}

// Obtener información de la tarjeta
$stmt = $conn->prepare('
    SELECT t.*, l.nombre as estado, u.nombre as creador_nombre, u.email as creador_email
    FROM tarjetas t 
    JOIN listas l ON t.lista_id = l.id 
    LEFT JOIN usuarios u ON t.creado_por = u.id 
    WHERE t.id = ?
');
$stmt->bind_param('i', $tarjeta_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit('Tarjeta no encontrada');
}

$tarjeta = $result->fetch_assoc();
$stmt->close();

// Obtener etiquetas de la tarjeta
$etiquetas = [];
$stmt = $conn->prepare('
    SELECT e.id, e.nombre, e.color 
    FROM tarjeta_etiquetas te 
    JOIN etiquetas e ON te.etiqueta_id = e.id 
    WHERE te.tarjeta_id = ?
');
$stmt->bind_param('i', $tarjeta_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $etiquetas[] = $row;
}
$stmt->close();

// Obtener responsables de la tarjeta
$responsables = [];
$stmt = $conn->prepare('
    SELECT u.id, u.nombre, u.email 
    FROM tarjeta_usuarios tu 
    JOIN usuarios u ON tu.usuario_id = u.id 
    WHERE tu.tarjeta_id = ?
');
$stmt->bind_param('i', $tarjeta_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $responsables[] = $row;
}
$stmt->close();

// Obtener comentarios de la tarjeta
$comentarios = [];
$stmt = $conn->prepare('
    SELECT c.*, u.nombre as usuario_nombre 
    FROM comentarios c 
    JOIN usuarios u ON c.usuario_id = u.id 
    WHERE c.tarjeta_id = ? 
    ORDER BY c.fecha_creacion DESC
');
$stmt->bind_param('i', $tarjeta_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $comentarios[] = $row;
}
$stmt->close();

// Obtener archivos adjuntos
$archivos = [];
$stmt = $conn->prepare('
    SELECT aa.*, u.nombre as subido_por_nombre 
    FROM archivos_adjuntos aa 
    JOIN usuarios u ON aa.subido_por = u.id 
    WHERE aa.tarjeta_id = ? 
    ORDER BY aa.fecha_subida DESC
');
$stmt->bind_param('i', $tarjeta_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $archivos[] = $row;
}
$stmt->close();
?>

<div class="space-y-6">
    <!-- Header de la tarjeta -->
    <div class="border-b border-blue-200 pb-4">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-blue-900 mb-2"><?php echo htmlspecialchars($tarjeta['titulo']); ?></h2>
                
                <!-- Etiquetas -->
                <?php if (!empty($etiquetas)): ?>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php foreach ($etiquetas as $etiqueta): ?>
                            <span class="text-sm px-3 py-1 rounded-full text-white font-medium" 
                                  style="background-color: <?php echo $etiqueta['color']; ?>">
                                <?php echo htmlspecialchars($etiqueta['nombre']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Estado y creador -->
                <div class="flex items-center gap-4 text-sm text-blue-600">
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium"><?php echo htmlspecialchars($tarjeta['estado']); ?></span>
                    </div>
                    
                    <?php if ($tarjeta['creador_nombre']): ?>
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Creado por <?php echo htmlspecialchars($tarjeta['creador_nombre']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($tarjeta['fecha_vencimiento']): ?>
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Vence: <?php echo htmlspecialchars($tarjeta['fecha_vencimiento']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <button onclick="document.getElementById('modal-ver-tarjeta').classList.add('hidden')" 
                    class="text-blue-400 hover:text-blue-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Descripción -->
    <?php if ($tarjeta['descripcion']): ?>
        <div class="bg-blue-50 rounded-lg p-4">
            <h3 class="font-semibold text-blue-900 mb-2">Descripción</h3>
            <div class="text-blue-700 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($tarjeta['descripcion'])); ?></div>
        </div>
    <?php endif; ?>
    
    <!-- Responsables -->
    <?php if (!empty($responsables)): ?>
        <div>
            <h3 class="font-semibold text-blue-900 mb-3">Responsables</h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($responsables as $responsable): ?>
                    <div class="bg-blue-100 rounded-lg px-3 py-2 flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                            <?php echo strtoupper(substr($responsable['nombre'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="font-medium text-blue-900"><?php echo htmlspecialchars($responsable['nombre']); ?></div>
                            <div class="text-xs text-blue-600"><?php echo htmlspecialchars($responsable['email']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Archivos adjuntos -->
    <?php if (!empty($archivos)): ?>
        <div>
            <h3 class="font-semibold text-blue-900 mb-3">Archivos adjuntos</h3>
            <div class="space-y-2">
                <?php foreach ($archivos as $archivo): ?>
                    <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center gap-3">
                            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-blue-900"><?php echo htmlspecialchars($archivo['nombre_archivo']); ?></div>
                                <div class="text-xs text-blue-600">
                                    Subido por <?php echo htmlspecialchars($archivo['subido_por_nombre']); ?> 
                                    el <?php echo date('d/m/Y H:i', strtotime($archivo['fecha_subida'])); ?>
                                </div>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars($archivo['ruta_archivo']); ?>" 
                           target="_blank" 
                           class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                            Descargar
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Comentarios -->
    <div>
        <h3 class="font-semibold text-blue-900 mb-3">Comentarios (<?php echo count($comentarios); ?>)</h3>
        
        <!-- Formulario para nuevo comentario -->
        <form method="post" action="agregar_comentario.php" class="mb-4">
            <input type="hidden" name="tarjeta_id" value="<?php echo $tarjeta_id; ?>">
            <div class="flex gap-3">
                <textarea name="contenido" rows="2" required 
                          class="flex-1 border border-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Escribe un comentario..."></textarea>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    Comentar
                </button>
            </div>
        </form>
        
        <!-- Lista de comentarios -->
        <div class="space-y-3">
            <?php if (empty($comentarios)): ?>
                <div class="text-center text-blue-500 py-4">No hay comentarios aún</div>
            <?php else: ?>
                <?php foreach ($comentarios as $comentario): ?>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                <?php echo strtoupper(substr($comentario['usuario_nombre'], 0, 1)); ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium text-blue-900"><?php echo htmlspecialchars($comentario['usuario_nombre']); ?></span>
                                    <span class="text-xs text-blue-500"><?php echo date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])); ?></span>
                                </div>
                                <div class="text-blue-700"><?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div> 