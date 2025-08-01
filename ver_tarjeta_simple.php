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

// Verificar si existen las tablas nuevas
$tabla_etiquetas_existe = false;
$tabla_comentarios_existe = false;

try {
    $conn->query("SELECT 1 FROM etiquetas LIMIT 1");
    $tabla_etiquetas_existe = true;
} catch (Exception $e) {
    $tabla_etiquetas_existe = false;
}

try {
    $conn->query("SELECT 1 FROM comentarios LIMIT 1");
    $tabla_comentarios_existe = true;
} catch (Exception $e) {
    $tabla_comentarios_existe = false;
}

// Obtener etiquetas de la tarjeta (si la tabla existe)
$etiquetas = [];
if ($tabla_etiquetas_existe) {
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
}

// Obtener comentarios de la tarjeta (si la tabla existe)
$comentarios = [];
if ($tabla_comentarios_existe) {
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
}
?>

<div class="space-y-6">
    <!-- Header de la tarjeta -->
    <div class="border-b border-gray-200 pb-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h2 class="text-3xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                    <i class="fas fa-sticky-note text-blue-600"></i>
                    <?php echo htmlspecialchars($tarjeta['titulo']); ?>
                </h2>
                
                <!-- Etiquetas -->
                <?php if (!empty($etiquetas)): ?>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <?php foreach ($etiquetas as $etiqueta): ?>
                                                         <span class="text-sm px-3 py-1 rounded-full text-white font-medium shadow-sm" 
                                   style="background-color: <?php echo $etiqueta['color']; ?>">
                                 <?php echo htmlspecialchars($etiqueta['nombre']); ?>
                             </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                                 <!-- Estado y creador -->
                 <div class="flex items-center gap-4 text-sm text-gray-600">
                     <div class="flex items-center gap-1">
                         <i class="fas fa-check-circle text-green-500"></i>
                         <span class="font-medium text-gray-700"><?php echo htmlspecialchars($tarjeta['estado']); ?></span>
                     </div>
                     
                     <?php if ($tarjeta['creador_nombre']): ?>
                         <div class="flex items-center gap-1">
                             <i class="fas fa-user text-blue-500"></i>
                             <span class="text-gray-700">Creado por <?php echo htmlspecialchars($tarjeta['creador_nombre']); ?></span>
                         </div>
                     <?php endif; ?>
                     
                     <?php if ($tarjeta['fecha_vencimiento']): ?>
                         <div class="flex items-center gap-1">
                             <i class="fas fa-calendar text-orange-500"></i>
                             <span class="text-gray-700">Vence: <?php echo htmlspecialchars($tarjeta['fecha_vencimiento']); ?></span>
                         </div>
                     <?php endif; ?>
                 </div>
            </div>
            
            <!-- Botón de cerrar removido - ya existe en el modal principal -->
        </div>
    </div>
    
    <!-- Descripción -->
    <?php if ($tarjeta['descripcion']): ?>
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="fas fa-align-left text-blue-600"></i>
                Descripción
            </h3>
            <div class="text-gray-700 whitespace-pre-wrap leading-relaxed"><?php echo nl2br(htmlspecialchars($tarjeta['descripcion'])); ?></div>
        </div>
    <?php endif; ?>
    
    <!-- Responsables -->
    <?php if (!empty($responsables)): ?>
        <div>
            <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-users text-blue-600"></i>
                Responsables
            </h3>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($responsables as $responsable): ?>
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl px-4 py-3 flex items-center gap-3 border border-blue-100 hover-lift">
                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg">
                            <?php echo strtoupper(substr($responsable['nombre'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($responsable['nombre']); ?></div>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($responsable['email']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Comentarios -->
    <div>
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-comments text-blue-600"></i>
            Comentarios (<?php echo count($comentarios); ?>)
        </h3>
        
        <?php if ($tabla_comentarios_existe): ?>
                         <!-- Formulario para nuevo comentario -->
             <form method="post" action="agregar_comentario.php" class="mb-4">
                 <input type="hidden" name="tarjeta_id" value="<?php echo $tarjeta_id; ?>">
                 <div class="flex gap-3">
                     <textarea name="contenido" rows="2" required 
                               class="flex-1 border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800"
                               placeholder="Escribe un comentario..."></textarea>
                     <button type="submit" 
                             class="btn-modern text-white px-6 py-3 rounded-xl font-medium flex items-center gap-2">
                         <i class="fas fa-paper-plane"></i>
                         <span>Comentar</span>
                     </button>
                 </div>
             </form>
            
            <!-- Lista de comentarios -->
            <div class="space-y-3">
                                 <?php if (empty($comentarios)): ?>
                     <div class="text-center text-gray-500 py-8 bg-gray-50 rounded-lg">
                         <i class="fas fa-comments text-3xl mb-2 text-gray-400"></i>
                         <p class="text-gray-600">No hay comentarios aún</p>
                     </div>
                 <?php else: ?>
                     <?php foreach ($comentarios as $comentario): ?>
                         <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                             <div class="flex items-start gap-3">
                                 <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                     <?php echo strtoupper(substr($comentario['usuario_nombre'], 0, 1)); ?>
                                 </div>
                                 <div class="flex-1">
                                     <div class="flex items-center gap-2 mb-2">
                                         <span class="font-medium text-gray-800"><?php echo htmlspecialchars($comentario['usuario_nombre']); ?></span>
                                         <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full"><?php echo date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])); ?></span>
                                     </div>
                                     <div class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?></div>
                                 </div>
                             </div>
                         </div>
                     <?php endforeach; ?>
                 <?php endif; ?>
            </div>
                 <?php else: ?>
             <div class="text-center text-gray-500 py-8 bg-gray-50 rounded-lg border border-gray-200">
                 <i class="fas fa-tools text-3xl mb-2 text-gray-400"></i>
                 <p class="text-gray-600 mb-1">Los comentarios estarán disponibles pronto.</p>
                 <p class="text-sm text-gray-500">Ejecuta el script de inicialización para habilitar esta función.</p>
             </div>
         <?php endif; ?>
    </div>
    
    <!-- Información adicional -->
    <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl p-6 border border-gray-200">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-600"></i>
            Información de la tarjeta
        </h3>
        <div class="grid grid-cols-2 gap-6 text-sm">
            <div class="flex items-center gap-2">
                <i class="fas fa-hashtag text-blue-500"></i>
                <span class="font-medium text-gray-700">ID:</span> 
                <span class="text-gray-900 font-bold">#<?php echo $tarjeta['id']; ?></span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar-plus text-blue-500"></i>
                <span class="font-medium text-gray-700">Creada:</span> 
                <span class="text-gray-900 font-bold"><?php echo date('d/m/Y H:i', strtotime($tarjeta['fecha_creacion'])); ?></span>
            </div>
        </div>
    </div>
</div> 