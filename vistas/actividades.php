<?php
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

// Obtener parámetros de filtro
$filtro_responsable = $_GET['responsable'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Obtener tarjetas por lista con información completa y filtros
$tarjetas = [];
if ($lista_ids) {
    $ids = implode(',', $lista_ids);
    
    // Construir consulta base
    $sql = "SELECT DISTINCT t.*, l.nombre as estado, u.nombre as creador_nombre 
            FROM tarjetas t 
            JOIN listas l ON t.lista_id = l.id 
            LEFT JOIN usuarios u ON t.creado_por = u.id";
    
    // Agregar JOIN para responsables si hay filtro
    if ($filtro_responsable) {
        $sql .= " JOIN tarjeta_usuarios tu ON t.id = tu.tarjeta_id";
    }
    
    $sql .= " WHERE t.lista_id IN ($ids)";
    
    // Aplicar filtros
    $params = [];
    $types = '';
    
    if ($filtro_responsable) {
        $sql .= " AND tu.usuario_id = ?";
        $params[] = $filtro_responsable;
        $types .= 'i';
    }
    
    if ($filtro_fecha_desde) {
        $sql .= " AND t.fecha_vencimiento >= ?";
        $params[] = $filtro_fecha_desde;
        $types .= 's';
    }
    
    if ($filtro_fecha_hasta) {
        $sql .= " AND t.fecha_vencimiento <= ?";
        $params[] = $filtro_fecha_hasta;
        $types .= 's';
    }
    
    $sql .= " ORDER BY t.fecha_creacion ASC";
    
    // Ejecutar consulta con filtros
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql);
    }
    
    while ($t = $res->fetch_assoc()) {
        foreach ($lista_ids as $key => $lid) {
            if ($t['lista_id'] == $lid) {
                $tarjetas[$key][] = $t;
            }
        }
    }
    
    if (!empty($params)) {
        $stmt->close();
    }
}

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

// Obtener etiquetas de cada tarjeta
$etiquetas_por_tarjeta = [];
if (!empty($tarjetas)) {
    $tarjeta_ids = [];
    foreach ($tarjetas as $estado_tarjetas) {
        foreach ($estado_tarjetas as $tarjeta) {
            $tarjeta_ids[] = $tarjeta['id'];
        }
    }
    
    if (!empty($tarjeta_ids)) {
        $ids = implode(',', $tarjeta_ids);
        $sql = "SELECT te.tarjeta_id, e.id, e.nombre, e.color 
                FROM tarjeta_etiquetas te 
                JOIN etiquetas e ON te.etiqueta_id = e.id 
                WHERE te.tarjeta_id IN ($ids)";
        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) {
            $etiquetas_por_tarjeta[$row['tarjeta_id']][] = $row;
        }
    }
}
?>

