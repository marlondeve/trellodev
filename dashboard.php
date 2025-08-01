<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$nombre = $_SESSION['nombre'] ?? '';
$rol = $_SESSION['rol'] ?? '';

require_once __DIR__ . '/conexion.php';

// Estados y sus IDs de lista (debes tener listas creadas con estos nombres en la BD)
$estados = [
    'pendiente' => 'Pendiente',
    'en_proceso' => 'En Proceso',
    'en_aprobacion' => 'En Aprobación',
    'detenido' => 'Detenido',
    'finalizado' => 'Finalizado',
];

// Obtener IDs de listas por nombre
$lista_ids = [];
$stmt = $conn->prepare('SELECT id, nombre FROM listas');
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

// Obtener tarjetas por lista
$tarjetas = [];
if ($lista_ids) {
    $ids = implode(',', $lista_ids);
    $sql = "SELECT t.*, l.nombre as estado FROM tarjetas t JOIN listas l ON t.lista_id = l.id WHERE t.lista_id IN ($ids) ORDER BY t.fecha_creacion ASC";
    $res = $conn->query($sql);
    while ($t = $res->fetch_assoc()) {
        foreach ($lista_ids as $key => $lid) {
            if ($t['lista_id'] == $lid) {
                $tarjetas[$key][] = $t;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MeritumDev</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Animaciones personalizadas */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 5px rgba(59, 130, 246, 0.5); }
            50% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.8); }
        }
        
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% { transform: translate3d(0,0,0); }
            40%, 43% { transform: translate3d(0, -30px, 0); }
            70% { transform: translate3d(0, -15px, 0); }
            90% { transform: translate3d(0, -4px, 0); }
        }
        
        /* Clases de animación */
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .animate-slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }
        
        .animate-pulse-slow {
            animation: pulse 2s infinite;
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .animate-glow {
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        .animate-bounce-slow {
            animation: bounce 2s infinite;
        }
        
        /* Efectos hover mejorados */
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .hover-scale {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        /* Gradientes modernos */
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .gradient-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .gradient-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .gradient-warning {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        /* Sidebar mejorado */
        .sidebar {
            background: linear-gradient(180deg, #1e3a8a 0%, #3730a3 50%, #5b21b6 100%);
            backdrop-filter: blur(10px);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .sidebar-item:hover::before {
            left: 100%;
        }
        
        .sidebar-item:hover {
            transform: translateX(10px);
            background: rgba(59, 130, 246, 0.3);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .sidebar-item.active {
            background: rgba(59, 130, 246, 0.4);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            border-left: 4px solid #3b82f6;
        }
        
        /* Cards con efectos */
        .card-modern {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-modern:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        /* Kanban mejorado */
        .kanban-col { 
            min-width: 270px; 
            max-width: 320px; 
            transition: all 0.3s ease;
        }
        
        .kanban-card { 
            cursor: grab; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .kanban-card:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .kanban-card.dragging { 
            opacity: 0.5; 
            transform: rotate(5deg) scale(1.05);
        }
        
        .kanban-dropzone { 
            min-height: 60px; 
            transition: all 0.3s ease;
        }
        
        .kanban-dropzone.drag-over {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
            border: 2px dashed #3b82f6;
            border-radius: 12px;
            transform: scale(1.02);
        }
        
        /* Spinner mejorado */
        .spinner { 
            border: 4px solid rgba(59, 130, 246, 0.1); 
            border-top: 4px solid #3b82f6; 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            animation: spin 1s linear infinite; 
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
        
        /* Modal mejorado */
        .modal-bg { 
            background: rgba(15, 23, 42, 0.8); 
            backdrop-filter: blur(8px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        /* Asegurar que los modales estén ocultos por defecto */
        #modal-ver-tarjeta,
        #modal-nueva-tarjeta,
        #modal-nuevo-evento,
        #modal-ver-evento {
            display: none !important;
        }
        
        #modal-ver-tarjeta:not(.hidden),
        #modal-nueva-tarjeta:not(.hidden),
        #modal-nuevo-evento:not(.hidden),
        #modal-ver-evento:not(.hidden) {
            display: flex !important;
        }
        
        /* Debug: Forzar que la vista inicial se muestre si no hay ninguna activa */
        .dashboard-view.hidden {
            display: none !important;
        }
        
        .dashboard-view:not(.hidden) {
            display: block !important;
        }
        
        /* Estilos para notificaciones */
        .notification {
            animation: slideInRight 0.3s ease-out;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Modal de ver tarjeta - Dimensiones corregidas */
        #modal-ver-tarjeta .modal-content {
            width: 95vw !important;
            max-width: 1600px !important;
            height: 90vh !important;
            max-height: 90vh !important;
            margin: 0 !important;
            position: relative !important;
        }
        
        /* Modal de nueva tarjeta - Dimensiones corregidas */
        #modal-nueva-tarjeta .modal-content {
            width: 95vw !important;
            max-width: 800px !important;
            height: auto !important;
            max-height: 90vh !important;
            margin: 0 !important;
            position: relative !important;
        }
        
        /* Modal de nuevo evento - Dimensiones corregidas */
        #modal-nuevo-evento .modal-content {
            width: 95vw !important;
            max-width: 500px !important;
            height: auto !important;
            max-height: 90vh !important;
            margin: 0 !important;
            position: relative !important;
        }
        
        /* Modal de ver evento - Dimensiones corregidas */
        #modal-ver-evento .modal-content {
            width: 95vw !important;
            max-width: 600px !important;
            height: auto !important;
            max-height: 90vh !important;
            margin: 0 !important;
            position: relative !important;
        }
        
        .modal-content {
            animation: fadeInUp 0.4s ease-out;
            overflow-y: auto;
        }
        
        /* Botones modernos */
        .btn-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Stats cards */
        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(59, 130, 246, 0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        }
        
        /* Loading animation */
        .loading-dots {
            display: inline-block;
        }
        
        .loading-dots::after {
            content: '';
            animation: dots 1.5s steps(5, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }
    </style>
    <script>
    let draggedCard = null;
    let spinner = null;
    
    function showSpinner(colId) {
        spinner = document.createElement('div');
        spinner.className = 'spinner my-4';
        document.getElementById(colId).prepend(spinner);
    }
    
    function hideSpinner() {
        if (spinner) spinner.remove();
    }
    
    // Función para agregar efectos de entrada
    function addEntranceEffects() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        });
        
        elements.forEach(el => observer.observe(el));
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded ejecutándose...');
        
        // Sidebar SPA con animaciones
        const links = document.querySelectorAll('.sidebar-link');
        const views = document.querySelectorAll('.dashboard-view');
        
        console.log('Links encontrados:', links.length);
        console.log('Views encontradas:', views.length);
        
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Click en sidebar link:', this.getAttribute('data-view'));
                
                // Remover clases activas
                links.forEach(l => {
                    l.classList.remove('active');
                });
                
                // Agregar clases activas con animación
                this.classList.add('active');
                
                // Ocultar todas las vistas con animación
                views.forEach(v => {
                    v.style.opacity = '0';
                    v.style.transform = 'translateY(20px)';
                    setTimeout(() => v.classList.add('hidden'), 200);
                });
                
                // Mostrar vista seleccionada con animación
                const target = this.getAttribute('data-view');
                const targetView = document.getElementById(target);
                
                console.log('Target view:', target, 'Elemento encontrado:', !!targetView);
                
                setTimeout(() => {
                    if (targetView) {
                        targetView.classList.remove('hidden');
                        targetView.style.opacity = '1';
                        targetView.style.transform = 'translateY(0)';
                        console.log('Vista mostrada:', target);
                    } else {
                        console.error('Vista no encontrada:', target);
                    }
                }, 250);
            });
        });
        
        // Activar vista según parámetro ?view=...
        const params = new URLSearchParams(window.location.search);
        const view = params.get('view');
        console.log('Parámetro view en URL:', view);
        
        if (view && document.querySelector('.sidebar-link[data-view="' + view + '"]')) {
            console.log('Activando vista desde URL:', view);
            document.querySelector('.sidebar-link[data-view="' + view + '"]').click();
        } else {
            console.log('Activando vista por defecto: inicio-view');
            const inicioLink = document.querySelector('.sidebar-link[data-view="inicio-view"]');
            if (inicioLink) {
                inicioLink.click();
            } else {
                console.error('Link de inicio no encontrado');
                // Fallback: mostrar la vista de inicio directamente
                const inicioView = document.getElementById('inicio-view');
                if (inicioView) {
                    inicioView.classList.remove('hidden');
                    inicioView.style.opacity = '1';
                    inicioView.style.transform = 'translateY(0)';
                    console.log('Vista de inicio mostrada por fallback');
                }
            }
        }
        
        // Verificación final: asegurar que al menos una vista esté visible
        setTimeout(() => {
            const visibleViews = document.querySelectorAll('.dashboard-view:not(.hidden)');
            console.log('Vistas visibles después de inicialización:', visibleViews.length);
            
            if (visibleViews.length === 0) {
                console.log('No hay vistas visibles, mostrando inicio-view');
                const inicioView = document.getElementById('inicio-view');
                if (inicioView) {
                    inicioView.classList.remove('hidden');
                    inicioView.style.opacity = '1';
                    inicioView.style.transform = 'translateY(0)';
                }
            }
        }, 500);

        // Kanban Drag & Drop mejorado
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.addEventListener('dragstart', function(e) {
                draggedCard = this;
                this.classList.add('dragging');
                this.style.transform = 'rotate(5deg) scale(1.05)';
            });
            
            card.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
                this.style.transform = '';
                draggedCard = null;
            });
        });
        
        // Configurar modal de ver tarjeta - Event listener centralizado abajo
        
        // Cerrar modales al hacer clic fuera
        document.addEventListener('click', function(e) {
            const modalVer = document.getElementById('modal-ver-tarjeta');
            const modalNueva = document.getElementById('modal-nueva-tarjeta');
            const modalNuevoEvento = document.getElementById('modal-nuevo-evento');
            const modalVerEvento = document.getElementById('modal-ver-evento');
            
            if (e.target === modalVer) {
                modalVer.classList.add('hidden');
            }
            
            if (e.target === modalNueva) {
                modalNueva.classList.add('hidden');
            }
            
            if (e.target === modalNuevoEvento) {
                cerrarModalNuevoEvento();
            }
            
            if (e.target === modalVerEvento) {
                cerrarModalVerEvento();
            }
        });
        
        // Configurar cerrar modal de nueva tarjeta
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'close-modal-tarjeta') {
                document.getElementById('modal-nueva-tarjeta').classList.add('hidden');
            }
            if (e.target && e.target.id === 'close-modal-ver-tarjeta') {
                document.getElementById('modal-ver-tarjeta').classList.add('hidden');
            }
        });
        
        // Configurar formulario de nuevo evento
        const formNuevoEvento = document.getElementById('form-nuevo-evento');
        if (formNuevoEvento) {
            formNuevoEvento.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Mostrar loading en el botón
                const submitBtn = formNuevoEvento.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<div class="spinner w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div> Guardando...';
                submitBtn.disabled = true;
                
                // Procesar formulario con AJAX
                const formData = new FormData(formNuevoEvento);
                
                fetch('procesar_evento.php', {
                    method: 'POST',
                    body: formData
                })
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
                        // Mostrar notificación de éxito
                        mostrarNotificacion('Evento guardado correctamente', 'success');
                        cerrarModalNuevoEvento();
                        
                        // Recargar la página para actualizar el calendario
                        window.location.reload();
                    } else {
                        mostrarNotificacion(data.message || 'Error al guardar el evento', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarNotificacion('Error al guardar el evento', 'error');
                })
                .finally(() => {
                    // Restaurar botón
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        }
        
        // Asegurar que los modales estén ocultos al cargar la página
        const modalVerTarjeta = document.getElementById('modal-ver-tarjeta');
        const modalNuevaTarjeta = document.getElementById('modal-nueva-tarjeta');
        const modalNuevoEvento = document.getElementById('modal-nuevo-evento');
        const modalVerEvento = document.getElementById('modal-ver-evento');
        
        if (modalVerTarjeta) {
            modalVerTarjeta.classList.add('hidden');
            console.log('Modal ver tarjeta oculto al cargar la página');
        }
        
        if (modalNuevaTarjeta) {
            modalNuevaTarjeta.classList.add('hidden');
            console.log('Modal nueva tarjeta oculto al cargar la página');
        }
        
        if (modalNuevoEvento) {
            modalNuevoEvento.classList.add('hidden');
            console.log('Modal nuevo evento oculto al cargar la página');
        }
        
        if (modalVerEvento) {
            modalVerEvento.classList.add('hidden');
            console.log('Modal ver evento oculto al cargar la página');
        }
        
        // Función para mostrar notificaciones modernas
        window.mostrarNotificacion = function(mensaje, tipo = 'success') {
            const notificacion = document.createElement('div');
            notificacion.className = `fixed top-4 right-4 z-50 p-4 notification ${
                tipo === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notificacion.textContent = mensaje;
            document.body.appendChild(notificacion);
            
            setTimeout(() => {
                notificacion.remove();
            }, 3000);
        };
        
        // Función para cambiar a la vista del calendario
        window.cambiarVistaCalendario = function() {
            const calendarioLink = document.querySelector('.sidebar-link[data-view="calendario-view"]');
            if (calendarioLink) {
                calendarioLink.click();
            }
        };
        
        // Funciones del calendario (nivel global)
        window.abrirModalNuevoEvento = function() {
            console.log('Abriendo modal nuevo evento...');
            
            // Verificar si la modal ya está abierta
            const modal = document.getElementById('modal-nuevo-evento');
            if (modal && !modal.classList.contains('hidden')) {
                console.log('Modal nuevo evento ya está abierta');
                return;
            }
            
            // Establecer fecha actual por defecto
            const fechaActual = new Date().toISOString().split('T')[0];
            const horaActual = new Date().toLocaleTimeString('es-ES', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const fechaInput = document.getElementById('fecha-evento');
            const horaInput = document.getElementById('hora-evento');
            
            if (fechaInput) fechaInput.value = fechaActual;
            if (horaInput) horaInput.value = horaActual;
            
            if (modal) {
                modal.classList.remove('hidden');
                console.log('Modal nuevo evento abierta');
            } else {
                console.log('Modal no encontrada');
            }
        };
        
        window.cerrarModalNuevoEvento = function() {
            console.log('Cerrando modal nuevo evento...');
            const modal = document.getElementById('modal-nuevo-evento');
            if (modal) {
                modal.classList.add('hidden');
                console.log('Modal cerrada');
            }
            document.getElementById('form-nuevo-evento').reset();
        };
        
        window.cerrarModalVerEvento = function() {
            console.log('Cerrando modal ver evento...');
            const modal = document.getElementById('modal-ver-evento');
            if (modal) {
                modal.classList.add('hidden');
                console.log('Modal ver evento cerrada');
            }
            window.eventoActual = null;
        };
        
        window.verEvento = function(evento) {
            console.log('Abriendo modal ver evento...', evento);
            
            // Verificar si la modal ya está abierta
            const modal = document.getElementById('modal-ver-evento');
            if (modal && !modal.classList.contains('hidden')) {
                console.log('Modal ya está abierta, cerrando primero...');
                cerrarModalVerEvento();
                return;
            }
            
            if (modal) {
                // Llenar la modal con la información del evento
                document.getElementById('evento-titulo').textContent = 'Detalles del Evento';
                document.getElementById('evento-titulo-detalle').textContent = evento.titulo;
                document.getElementById('evento-descripcion').textContent = evento.descripcion || 'Sin descripción';
                
                const fechaEvento = new Date(evento.fecha_hora);
                document.getElementById('evento-fecha').textContent = fechaEvento.toLocaleDateString('es-ES', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                document.getElementById('evento-hora').textContent = fechaEvento.toLocaleTimeString('es-ES', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Configurar tipo con color
                const tipoElement = document.getElementById('evento-tipo');
                const tipoText = evento.tipo === 'reunion' ? 'Reunión' : 
                                evento.tipo === 'tarea' ? 'Tarea' : 
                                evento.tipo === 'recordatorio' ? 'Recordatorio' : 'Evento';
                
                const tipoColor = evento.tipo === 'reunion' ? 'bg-blue-100 text-blue-800' : 
                                 evento.tipo === 'tarea' ? 'bg-green-100 text-green-800' : 
                                 evento.tipo === 'recordatorio' ? 'bg-orange-100 text-orange-800' : 'bg-purple-100 text-purple-800';
                
                tipoElement.textContent = tipoText;
                tipoElement.className = `inline-block px-3 py-1 rounded-full text-sm font-medium ${tipoColor}`;
                
                document.getElementById('evento-creador').textContent = evento.creador_nombre || 'Usuario';
                
                // Guardar el evento actual para edición
                window.eventoActual = evento;
                
                // Mostrar la modal
                modal.classList.remove('hidden');
                console.log('Modal ver evento abierta');
            } else {
                console.log('Modal ver evento no encontrada');
            }
        };
        
        window.editarEvento = function() {
            if (window.eventoActual) {
                mostrarNotificacion('Función de edición próximamente disponible', 'error');
                cerrarModalVerEvento();
            }
        };
        
        window.verEventoDesdeElemento = function(elemento) {
            // Prevenir que se abra la modal durante la inicialización
            if (!window.calendarioInicializado) {
                console.log('Calendario no inicializado, ignorando clic en evento');
                return;
            }
            
            try {
                const eventoData = elemento.getAttribute('data-evento');
                if (eventoData) {
                    const evento = JSON.parse(eventoData);
                    verEvento(evento);
                } else {
                    console.error('No se encontraron datos del evento en el elemento');
                }
            } catch (error) {
                console.error('Error al parsear datos del evento:', error);
            }
        };
        
        document.querySelectorAll('.kanban-dropzone').forEach(zone => {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });
            
            zone.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            zone.addEventListener('drop', function(e) {
                this.classList.remove('drag-over');
                if (draggedCard) {
                    const tarjetaId = draggedCard.getAttribute('data-id');
                    const newListaId = this.getAttribute('data-lista');
                    showSpinner(this.id);
                    
                    fetch('mover_tarjeta.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `tarjeta_id=${tarjetaId}&lista_id=${newListaId}`
                    })
                    .then(res => res.text())
                    .then(() => { 
                        // Animación de éxito antes de recargar
                        draggedCard.style.transform = 'scale(1.1)';
                        draggedCard.style.opacity = '0.8';
                        setTimeout(() => window.location.reload(), 300);
                    })
                    .catch(() => { 
                        hideSpinner(); 
                        mostrarNotificacion('Error al mover la tarjeta', 'error');
                    });
                }
            });
        });

        // Modal Nueva Tarjeta mejorado - Eliminado código duplicado
        // El modal se maneja en vistas/actividades.php
        
        // Agregar efectos de entrada
        addEntranceEffects();
        
        // Animación de bienvenida
        setTimeout(() => {
            const welcomeCard = document.querySelector('.welcome-card');
            if (welcomeCard) {
                welcomeCard.classList.add('animate-fade-in-up');
            }
        }, 500);
    });
    </script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex">
    <!-- Sidebar Moderno -->
    <aside class="sidebar w-64 text-white flex flex-col py-8 px-4 min-h-screen shadow-2xl relative overflow-hidden">
        <!-- Efecto de fondo animado -->
        <div class="gradient-primary absolute inset-0 bg-gradient-to-br from-blue-600/30 to-purple-600/30 animate-pulse-slow"></div>
        
        <div class="relative z-10">
            <div class="flex flex-col items-center mb-10 animate-fade-in-up">
                <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg animate-float">
                    <i class="fas fa-rocket text-white text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">MeritumDev</h1>
                <span class="text-blue-200 text-sm text-center">Bienvenido, <?php echo htmlspecialchars($nombre); ?></span>
                <div class="mt-2 px-3 py-1 bg-blue-600/60 rounded-full text-xs text-white">
                    <i class="fas fa-crown mr-1"></i><?php echo ucfirst($rol); ?>
                </div>
            </div>
            
            <nav class="flex-1">
                <ul class="space-y-3">
                    <li>
                        <a href="#" class="sidebar-link sidebar-item flex items-center gap-4 px-4 py-4 rounded-xl font-semibold text-white transition-all duration-300" data-view="inicio-view">
                            <i class="fas fa-home text-lg text-blue-200"></i>
                            <span>Inicio</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="sidebar-link sidebar-item flex items-center gap-4 px-4 py-4 rounded-xl font-semibold text-white transition-all duration-300" data-view="actividades-view">
                            <i class="fas fa-tasks text-lg text-green-200"></i>
                            <span>Actividades</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="sidebar-link sidebar-item flex items-center gap-4 px-4 py-4 rounded-xl font-semibold text-white transition-all duration-300" data-view="calendario-view">
                            <i class="fas fa-calendar-alt text-lg text-purple-200"></i>
                            <span>Calendario</span>
                        </a>
                    </li>
                    <?php if ($rol === 'admin'): ?>
                    <li>
                        <a href="#" class="sidebar-link sidebar-item flex items-center gap-4 px-4 py-4 rounded-xl font-semibold text-white transition-all duration-300" data-view="usuarios-view">
                            <i class="fas fa-users text-lg text-orange-200"></i>
                            <span>Usuarios</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="mt-10">
                <a href="logout.php" class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 text-center flex items-center justify-center gap-2 shadow-lg hover:shadow-xl">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar sesión
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto">
        <!-- INICIO -->
        <section id="inicio-view" class="dashboard-view hidden" style="transition: all 0.3s ease;">
            <div class="animate-fade-in-up">
                <h2 class="text-4xl font-bold text-gray-800 mb-8 flex items-center gap-3">
                    <i class="fas fa-home text-blue-600"></i>
                    Dashboard
                </h2>
                
                <!-- Welcome Card -->
                <div class="welcome-card rounded-2xl shadow-xl p-8 mb-8 gradient-primary">
                    <div class="flex flex-col lg:flex-row items-center gap-8">
                        <div class="flex-1">
                            <h3 class="text-3xl font-bold text-white mb-4">¡Bienvenido a tu Dashboard, <?php echo htmlspecialchars($nombre); ?>!</h3>
                            <p class="text-xl text-white opacity-90 mb-6">Gestiona tus actividades y revisa tu calendario de manera sencilla y moderna.</p>
                            <div class="flex flex-wrap gap-4">
                                <div class="flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full">
                                    <i class="fas fa-check-circle text-white"></i>
                                    <span class="text-white">Gestión de tareas</span>
                                </div>
                                <div class="flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full">
                                    <i class="fas fa-calendar text-white"></i>
                                    <span class="text-white">Calendario integrado</span>
                                </div>
                                <div class="flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full">
                                    <i class="fas fa-users text-white"></i>
                                    <span class="text-white">Colaboración en equipo</span>
                                </div>
                            </div>
                        </div>
                        <div class="animate-float">
                            <i class="fas fa-rocket text-white text-8xl opacity-80"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card rounded-2xl p-6 hover-lift">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-medium">Total Tareas</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo array_sum(array_map('count', $tarjetas)); ?></p>
                            </div>
                                                         <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                 <i class="fas fa-tasks text-blue-700 text-xl"></i>
                             </div>
                        </div>
                    </div>
                    
                    <div class="stat-card rounded-2xl p-6 hover-lift">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-medium">En Proceso</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo isset($tarjetas['en_proceso']) ? count($tarjetas['en_proceso']) : 0; ?></p>
                            </div>
                                                         <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                                 <i class="fas fa-clock text-yellow-700 text-xl"></i>
                             </div>
                        </div>
                    </div>
                    
                    <div class="stat-card rounded-2xl p-6 hover-lift">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-medium">Completadas</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo isset($tarjetas['finalizado']) ? count($tarjetas['finalizado']) : 0; ?></p>
                            </div>
                                                         <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                 <i class="fas fa-check-circle text-green-700 text-xl"></i>
                             </div>
                        </div>
                    </div>
                    
                    <div class="stat-card rounded-2xl p-6 hover-lift">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-medium">Pendientes</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo isset($tarjetas['pendiente']) ? count($tarjetas['pendiente']) : 0; ?></p>
                            </div>
                                                         <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                 <i class="fas fa-exclamation-triangle text-red-700 text-xl"></i>
                             </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="card-modern rounded-2xl p-6 hover-lift">
                                                 <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                             <i class="fas fa-plus-circle text-blue-700"></i>
                             Acciones Rápidas
                         </h3>
                        <div class="space-y-3">
                                                         <button onclick="document.querySelector('[data-view=\'actividades-view\']').click()" class="w-full text-left p-3 rounded-xl bg-blue-50 hover:bg-blue-100 transition-colors flex items-center gap-3">
                                 <i class="fas fa-plus text-blue-700"></i>
                                 <span class="text-gray-800">Crear nueva tarea</span>
                             </button>
                             <button onclick="cambiarVistaCalendario()" class="w-full text-left p-3 rounded-xl bg-purple-50 hover:bg-purple-100 transition-colors flex items-center gap-3">
                                 <i class="fas fa-calendar-plus text-purple-700"></i>
                                 <span class="text-gray-800">Agregar evento</span>
                             </button>
                        </div>
                    </div>
                    
                    <div class="card-modern rounded-2xl p-6 hover-lift">
                                                 <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                             <i class="fas fa-chart-line text-green-700"></i>
                             Progreso del Día
                         </h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Tareas completadas</span>
                                    <span><?php echo isset($tarjetas['finalizado']) ? count($tarjetas['finalizado']) : 0; ?></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo min(100, (isset($tarjetas['finalizado']) ? count($tarjetas['finalizado']) : 0) * 20); ?>%"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-800">¡Mantén el ritmo!</p>
                                <p class="text-gray-600">Sigue así con tu productividad</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- ACTIVIDADES -->
        <section id="actividades-view" class="dashboard-view hidden" style="transition: all 0.3s ease;">
            <?php include __DIR__ . '/vistas/actividades.php'; ?>
        </section>
        
        <!-- CALENDARIO -->
        <section id="calendario-view" class="dashboard-view hidden" style="transition: all 0.3s ease;">
            <?php include __DIR__ . '/vistas/calendario.php'; ?>
        </section>
        
        <?php if ($rol === 'admin'): ?>
        <!-- USUARIOS (solo admin) -->
        <section id="usuarios-view" class="dashboard-view hidden" style="transition: all 0.3s ease;">
            <?php include __DIR__ . '/vistas/usuarios.php'; ?>
        </section>
        <?php endif; ?>
    </main>
    
    <!-- Modal Ver Tarjeta (Nivel superior) -->
    <div id="modal-ver-tarjeta" class="fixed inset-0 z-[9999] flex items-center justify-center hidden modal-bg">
        <div class="modal-content bg-white rounded-2xl shadow-2xl p-8 relative overflow-y-auto">
            <button id="close-modal-ver-tarjeta" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl hover-scale z-10">&times;</button>
            <div id="contenido-tarjeta" class="h-full">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>
    
    <!-- Modal Nueva Tarjeta (Nivel superior) -->
    <div id="modal-nueva-tarjeta" class="fixed inset-0 z-[9999] flex items-center justify-center hidden modal-bg">
        <div class="modal-content bg-white rounded-2xl shadow-2xl p-8 relative overflow-y-auto">
            <button id="close-modal-tarjeta" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl hover-scale z-10">&times;</button>
            <div id="contenido-nueva-tarjeta">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>
    
    <!-- Modal Nuevo Evento (Nivel superior) -->
    <div id="modal-nuevo-evento" class="fixed inset-0 z-[9999] flex items-center justify-center hidden modal-bg">
        <div class="modal-content bg-white rounded-2xl shadow-2xl p-8 relative overflow-y-auto max-w-md w-full mx-4">
            <button onclick="cerrarModalNuevoEvento()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl hover-scale z-10">&times;</button>
            
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-3">
                    <i class="fas fa-plus-circle text-purple-600"></i>
                    Nuevo Evento
                </h3>
                <p class="text-gray-600">Crea un nuevo evento en tu calendario</p>
            </div>

            <form id="form-nuevo-evento" class="space-y-4">
                <input type="hidden" name="crear_evento" value="1">
                <div>
                    <label class="block text-gray-800 font-semibold mb-2" for="titulo-evento">Título *</label>
                    <input type="text" id="titulo-evento" name="titulo" required 
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white text-gray-800">
                </div>

                <div>
                    <label class="block text-gray-800 font-semibold mb-2" for="descripcion-evento">Descripción</label>
                    <textarea id="descripcion-evento" name="descripcion" rows="3" 
                              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white text-gray-800"
                              placeholder="Describe el evento..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-800 font-semibold mb-2" for="fecha-evento">Fecha *</label>
                        <input type="date" id="fecha-evento" name="fecha" required 
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white text-gray-800">
                    </div>
                    <div>
                        <label class="block text-gray-800 font-semibold mb-2" for="hora-evento">Hora *</label>
                        <input type="time" id="hora-evento" name="hora" required 
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white text-gray-800">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-800 font-semibold mb-2" for="tipo-evento">Tipo de Evento</label>
                    <select id="tipo-evento" name="tipo" 
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white text-gray-800">
                        <option value="reunion">Reunión</option>
                        <option value="tarea">Tarea</option>
                        <option value="recordatorio">Recordatorio</option>
                        <option value="evento">Evento</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="cerrarModalNuevoEvento()" 
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 btn-modern text-white font-bold py-3 px-6 rounded-xl transition-all duration-300">
                        <i class="fas fa-save mr-2"></i>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Ver Evento (Nivel superior) -->
    <div id="modal-ver-evento" class="fixed inset-0 z-[9999] flex items-center justify-center hidden modal-bg">
        <div class="modal-content bg-white rounded-2xl shadow-2xl p-8 relative overflow-y-auto max-w-lg w-full mx-4">
            <button onclick="cerrarModalVerEvento()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl hover-scale z-10">&times;</button>
            
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-800 mb-2 flex items-center gap-3">
                    <i class="fas fa-calendar-check text-purple-600"></i>
                    <span id="evento-titulo">Detalles del Evento</span>
                </h3>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-gray-600 font-semibold mb-2">Título</label>
                    <div id="evento-titulo-detalle" class="text-gray-800 font-medium text-lg"></div>
                </div>

                <div>
                    <label class="block text-gray-600 font-semibold mb-2">Descripción</label>
                    <div id="evento-descripcion" class="text-gray-700 bg-gray-50 p-3 rounded-lg"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2">Fecha</label>
                        <div id="evento-fecha" class="text-gray-800"></div>
                    </div>
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2">Hora</label>
                        <div id="evento-hora" class="text-gray-800"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-600 font-semibold mb-2">Tipo</label>
                    <div id="evento-tipo" class="inline-block px-3 py-1 rounded-full text-sm font-medium"></div>
                </div>

                <div>
                    <label class="block text-gray-600 font-semibold mb-2">Creado por</label>
                    <div id="evento-creador" class="text-gray-800"></div>
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <button onclick="cerrarModalVerEvento()" 
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300">
                    Cerrar
                </button>
                <button onclick="editarEvento()" 
                        class="flex-1 btn-modern text-white font-bold py-3 px-6 rounded-xl transition-all duration-300">
                    <i class="fas fa-edit mr-2"></i>
                    Editar
                </button>
            </div>
        </div>
    </div>
</body>
</html> 