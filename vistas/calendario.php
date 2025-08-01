<?php
// Obtener el mes y año actual
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$año = isset($_GET['año']) ? intval($_GET['año']) : date('Y');

// Nombres de los meses
$nombres_meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Días de la semana
$dias_semana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

// Obtener el primer día del mes
$primer_dia = mktime(0, 0, 0, $mes, 1, $año);
$ultimo_dia = mktime(0, 0, 0, $mes + 1, 0, $año);

// Obtener información del primer día
$dia_semana_inicio = date('N', $primer_dia); // 1 = Lunes, 7 = Domingo
$total_dias = date('j', $ultimo_dia);

// Obtener eventos del mes desde la base de datos
$eventos = [];

// Verificar si la tabla eventos existe, si no, crearla
$tabla_existe = false;

// Intentar crear la tabla si no existe
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

if ($conn->query($sql_crear_tabla)) {
    $tabla_existe = true;
}

// Solo intentar consultar si la tabla existe
if ($tabla_existe) {
    try {
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
            
            while ($row = $result->fetch_assoc()) {
                $fecha = date('Y-m-d', strtotime($row['fecha_hora']));
                if (!isset($eventos[$fecha])) {
                    $eventos[$fecha] = [];
                }
                $eventos[$fecha][] = $row;
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        // Si hay error, simplemente continuar sin eventos
        $eventos = [];
    }
}

// Función para obtener eventos de un día específico
function obtenerEventosDia($dia, $mes, $año, $eventos) {
    $fecha = sprintf('%04d-%02d-%02d', $año, $mes, $dia);
    return isset($eventos[$fecha]) ? $eventos[$fecha] : [];
}

// Función para verificar si es hoy
function esHoy($dia, $mes, $año) {
    return $dia == date('j') && $mes == date('n') && $año == date('Y');
}

// Función para verificar si es fin de semana
function esFinDeSemana($dia_semana) {
    return $dia_semana == 6 || $dia_semana == 7;
}
?>

<div class="animate-fade-in-up">
    <!-- Header del Calendario -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-4xl font-bold text-gray-800 mb-2 flex items-center gap-3">
                <i class="fas fa-calendar-alt text-purple-600"></i>
                Calendario
            </h2>
            <p class="text-gray-600 text-lg">Gestiona tus eventos y reuniones de manera intuitiva</p>
        </div>
        <div class="flex items-center gap-4">
            <!-- Navegación del Calendario -->
            <div class="flex items-center gap-3 bg-white rounded-xl shadow-lg p-3 border border-gray-200">
                <button onclick="cambiarMes(-1)" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-chevron-left text-gray-600"></i>
                </button>
                <span class="text-lg font-semibold text-gray-800 px-4">
                    <?php echo $nombres_meses[$mes] . ' ' . $año; ?>
                </span>
                <button onclick="cambiarMes(1)" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-chevron-right text-gray-600"></i>
                </button>
            </div>
            
                         <!-- Botón Nuevo Evento -->
             <button id="btn-nuevo-evento" class="btn-modern text-white font-bold py-3 px-8 rounded-xl transition-all duration-300 flex items-center gap-3 hover-scale">
                 <i class="fas fa-plus text-lg"></i>
                 <span>Nuevo evento</span>
             </button>
        </div>
    </div>

    <!-- Vista del Calendario -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Calendario Principal -->
        <div class="lg:col-span-3">
            <div class="card-modern rounded-2xl shadow-xl p-6">
                <!-- Días de la semana -->
                <div class="grid grid-cols-7 gap-2 mb-4">
                    <?php foreach ($dias_semana as $dia): ?>
                        <div class="text-center py-3 font-semibold text-gray-600 text-sm">
                            <?php echo $dia; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Días del mes -->
                <div class="grid grid-cols-7 gap-2">
                    <?php
                    // Días vacíos al inicio
                    for ($i = 1; $i < $dia_semana_inicio; $i++) {
                        echo '<div class="h-24 bg-gray-50 rounded-lg border border-gray-100"></div>';
                    }

                    // Días del mes
                    for ($dia = 1; $dia <= $total_dias; $dia++) {
                        $dia_semana = ($dia_semana_inicio + $dia - 2) % 7 + 1;
                        $eventos_dia = obtenerEventosDia($dia, $mes, $año, $eventos);
                        $es_hoy = esHoy($dia, $mes, $año);
                        $es_finde = esFinDeSemana($dia_semana);
                        
                        $clases = 'h-24 p-2 rounded-lg border transition-all duration-300 cursor-pointer hover:shadow-md ';
                        $clases .= $es_hoy ? 'bg-gradient-to-br from-purple-500 to-purple-600 text-white border-purple-500' : 
                                   ($es_finde ? 'bg-gray-50 border-gray-200' : 'bg-white border-gray-200 hover:border-purple-300');
                        
                        echo '<div class="' . $clases . '" onclick="seleccionarDia(' . $dia . ', ' . $mes . ', ' . $año . ')">';
                        echo '<div class="flex justify-between items-start">';
                        echo '<span class="text-sm font-medium ' . ($es_hoy ? 'text-white' : 'text-gray-700') . '">' . $dia . '</span>';
                        
                        if (!empty($eventos_dia)) {
                            echo '<div class="flex flex-col gap-1">';
                            foreach (array_slice($eventos_dia, 0, 2) as $evento) {
                                $color_clase = $evento['tipo'] === 'reunion' ? 'bg-blue-500' : 
                                             ($evento['tipo'] === 'tarea' ? 'bg-green-500' : 'bg-orange-500');
                                echo '<div class="w-2 h-2 rounded-full ' . $color_clase . '"></div>';
                            }
                            if (count($eventos_dia) > 2) {
                                echo '<div class="text-xs ' . ($es_hoy ? 'text-white' : 'text-gray-500') . '">+' . (count($eventos_dia) - 2) . '</div>';
                            }
                            echo '</div>';
                        }
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="lg:col-span-1">
            <!-- Día Seleccionado -->
            <div class="card-modern rounded-2xl shadow-xl p-6 mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-calendar-day text-purple-600"></i>
                    <span id="dia-seleccionado">Hoy</span>
                </h3>
                <div id="eventos-dia" class="space-y-3">
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-calendar-plus text-3xl mb-2 text-gray-300"></i>
                        <p class="text-sm">No hay eventos programados</p>
                    </div>
                </div>
            </div>

                         <!-- Próximos Eventos -->
             <div class="card-modern rounded-2xl shadow-xl p-6">
                 <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                     <i class="fas fa-clock text-blue-600"></i>
                     Próximos Eventos
                 </h3>
                 <div id="proximos-eventos" class="space-y-3">
                     <div class="text-center text-gray-500 py-8">
                         <i class="fas fa-calendar-plus text-3xl mb-2 text-gray-300"></i>
                         <p class="text-sm">Cargando próximos eventos...</p>
                     </div>
                 </div>
             </div>
        </div>
    </div>
</div>



<script>
// Variables globales
let diaSeleccionado = null;
let mesActual = <?php echo $mes; ?>;
let añoActual = <?php echo $año; ?>;
let modalVerEventoAbierta = false; // Flag para controlar el estado de la modal






// Función para cambiar mes
function cambiarMes(direccion) {
    mesActual += direccion;
    
    if (mesActual > 12) {
        mesActual = 1;
        añoActual++;
    } else if (mesActual < 1) {
        mesActual = 12;
        añoActual--;
    }
    
    // Recargar la página con el nuevo mes
    window.location.href = `dashboard.php?view=calendario-view&mes=${mesActual}&año=${añoActual}`;
}

// Función para seleccionar día
function seleccionarDia(dia, mes, año) {
    diaSeleccionado = { dia, mes, año };
    
    // Actualizar el texto del día seleccionado
    const fecha = new Date(año, mes - 1, dia);
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('dia-seleccionado').textContent = fecha.toLocaleDateString('es-ES', opciones);
    
    // Aquí se cargarían los eventos del día seleccionado
    cargarEventosDia(dia, mes, año);
}

// Función para actualizar indicadores del calendario
function actualizarIndicadoresCalendario(mes, año) {
    // Obtener eventos del mes desde el servidor
    fetch(`procesar_evento.php?obtener_eventos=1&mes=${mes}&año=${año}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Actualizar los indicadores visuales en el calendario
                actualizarIndicadoresVisuales(data.eventos);
            }
        })
        .catch(error => {
            console.error('Error al actualizar indicadores:', error);
        });
}

// Función para actualizar indicadores visuales
function actualizarIndicadoresVisuales(eventos) {
    // Limpiar indicadores existentes
    const diasCalendario = document.querySelectorAll('[onclick*="seleccionarDia"]');
    diasCalendario.forEach(dia => {
        const indicadores = dia.querySelector('.flex.flex-col.gap-1');
        if (indicadores) {
            indicadores.remove();
        }
    });
    
    // Agregar nuevos indicadores
    Object.keys(eventos).forEach(fecha => {
        const [año, mes, dia] = fecha.split('-');
        const diaElement = document.querySelector(`[onclick*="seleccionarDia(${parseInt(dia)}, ${parseInt(mes)}, ${año})"]`);
        
        if (diaElement && eventos[fecha].length > 0) {
            const indicadoresContainer = document.createElement('div');
            indicadoresContainer.className = 'flex flex-col gap-1';
            
            eventos[fecha].slice(0, 2).forEach(evento => {
                const colorClase = evento.tipo === 'reunion' ? 'bg-blue-500' : 
                                 evento.tipo === 'tarea' ? 'bg-green-500' : 'bg-orange-500';
                const indicador = document.createElement('div');
                indicador.className = `w-2 h-2 rounded-full ${colorClase}`;
                indicadoresContainer.appendChild(indicador);
            });
            
            if (eventos[fecha].length > 2) {
                const contador = document.createElement('div');
                contador.className = 'text-xs text-gray-500';
                contador.textContent = `+${eventos[fecha].length - 2}`;
                indicadoresContainer.appendChild(contador);
            }
            
            diaElement.appendChild(indicadoresContainer);
        }
    });
}

// Función para cargar próximos eventos
function cargarProximosEventos() {
    console.log('Cargando próximos eventos...');
    const proximosEventosContainer = document.getElementById('proximos-eventos');
    
    if (!proximosEventosContainer) {
        console.error('Contenedor de próximos eventos no encontrado');
        return;
    }
    
    // Mostrar loading
    proximosEventosContainer.innerHTML = `
        <div class="text-center text-gray-500 py-8">
            <div class="spinner mb-2"></div>
            <p class="text-sm">Cargando próximos eventos...</p>
        </div>
    `;
    
    // Obtener fecha actual
    const fechaActual = new Date();
    const fechaFormateada = fechaActual.toISOString().split('T')[0];
    
    // Cargar eventos desde hoy en adelante
    fetch(`procesar_evento.php?obtener_proximos_eventos=1&fecha=${fechaFormateada}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success && data.eventos.length > 0) {
                // Mostrar solo los próximos 5 eventos
                const eventosMostrar = data.eventos.slice(0, 5);
                
                proximosEventosContainer.innerHTML = eventosMostrar.map(evento => {
                    const fechaEvento = new Date(evento.fecha_hora);
                    const hora = fechaEvento.toLocaleTimeString('es-ES', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    
                    // Calcular días de diferencia
                    const diffTime = fechaEvento - fechaActual;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    let tiempoRelativo = '';
                    if (diffDays === 0) {
                        tiempoRelativo = 'Hoy';
                    } else if (diffDays === 1) {
                        tiempoRelativo = 'Mañana';
                    } else if (diffDays < 7) {
                        tiempoRelativo = `En ${diffDays} días`;
                    } else {
                        tiempoRelativo = fechaEvento.toLocaleDateString('es-ES', { 
                            day: 'numeric', 
                            month: 'short' 
                        });
                    }
                    
                    const colorClase = evento.tipo === 'reunion' ? 'bg-blue-500' : 
                                     evento.tipo === 'tarea' ? 'bg-green-500' : 
                                     evento.tipo === 'recordatorio' ? 'bg-orange-500' : 'bg-purple-500';
                    
                    const bgClase = evento.tipo === 'reunion' ? 'bg-blue-50 border-blue-200' : 
                                   evento.tipo === 'tarea' ? 'bg-green-50 border-green-200' : 
                                   evento.tipo === 'recordatorio' ? 'bg-orange-50 border-orange-200' : 'bg-purple-50 border-purple-200';
                    
                                         return `
                         <div class="flex items-center gap-3 p-3 ${bgClase} rounded-lg border hover:shadow-md transition-all duration-300 cursor-pointer" 
                              data-evento='${JSON.stringify(evento)}' onclick="verEventoDesdeElemento(this)">
                             <div class="w-3 h-3 ${colorClase} rounded-full"></div>
                             <div class="flex-1">
                                 <div class="font-medium text-gray-800">${evento.titulo}</div>
                                 <div class="text-sm text-gray-600">${tiempoRelativo}, ${hora}</div>
                             </div>
                         </div>
                     `;
                }).join('');
            } else {
                proximosEventosContainer.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-calendar-plus text-3xl mb-2 text-gray-300"></i>
                        <p class="text-sm">No hay próximos eventos</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            proximosEventosContainer.innerHTML = `
                <div class="text-center text-red-500 py-8">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                    <p class="text-sm">Error al cargar próximos eventos</p>
                </div>
            `;
        });
}



// Función para cargar eventos del día
function cargarEventosDia(dia, mes, año) {
    const eventosContainer = document.getElementById('eventos-dia');
    const fecha = `${año}-${String(mes).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
    
    // Mostrar loading
    eventosContainer.innerHTML = `
        <div class="text-center text-gray-500 py-8">
            <div class="spinner mb-2"></div>
            <p class="text-sm">Cargando eventos...</p>
        </div>
    `;
    
    // Cargar eventos desde la base de datos
    fetch(`procesar_evento.php?obtener_eventos_dia=1&fecha=${fecha}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            if (data.success && data.eventos.length > 0) {
                eventosContainer.innerHTML = data.eventos.map(evento => {
                    const hora = new Date(evento.fecha_hora).toLocaleTimeString('es-ES', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    
                    const colorClase = evento.tipo === 'reunion' ? 'bg-blue-500' : 
                                     evento.tipo === 'tarea' ? 'bg-green-500' : 
                                     evento.tipo === 'recordatorio' ? 'bg-orange-500' : 'bg-purple-500';
                    
                                                                                                                             return `
                           <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200 hover:shadow-md transition-all duration-300 cursor-pointer" 
                                data-evento='${JSON.stringify(evento)}' onclick="verEventoDesdeElemento(this)">
                               <div class="w-3 h-3 ${colorClase} rounded-full"></div>
                               <div class="flex-1">
                                   <div class="font-medium text-gray-800">${evento.titulo}</div>
                                   <div class="text-sm text-gray-600">${hora}</div>
                                   ${evento.descripcion ? `<div class="text-xs text-gray-500 mt-1">${evento.descripcion}</div>` : ''}
                               </div>
                           </div>
                       `;
                }).join('');
            } else {
                eventosContainer.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-calendar-plus text-3xl mb-2 text-gray-300"></i>
                        <p class="text-sm">No hay eventos programados</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            eventosContainer.innerHTML = `
                <div class="text-center text-red-500 py-8">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                    <p class="text-sm">Error al cargar eventos</p>
                </div>
            `;
        });
}

// Configurar calendario
document.addEventListener('DOMContentLoaded', function() {
    // Prevenir múltiples ejecuciones
    if (window.calendarioInicializado) {
        console.log('Calendario ya inicializado, saltando...');
        return;
    }
    window.calendarioInicializado = true;
    console.log('Inicializando calendario...');
    
    // Configurar botón nuevo evento
    const btnNuevoEvento = document.getElementById('btn-nuevo-evento');
    if (btnNuevoEvento) {
        btnNuevoEvento.addEventListener('click', function() {
            console.log('Botón clickeado');
            abrirModalNuevoEvento();
        });
    }
    
    // Seleccionar hoy por defecto
    seleccionarDia(<?php echo date('j'); ?>, <?php echo date('n'); ?>, <?php echo date('Y'); ?>);
    
    // Cargar próximos eventos
    cargarProximosEventos();
    
    console.log('Calendario inicializado correctamente');
});
</script>

<style>
/* Estilos específicos para el calendario */
.calendar-day {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.calendar-day:hover {
    transform: scale(1.02);
}

.calendar-day.today {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}

.calendar-day.weekend {
    background-color: #f8fafc;
}

.calendar-day.has-events {
    border-color: #8b5cf6;
}

/* Animaciones para eventos */
.event-item {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Efectos hover para días del calendario */
.calendar-day:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #8b5cf6;
}

/* Estilos para indicadores de eventos */
.event-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin: 1px;
}

.event-indicator.reunion { background-color: #3b82f6; }
.event-indicator.tarea { background-color: #10b981; }
.event-indicator.recordatorio { background-color: #f59e0b; }
.event-indicator.evento { background-color: #ef4444; }

/* Spinner para loading */
.spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid #8b5cf6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}


</style> 