<div class="animate-fade-in-up">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-4xl font-bold text-gray-800 mb-2 flex items-center gap-3">
                <i class="fas fa-tasks text-blue-600"></i>
                <?php echo $tablero_principal ? htmlspecialchars($tablero_principal['nombre']) : 'Actividades'; ?>
            </h2>
            <?php if ($tablero_principal && $tablero_principal['descripcion']): ?>
                <p class="text-gray-600 text-lg"><?php echo htmlspecialchars($tablero_principal['descripcion']); ?></p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-4">
            <!-- Filtros -->
            <div class="flex items-center gap-3 bg-white rounded-xl shadow-lg p-3 border border-gray-200">
                <!-- Filtro por Responsable -->
                <div class="flex items-center gap-2">
                    <i class="fas fa-user text-blue-600"></i>
                    <select id="filtro-responsable" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800">
                        <option value="">Todos los responsables</option>
                        <?php 
                        $res_usuarios_filtro = $conn->query("SELECT DISTINCT u.id, u.nombre FROM usuarios u 
                                                           JOIN tarjeta_usuarios tu ON u.id = tu.usuario_id 
                                                           WHERE u.rol = 'miembro' AND u.activo = 1 
                                                           ORDER BY u.nombre ASC");
                        while ($u = $res_usuarios_filtro->fetch_assoc()): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo ($filtro_responsable == $u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Filtro por Fecha Desde -->
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar text-green-600"></i>
                    <input type="date" id="filtro-fecha-desde" value="<?php echo $filtro_fecha_desde; ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800" placeholder="Desde">
                </div>
                
                <!-- Filtro por Fecha Hasta -->
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar text-red-600"></i>
                    <input type="date" id="filtro-fecha-hasta" value="<?php echo $filtro_fecha_hasta; ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-800" placeholder="Hasta">
                </div>
                
                <!-- Botón Aplicar Filtros -->
                <button id="btn-aplicar-filtros" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                    <i class="fas fa-filter"></i>
                    <span>Filtrar</span>
                </button>
                
                <!-- Botón Limpiar Filtros -->
                <button id="btn-limpiar-filtros" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    <span>Limpiar</span>
                </button>
            </div>
            
            <!-- Botón Nueva Tarjeta -->
            <button id="btn-nueva-tarjeta" class="btn-modern text-white font-bold py-3 px-8 rounded-xl transition-all duration-300 flex items-center gap-3 hover-scale">
                <i class="fas fa-plus text-lg"></i>
                <span>Nueva tarjeta</span>
            </button>
        </div>
    </div>
    
    <!-- Indicador de filtros activos -->
    <?php if ($filtro_responsable || $filtro_fecha_desde || $filtro_fecha_hasta): ?>
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-filter text-blue-600"></i>
                <span class="text-blue-800 font-medium">Filtros activos:</span>
                <div class="flex items-center gap-2">
                    <?php if ($filtro_responsable): ?>
                        <?php 
                        $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ?");
                        $stmt->bind_param('i', $filtro_responsable);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        $responsable_nombre = $res->fetch_assoc()['nombre'] ?? 'Desconocido';
                        $stmt->close();
                        ?>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($responsable_nombre); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($filtro_fecha_desde): ?>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-calendar mr-1"></i>Desde: <?php echo date('d/m/Y', strtotime($filtro_fecha_desde)); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($filtro_fecha_hasta): ?>
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-calendar mr-1"></i>Hasta: <?php echo date('d/m/Y', strtotime($filtro_fecha_hasta)); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <button onclick="window.limpiarFiltros()" class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center gap-2">
                <i class="fas fa-times"></i>
                Limpiar filtros
            </button>
        </div>
    </div>
    <?php endif; ?>

<div class="flex gap-6 overflow-x-auto pb-4">
    <?php foreach ($estados as $key => $nombre_estado): ?>
    <div class="kanban-col card-modern rounded-2xl shadow-xl p-6 flex-1 min-w-[280px] hover-lift" id="col-<?php echo $key; ?>">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <?php
                $iconos = [
                    'pendiente' => 'fas fa-clock',
                    'en_proceso' => 'fas fa-cogs',
                    'en_aprobacion' => 'fas fa-eye',
                    'detenido' => 'fas fa-pause',
                    'finalizado' => 'fas fa-check-circle'
                ];
                $colores = [
                    'pendiente' => 'text-red-500',
                    'en_proceso' => 'text-yellow-500',
                    'en_aprobacion' => 'text-blue-500',
                    'detenido' => 'text-gray-500',
                    'finalizado' => 'text-green-500'
                ];
                ?>
                <i class="<?php echo $iconos[$key] ?? 'fas fa-circle'; ?> <?php echo $colores[$key] ?? 'text-gray-500'; ?>"></i>
                <?php echo $nombre_estado; ?>
            </h3>
            <span class="bg-gradient-to-r from-blue-500 to-purple-500 text-white text-sm font-bold px-3 py-1 rounded-full shadow-lg">
                <?php echo isset($tarjetas[$key]) ? count($tarjetas[$key]) : 0; ?>
            </span>
        </div>
        <div class="kanban-dropzone" data-lista="<?php echo $lista_ids[$key] ?? 0; ?>">
                            <?php if (!empty($tarjetas[$key])): ?>
                    <?php foreach ($tarjetas[$key] as $tarjeta): ?>
                        <div class="kanban-card bg-white rounded-xl shadow-lg p-5 mb-4 border border-gray-100 hover:shadow-xl transition-all duration-300 cursor-pointer hover-lift" 
                             draggable="true" 
                             data-id="<?php echo $tarjeta['id']; ?>"
                             onclick="abrirTarjeta(<?php echo $tarjeta['id']; ?>)">
                        
                        <!-- Etiquetas -->
                        <?php if (isset($etiquetas_por_tarjeta[$tarjeta['id']])): ?>
                            <div class="flex flex-wrap gap-1 mb-2">
                                <?php foreach ($etiquetas_por_tarjeta[$tarjeta['id']] as $etiqueta): ?>
                                                                         <span class="text-xs px-2 py-1 rounded text-white font-medium shadow-sm" 
                                           style="background-color: <?php echo $etiqueta['color']; ?>">
                                         <?php echo htmlspecialchars($etiqueta['nombre']); ?>
                                     </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                                                 <div class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($tarjeta['titulo']); ?></div>
                         
                         <?php if ($tarjeta['descripcion']): ?>
                             <div class="text-gray-600 text-sm mb-2 line-clamp-2">
                                 <?php echo nl2br(htmlspecialchars(substr($tarjeta['descripcion'], 0, 100))); ?>
                                 <?php if (strlen($tarjeta['descripcion']) > 100): ?>...<?php endif; ?>
                             </div>
                         <?php endif; ?>
                         
                         <div class="flex items-center justify-between text-xs text-gray-500">
                                                         <?php if ($tarjeta['fecha_vencimiento']): ?>
                                 <div class="flex items-center gap-1">
                                     <i class="fas fa-calendar text-gray-400"></i>
                                     <?php echo htmlspecialchars($tarjeta['fecha_vencimiento']); ?>
                                 </div>
                             <?php endif; ?>
                             
                             <?php if ($tarjeta['creador_nombre']): ?>
                                 <div class="flex items-center gap-1">
                                     <i class="fas fa-user text-gray-400"></i>
                                     <?php echo htmlspecialchars($tarjeta['creador_nombre']); ?>
                                 </div>
                             <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Nueva Tarjeta - Removido del archivo de actividades -->



<!-- Procesamiento de tarjeta movido a procesar_nueva_tarjeta.php -->

<script>
// Variables globales para drag & drop
let draggedElement = null;
let originalParent = null;

// Función para abrir tarjeta
function abrirTarjeta(tarjetaId) {
    console.log('Abriendo tarjeta:', tarjetaId);
    // Cargar contenido de la tarjeta via AJAX
    fetch('ver_tarjeta_simple.php?id=' + tarjetaId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('contenido-tarjeta').innerHTML = html;
            const modal = document.getElementById('modal-ver-tarjeta');
            modal.classList.remove('hidden');
            console.log('Modal abierto');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar la tarjeta');
        });
}

// Configurar drag & drop para tarjetas
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Activities view');
    
    // Evitar ejecución múltiple
    if (window.activitiesInitialized) {
        console.log('Activities ya inicializado, saltando...');
        return;
    }
    window.activitiesInitialized = true;
    // Configurar tarjetas como draggable
    const cards = document.querySelectorAll('.kanban-card');
    cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    // Configurar zonas de drop
    const dropZones = document.querySelectorAll('.kanban-dropzone');
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('drop', handleDrop);
        zone.addEventListener('dragenter', handleDragEnter);
        zone.addEventListener('dragleave', handleDragLeave);
    });

    // Configurar botón nueva tarjeta
    const btnNuevaTarjeta = document.getElementById('btn-nueva-tarjeta');
    if (btnNuevaTarjeta) {
        btnNuevaTarjeta.addEventListener('click', function() {
            // Cargar formulario dinámicamente
            fetch('nueva_tarjeta_form.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('contenido-nueva-tarjeta').innerHTML = html;
                    document.getElementById('modal-nueva-tarjeta').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el formulario');
                });
        });
    }

    // Configurar cerrar modales
    const closeModalTarjeta = document.getElementById('close-modal-tarjeta');
    if (closeModalTarjeta) {
        closeModalTarjeta.addEventListener('click', function() {
            document.getElementById('modal-nueva-tarjeta').classList.add('hidden');
        });
    }
    
    // Asegurar que el modal de nueva tarjeta esté oculto al cargar
    const modalNuevaTarjeta = document.getElementById('modal-nueva-tarjeta');
    if (modalNuevaTarjeta) {
        modalNuevaTarjeta.classList.add('hidden');
        console.log('Modal nueva tarjeta oculto al cargar');
    }
    
    // Configurar filtros
    const btnAplicarFiltros = document.getElementById('btn-aplicar-filtros');
    const btnLimpiarFiltros = document.getElementById('btn-limpiar-filtros');
    const filtroResponsable = document.getElementById('filtro-responsable');
    const filtroFechaDesde = document.getElementById('filtro-fecha-desde');
    const filtroFechaHasta = document.getElementById('filtro-fecha-hasta');
    
    console.log('Elementos de filtro encontrados:', {
        btnAplicarFiltros: !!btnAplicarFiltros,
        btnLimpiarFiltros: !!btnLimpiarFiltros,
        filtroResponsable: !!filtroResponsable,
        filtroFechaDesde: !!filtroFechaDesde,
        filtroFechaHasta: !!filtroFechaHasta
    });
    
    if (btnAplicarFiltros) {
        console.log('Botón aplicar filtros encontrado');
        btnAplicarFiltros.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Click en botón aplicar filtros');
            window.aplicarFiltros();
        });
    } else {
        console.log('Botón aplicar filtros NO encontrado');
    }
    
    if (btnLimpiarFiltros) {
        btnLimpiarFiltros.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Click en botón limpiar filtros');
            window.limpiarFiltros();
        });
    }
    
    // Función para aplicar filtros
    window.aplicarFiltros = function() {
        console.log('Aplicando filtros...');
        const responsable = filtroResponsable.value;
        const fechaDesde = filtroFechaDesde.value;
        const fechaHasta = filtroFechaHasta.value;
        
        console.log('Valores de filtros:', { responsable, fechaDesde, fechaHasta });
        
        // Construir URL con parámetros de filtro
        let url = 'dashboard.php?view=actividades-view';
        const params = new URLSearchParams();
        
        if (responsable) params.append('responsable', responsable);
        if (fechaDesde) params.append('fecha_desde', fechaDesde);
        if (fechaHasta) params.append('fecha_hasta', fechaHasta);
        
        if (params.toString()) {
            url += '&' + params.toString();
        }
        
        console.log('URL final:', url);
        
        // Recargar la página con los filtros
        window.location.href = url;
    }
    
    // Función para limpiar filtros
    window.limpiarFiltros = function() {
        filtroResponsable.value = '';
        filtroFechaDesde.value = '';
        filtroFechaHasta.value = '';
        
        // Recargar sin filtros
        window.location.href = 'dashboard.php?view=actividades-view';
    }
    
    // Cargar valores de filtros desde URL si existen
    function cargarFiltrosDesdeURL() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('responsable')) {
            filtroResponsable.value = urlParams.get('responsable');
        }
        if (urlParams.has('fecha_desde')) {
            filtroFechaDesde.value = urlParams.get('fecha_desde');
        }
        if (urlParams.has('fecha_hasta')) {
            filtroFechaHasta.value = urlParams.get('fecha_hasta');
        }
    }
    
    // Ejecutar al cargar
    cargarFiltrosDesdeURL();
    
    console.log('Configuración de filtros completada');

});

