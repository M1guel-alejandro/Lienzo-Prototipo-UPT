# Lienzo - Prototipo de Edicion de Imagenes con IA

Aplicacion web desarrollada en PHP y MySQL que integra la API de Pixelcut para escalado de imagenes y eliminacion de fondo, ademas de correccion de color client-side.

## Funcionalidades

- **Escalado de imagenes** - Aumenta resolucion 2x o 4x con la API de Pixelcut
- **Eliminacion de fondo** - Remueve fondos automaticamente con la API de Pixelcut
- **Correccion de color** - Ajusta tono, saturacion, brillo, contraste, exposicion y curvas (100% en el navegador)
- **Sistema de usuarios** - Registro, login y sesiones
- **Panel de administracion** - Gestion de usuarios, creditos y sesiones activas
- **Historial** - Registro de imagenes procesadas por usuario
- **Sistema de creditos** - Limite diario de 5 imagenes y creditos por usuario

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7+ o MariaDB 10.3+
- Extensiones PHP: `pdo_mysql`, `curl`, `mbstring`, `session`
- API key de Pixelcut (https://www.pixelcut.ai/developer-settings)

## Instalacion

### 1. Crear la base de datos

```bash
mysql -u root -p < database.sql
```

O importar el archivo `database.sql` desde phpMyAdmin.

### 2. Configurar la API key

```bash
# Linux/Mac
export PIXA_API_KEY="sk_tu_api_key_aqui"

# Windows (CMD)
set PIXA_API_KEY=sk_tu_api_key_aqui

# Windows (PowerShell)
$env:PIXA_API_KEY="sk_tu_api_key_aqui"
```

Tambien se puede configurar directamente en `config.php`:
```php
define('PIX_API_KEY', 'sk_tu_api_key_aqui');
```

### 3. Configurar credenciales de MySQL

Editar `config.php` si las credenciales de MySQL son distintas:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lienzo');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Crear carpeta de uploads

```bash
mkdir uploads
```

## Ejecutar

### Windows (Recomendado para prototipo)

```bash
php -S localhost:8000 router.php
```

### Linux/Mac

```bash
php -S localhost:8000 router.php
```

Despues abrir `http://localhost:8000` en el navegador.

### Con Apache (opcional)

Si se usa Apache, el archivo `.htaccess` maneja las rutas automaticamente. No es necesario usar `router.php`.

## Credenciales por defecto

| Usuario | Email | Contrasena | Rol |
|---------|-------|------------|-----|
| Administrador | admin@lienzo.com | Admin123 | admin |

> Cambiar el email y password antes de usar en produccion.

## Estructura del proyecto

```
lienzo/
├── router.php          # Router para php built-in server (Windows/Linux)
├── index.php           # Front controller / router principal
├── config.php          # Configuracion, helpers y conexion DB
├── database.sql        # Schema de la base de datos
├── .htaccess           # Reescritura de URLs (solo Apache)
├── api/
│   ├── auth.php        # Registro, login, logout, usuario actual
│   ├── images.php      # Upload, upscale, remove_bg, historial
│   └── admin.php       # Panel admin: sesiones, usuarios, creditos
├── views/
│   ├── index.php       # Dashboard principal
│   ├── login.php       # Formulario de inicio de sesion
│   ├── signup.php      # Formulario de registro
│   ├── admin.php       # Panel de administracion
│   └── history.php     # Historial de imagenes procesadas
├── static/
│   ├── css/styles.css  # Estilos de la aplicacion
│   └── js/
│       ├── script.js   # Logica principal (upscale, remove-bg, color-correct)
│       └── auth.js     # Logica de autenticacion
└── uploads/            # Imagenes subidas por los usuarios
```

## Nota sobre la API de Pixelcut

Las imagenes subidas se alojan localmente en `uploads/`. Para que la API de Pixelcut pueda procesarlas, la imagen debe ser accesible desde internet. Opciones:

1. **URL publica** - Usar una imagen de internet directamente via URL
2. **ngrok** - Exponer el servidor local con `ngrok http 8000`
3. **Desplegar en un servidor** - Subir el proyecto a un hosting con dominio publico

Para prototipos locales, se recomienda usar imagenes con URL publica directamente en el formulario.
# Lienzo-Prototipo-UPT
