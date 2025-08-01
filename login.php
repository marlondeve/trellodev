<?php
session_start();
$mensaje = '';

// Configuración de conexión (ajusta según tu entorno)
$host = '193.203.166.161';
$user = 'u990790165_spacedev';
$pass = 'Platino5..';
$dbname = 'u990790165_spacedev';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Error de conexión a la base de datos: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $stmt = $conn->prepare('SELECT id, nombre, password_hash, rol, activo FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $nombre, $password_hash, $rol, $activo);
            $stmt->fetch();
            if (!$activo) {
                $mensaje = 'Tu cuenta está inactiva. Contacta al administrador.';
            } elseif (password_verify($password, $password_hash)) {
                $_SESSION['usuario_id'] = $id;
                $_SESSION['nombre'] = $nombre;
                $_SESSION['rol'] = $rol;
                header('Location: dashboard.php');
                exit;
            } else {
                $mensaje = 'Contraseña incorrecta.';
            }
        } else {
            $mensaje = 'Usuario no encontrado.';
        }
        $stmt->close();
    } else {
        $mensaje = 'Completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MeritumDev</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-700 to-blue-400 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="flex flex-col items-center mb-6">
            <div class="w-16 h-16 bg-blue-700 rounded-full flex items-center justify-center mb-2">
                <span class="text-white text-3xl font-bold">M</span>
            </div>
            <h1 class="text-2xl font-bold text-blue-800 mb-1">MeritumDev</h1>
            <p class="text-blue-500">Acceso a la plataforma</p>
        </div>
        <?php if ($mensaje): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4 text-center text-sm">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="space-y-5">
            <div>
                <label for="email" class="block text-blue-900 font-semibold mb-1">Correo electrónico</label>
                <input type="email" name="email" id="email" required class="w-full border border-blue-200 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="password" class="block text-blue-900 font-semibold mb-1">Contraseña</label>
                <input type="password" name="password" id="password" required class="w-full border border-blue-200 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 rounded transition">Iniciar sesión</button>
        </form>
        <div class="mt-6 text-center text-blue-400 text-xs">&copy; <?php echo date('Y'); ?> MeritumDev. Todos los derechos reservados.</div>
    </div>
</body>
</html> 