// Funciones de drag & drop
function handleDragStart(e) {
    draggedElement = this;
    originalParent = this.parentNode;
    this.style.opacity = '0.4';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.outerHTML);
}

function handleDragEnd(e) {
    this.style.opacity = '1';
    draggedElement = null;
    originalParent = null;
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    
    if (draggedElement && draggedElement !== this) {
        const targetListaId = this.getAttribute('data-lista');
        const tarjetaId = draggedElement.getAttribute('data-id');
        
        // Mover visualmente la tarjeta
        this.appendChild(draggedElement);
        
        // Actualizar en la base de datos
        moverTarjeta(tarjetaId, targetListaId);
        
        // Actualizar contadores
        actualizarContadores();
    }
}

// Función para mover tarjeta en la base de datos
function moverTarjeta(tarjetaId, nuevaListaId) {
    fetch('mover_tarjeta.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'tarjeta_id=' + tarjetaId + '&nueva_lista_id=' + nuevaListaId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Tarjeta movida correctamente');
        } else {
            console.error('Error al mover tarjeta:', data.message);
            // Revertir el movimiento visual si hay error
            if (originalParent && draggedElement) {
                originalParent.appendChild(draggedElement);
                actualizarContadores();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revertir el movimiento visual si hay error
        if (originalParent && draggedElement) {
            originalParent.appendChild(draggedElement);
            actualizarContadores();
        }
    });
}

