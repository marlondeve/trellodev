# MeritumDev - Sistema de Gestión de Proyectos tipo Trello

Un sistema completo de gestión de proyectos inspirado en Trello, desarrollado en PHP con interfaz moderna usando Tailwind CSS.

## 🚀 Características

### ✅ Funcionalidades Implementadas
- **Sistema de usuarios** con roles (admin/miembro)
- **Tableros Kanban** con drag & drop
- **Tarjetas** con título, descripción y fecha de vencimiento
- **Etiquetas** coloridas para categorizar tarjetas
- **Responsables** asignados a tarjetas
- **Comentarios** en tarjetas
- **Archivos adjuntos** (estructura preparada)
- **Estados de tarjetas**: Pendiente, En Proceso, En Aprobación, Detenido, Finalizado
- **Interfaz moderna** con Tailwind CSS
- **Sistema de autenticación** seguro

### 🎯 Próximas Funcionalidades
- Subida de archivos adjuntos
- Notificaciones en tiempo real
- Filtros y búsqueda avanzada
- Múltiples tableros
- Reportes y estadísticas

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: mysqli, session

## 🛠️ Instalación

### 1. Clonar el proyecto
```bash
git clone [URL_DEL_REPOSITORIO]
cd trellodev
```

### 2. Configurar la base de datos
- Crear una base de datos MySQL
- Importar el archivo `database` en tu servidor MySQL
- Actualizar las credenciales en `conexion.php`

### 3. Configurar conexión
Editar `conexion.php` con tus credenciales:
```php
$host = 'tu_host';
$user = 'tu_usuario';
$pass = 'tu_contraseña';
$dbname = 'tu_base_de_datos';
```

### 4. Inicializar datos
Ejecutar el script de inicialización:
```
http://tu-dominio/inicializar_bd.php
```

### 5. Crear usuario administrador
Ejecutar el script de creación de admin:
```
http://tu-dominio/crear_admin.php
```

**Credenciales por defecto:**
- Email: admin@meritumdev.com
- Contraseña: Platino5..

### 6. Acceder al sistema
```
http://tu-dominio/dashboard.php
```

## 🗄️ Estructura de la Base de Datos

### Tablas principales:
- **usuarios**: Gestión de usuarios y roles
- **tableros**: Tableros de proyectos
- **listas**: Columnas dentro de tableros
- **tarjetas**: Tareas individuales
- **etiquetas**: Categorización de tarjetas
- **comentarios**: Comentarios en tarjetas
- **archivos_adjuntos**: Archivos vinculados a tarjetas

## 📁 Estructura del Proyecto

```
trellodev/
├── conexion.php              # Configuración de base de datos
├── dashboard.php             # Panel principal
├── login.php                 # Página de login
├── logout.php                # Cerrar sesión
├── crear_admin.php           # Script para crear admin
├── inicializar_bd.php        # Script de inicialización
├── mover_tarjeta.php         # API para mover tarjetas
├── ver_tarjeta.php           # Vista detallada de tarjeta
├── agregar_comentario.php    # API para comentarios
├── procesar_usuario.php      # Gestión de usuarios
├── database                  # Estructura de BD
├── vistas/
│   ├── actividades.php       # Vista Kanban
│   ├── usuarios.php          # Gestión de usuarios
│   ├── calendario.php        # Vista de calendario
│   └── inicio.php            # Página de inicio
└── README.md                 # Este archivo
```

## 🎨 Características de la Interfaz

- **Diseño responsive** que funciona en móviles y desktop
- **Drag & Drop** para mover tarjetas entre columnas
- **Modales** para crear y ver tarjetas
- **Iconos SVG** para mejor UX
- **Colores consistentes** con tema azul profesional
- **Animaciones suaves** para transiciones

## 🔧 Configuración Avanzada

### Personalizar colores
Editar las clases de Tailwind CSS en los archivos para cambiar el esquema de colores.

### Agregar nuevos estados
1. Modificar el array `$estados` en `vistas/actividades.php`
2. Agregar la lista correspondiente en la base de datos
3. Actualizar el script de inicialización

### Configurar etiquetas
Editar el array de etiquetas en `inicializar_bd.php` para personalizar las etiquetas por defecto.

## 🚨 Seguridad

- **Contraseñas hasheadas** con `password_hash()`
- **Prepared statements** para prevenir SQL injection
- **Validación de sesiones** en todas las páginas
- **Sanitización de datos** de entrada
- **Control de acceso** basado en roles

## 🐛 Solución de Problemas

### Error de conexión a BD
- Verificar credenciales en `conexion.php`
- Asegurar que MySQL esté ejecutándose
- Verificar permisos de usuario de BD

### Tarjetas no se crean
- Ejecutar `inicializar_bd.php` para crear datos iniciales
- Verificar que existan usuarios con rol "miembro"
- Revisar logs de errores de PHP

### Drag & Drop no funciona
- Verificar que JavaScript esté habilitado
- Revisar consola del navegador para errores
- Asegurar que `mover_tarjeta.php` sea accesible

## 📞 Soporte

Para reportar bugs o solicitar nuevas funcionalidades, crear un issue en el repositorio.

## 📄 Licencia

Este proyecto está bajo licencia MIT. Ver archivo LICENSE para más detalles.

---

**Desarrollado con ❤️ para MeritumDev**