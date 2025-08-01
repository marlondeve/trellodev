<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    die('No autorizado');
}

// Obtener datos del tablero principal
$tablero_principal = null;
$stmt = $conn->prepare('SELECT id, nombre, descripcion FROM tableros WHERE id = 1');
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $tablero_principal = $result->fetch_assoc();
}

// Estados y sus IDs de lista
$estados = [
    'pendiente' => 'Pendiente',
    'en_proceso' => 'En Proceso',
    'en_aprobacion' => 'En Aprobación',
    'detenido' => 'Detenido',
    'finalizado' => 'Finalizado',
];

// Obtener IDs de listas por nombre
$lista_ids = [];
$stmt = $conn->prepare('SELECT id, nombre FROM listas WHERE tablero_id = ? ORDER BY orden ASC');
$tablero_id = $tablero_principal ? $tablero_principal['id'] : 1;
$stmt->bind_param('i', $tablero_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    foreach ($estados as $key => $nombre_estado) {
        if (mb_strtolower($row['nombre']) === mb_strtolower($nombre_estado)) {
            $lista_ids[$key] = $row['id'];
        }
    }
}
$stmt->close();

// Obtener etiquetas del tablero
$etiquetas = [];
$stmt = $conn->prepare('SELECT id, nombre, color FROM etiquetas WHERE tablero_id = ?');
$stmt->bind_param('i', $tablero_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $etiquetas[] = $row;
}
$stmt->close();

// Obtener usuarios responsables
$res_usuarios = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'miembro' AND activo = 1 ORDER BY nombre ASC");
$usuarios_responsables = [];
while ($u = $res_usuarios->fetch_assoc()) {
    $usuarios_responsables[] = $u;
}
?>

<h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
    <i class="fas fa-plus-circle text-blue-600"></i>
    Nueva tarjeta
</h3>

<form method="post" action="procesar_nueva_tarjeta.php" autocomplete="off">
    <input type="hidden" name="crear_tarjeta" value="1">
    
    <div class="mb-4">
        <label class="block text-gray-800 font-semibold mb-2" for="titulo">Título *</label>
        <input type="text" name="titulo" id="titulo" required 
               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800">
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-800 font-semibold mb-2" for="descripcion">Descripción</label>
        <textarea name="descripcion" id="descripcion" rows="3" 
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800"
                  placeholder="Describe la tarea..."></textarea>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-800 font-semibold mb-2" for="fecha_vencimiento">Fecha de vencimiento</label>
        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" 
               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800">
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-800 font-semibold mb-2" for="responsable">Responsable</label>
        <?php if (count($usuarios_responsables) > 0): ?>
        <select name="responsable" id="responsable" 
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800">
            <option value="">Selecciona un responsable</option>
            <?php foreach ($usuarios_responsables as $u): ?>
                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php else: ?>
        <div class="text-red-600 font-bold bg-red-50 p-3 rounded-lg border border-red-200">No hay usuarios responsables disponibles. Crea usuarios "miembro" activos primero.</div>
        <?php endif; ?>
    </div>
    
    <div class="mb-4">
        <label class="block text-gray-800 font-semibold mb-2" for="estado">Estado inicial *</label>
        <select name="estado" id="estado" required 
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800">
            <option value="">Selecciona un estado</option>
            <?php foreach ($estados as $key => $nombre_estado): ?>
                <option value="<?php echo $lista_ids[$key] ?? 0; ?>"><?php echo $nombre_estado; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="mb-6">
        <label class="block text-gray-800 font-semibold mb-3">Etiquetas</label>
        <div class="flex flex-wrap gap-3">
            <?php foreach ($etiquetas as $etiqueta): ?>
                <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-gray-50 transition-colors">
                    <input type="checkbox" name="etiquetas[]" value="<?php echo $etiqueta['id']; ?>" 
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2">
                    <span class="text-sm px-3 py-1 rounded-full text-white font-medium shadow-sm" 
                          style="background-color: <?php echo $etiqueta['color']; ?>">
                        <?php echo htmlspecialchars($etiqueta['nombre']); ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    
    <button type="submit" class="w-full btn-modern text-white font-bold py-3 rounded-xl transition-all duration-300 flex items-center justify-center gap-2" 
            <?php if (count($usuarios_responsables) === 0) echo 'disabled'; ?>>
        <i class="fas fa-plus"></i>
        <span>Crear tarjeta</span>
    </button>
</form> 