// Función para actualizar contadores de tarjetas
function actualizarContadores() {
    const columns = document.querySelectorAll('.kanban-col');
    columns.forEach(column => {
        const dropzone = column.querySelector('.kanban-dropzone');
        const counter = column.querySelector('.bg-blue-200');
        if (dropzone && counter) {
            const cardCount = dropzone.querySelectorAll('.kanban-card').length;
            counter.textContent = cardCount;
        }
    });
}

// Cerrar modal al hacer clic fuera
window.onclick = function(e) {
    const modalNueva = document.getElementById('modal-nueva-tarjeta');
    
    if (e.target === modalNueva) modalNueva.classList.add('hidden');
};
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Estilos para drag & drop */
.kanban-dropzone {
    min-height: 100px;
    transition: all 0.3s ease;
}

.kanban-dropzone.drag-over {
    background-color: rgba(59, 130, 246, 0.1);
    border: 2px dashed #3B82F6;
    border-radius: 8px;
}

.kanban-card {
    transition: all 0.3s ease;
    cursor: grab;
}

.kanban-card:active {
    cursor: grabbing;
}

.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

/* Estilo para tarjeta siendo arrastrada */
.kanban-card[draggable="true"]:hover {
    transform: translateY(-2px);
}

/* Animación para el contador */
.bg-blue-200 {
    transition: all 0.3s ease;
}

/* Estilo para zona de drop activa */
.kanban-dropzone.drag-over .kanban-card {
    pointer-events: none;
}
</style> 