<?php
session_start();
require_once __DIR__ . '/conexion.php';

// Verificar si hay tarjetas para probar
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM tarjetas');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$tarjetas_count = $row['count'];

// Verificar si hay listas
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM listas');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$listas_count = $row['count'];

// Verificar si hay usuarios
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM usuarios WHERE rol = "miembro" AND activo = 1');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$usuarios_count = $row['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Drag & Drop - MeritumDev</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-900 mb-8">🧪 Test Drag & Drop</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Estado del Sistema -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-blue-800 mb-4">📊 Estado del Sistema</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tarjetas:</span>
                        <span class="font-semibold <?php echo $tarjetas_count > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $tarjetas_count; ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Listas:</span>
                        <span class="font-semibold <?php echo $listas_count >= 5 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $listas_count; ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Usuarios miembros:</span>
                        <span class="font-semibold <?php echo $usuarios_count > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $usuarios_count; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Instrucciones -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-blue-800 mb-4">📝 Instrucciones</h2>
                <div class="space-y-2 text-sm text-gray-700">
                    <p>✅ <strong>Para probar el drag & drop:</strong></p>
                    <ol class="list-decimal list-inside space-y-1 ml-4">
                        <li>Ve al <a href="dashboard.php" class="text-blue-600 hover:underline">Dashboard</a></li>
                        <li>Crea algunas tarjetas si no hay</li>
                        <li>Intenta arrastrar una tarjeta a otra columna</li>
                        <li>Verifica que se actualice en la base de datos</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <!-- Problemas Comunes -->
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-semibold text-blue-800 mb-4">🔧 Solución de Problemas</h2>
            
            <?php if ($tarjetas_count == 0): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <h3 class="font-semibold text-yellow-800 mb-2">⚠️ No hay tarjetas para probar</h3>
                <p class="text-yellow-700 mb-3">Necesitas crear tarjetas primero para probar el drag & drop.</p>
                <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Ir al Dashboard</a>
            </div>
            <?php endif; ?>
            
            <?php if ($usuarios_count == 0): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <h3 class="font-semibold text-red-800 mb-2">❌ No hay usuarios miembros</h3>
                <p class="text-red-700 mb-3">Necesitas crear usuarios con rol "miembro" para poder crear tarjetas.</p>
                <a href="crear_admin.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Crear Admin</a>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-800 mb-2">🎯 Drag & Drop no funciona</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Verifica que JavaScript esté habilitado</li>
                        <li>• Asegúrate de que las tarjetas tengan el atributo <code>draggable="true"</code></li>
                        <li>• Revisa la consola del navegador para errores</li>
                    </ul>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-800 mb-2">💾 No se guarda en BD</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Verifica que <code>mover_tarjeta.php</code> funcione</li>
                        <li>• Revisa los logs de error de PHP</li>
                        <li>• Confirma que la conexión a BD esté correcta</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Enlaces de Acción -->
        <div class="flex flex-wrap gap-4 mt-6">
            <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold">
                🏠 Ir al Dashboard
            </a>
            <a href="verificar_instalacion.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-semibold">
                🔍 Verificar Instalación
            </a>
            <a href="crear_admin.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 font-semibold">
                👤 Crear Admin
            </a>
        </div>
    </div>
</body>
</html> 