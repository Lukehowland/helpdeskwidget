# 🎫 Helpdesk Widget para Laravel

Widget embebible para integrar el sistema de tickets de Helpdesk en proyectos Laravel externos.

## ✨ Características

- 🔐 Autenticación automática/manual con Helpdesk
- 📱 Widget responsive embebido en iframe
- 🎨 Compatible con AdminLTE v3
- 🔧 Comando de instalación incluido
- 📐 Altura dinámica via postMessage

## 📋 Requisitos

- PHP 8.2+
- Laravel 11.x o 12.x
- Guzzle HTTP Client

## 🚀 Instalación

### 1. Instalar el paquete

```bash
composer require lukehowland/helpdeskwidget
```

### 2. Ejecutar el instalador

```bash
php artisan helpdeskwidget:install
```

Este comando:
- ✅ Publica `config/helpdeskwidget.php`
- ✅ Crea `resources/views/helpdesk.blade.php`
- ✅ Agrega ruta `/helpdesk` a `routes/web.php`
- ✅ Muestra instrucciones para AdminLTE

### 3. Configurar variables de entorno

Añade estas líneas a tu archivo `.env`:

```env
HELPDESK_API_URL=https://proyecto-de-ultimo-minuto.online
HELPDESK_API_KEY=tu-api-key-aqui
```

> **Nota**: El API Key es proporcionado por el administrador de Helpdesk cuando registra tu empresa.

### 4. Limpiar caché

```bash
php artisan config:clear
```

### 5. ¡Listo!

Visita `/helpdesk` en tu navegador.

---

## 🛠️ Opciones del Instalador

```bash
# Instalación básica
php artisan helpdeskwidget:install

# Sobrescribir archivos existentes
php artisan helpdeskwidget:install --force

# No agregar ruta automáticamente
php artisan helpdeskwidget:install --skip-route

# No mostrar instrucciones de AdminLTE
php artisan helpdeskwidget:install --skip-adminlte
```

---

## 📦 Uso Manual (sin instalador)

### En cualquier vista Blade

```blade
{{-- Uso básico --}}
<x-helpdesk-widget />

{{-- Con parámetros personalizados --}}
<x-helpdesk-widget 
    height="800px" 
    width="100%" 
    :border="true" 
/>
```

### Publicar configuración manualmente

```bash
php artisan vendor:publish --tag=helpdeskwidget-config
```

---

## 🎨 Integración con AdminLTE v3

Si usas AdminLTE, agrega esto a tu `config/adminlte.php` en el array `menu`:

```php
['header' => 'SOPORTE'],
[
    'text' => 'Centro de Soporte',
    'url' => 'helpdesk',
    'icon' => 'fas fa-fw fa-headset',
],
```

---

## ⚙️ Configuración Avanzada

Archivo: `config/helpdeskwidget.php`

```php
return [
    // URL del servidor Helpdesk
    'api_url' => env('HELPDESK_API_URL', 'https://helpdesk.example.com'),

    // API Key de tu empresa
    'api_key' => env('HELPDESK_API_KEY', ''),

    // Dimensiones del iframe
    'iframe_height' => env('HELPDESK_WIDGET_HEIGHT', '600px'),
    'iframe_width' => env('HELPDESK_WIDGET_WIDTH', '100%'),
    'iframe_border' => env('HELPDESK_WIDGET_BORDER', false),

    // Cache de tokens (en minutos)
    'token_cache_ttl' => env('HELPDESK_TOKEN_CACHE_TTL', 55),

    // Debug mode
    'debug' => env('HELPDESK_DEBUG', false),
];
```

---

## 🔄 Flujo de Autenticación

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Tu Proyecto    │────▶│  Widget Package  │────▶│    Helpdesk     │
│  (auth user)    │     │  (API calls)     │     │   (API + View)  │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

1. **Usuario detectado**: Lee `auth()->user()` para obtener email y nombre
2. **Verificación**: Consulta a Helpdesk si el usuario ya tiene cuenta
3. **Login automático**: Si existe, obtiene token JWT
4. **Registro**: Si no existe, muestra formulario para crear contraseña
5. **Widget**: Muestra la interfaz de tickets

---

## 🔧 Personalización del Modelo User

El componente busca automáticamente estos atributos en tu modelo User:

```php
// Intentos en orden:
$user->first_name
$user->name        // Separa por espacios
$user->profile->first_name  // Si existe relación
```

Si tu modelo tiene atributos diferentes, puedes extender el componente:

```php
// app/View/Components/CustomHelpdeskWidget.php
namespace App\View\Components;

use Lukehowland\HelpdeskWidget\View\Components\HelpdeskWidget;

class CustomHelpdeskWidget extends HelpdeskWidget
{
    protected function getUserFirstName($user): string
    {
        return $user->primer_nombre; // Tu atributo personalizado
    }
    
    protected function getUserLastName($user): string
    {
        return $user->apellido;
    }
}
```

---

## 🐛 Solución de Problemas

### "API Key inválida"
- Verifica que `HELPDESK_API_KEY` esté en tu `.env`
- Confirma que tu empresa esté registrada en Helpdesk

### "Widget no carga"
- Ejecuta `php artisan config:clear`
- Verifica la URL en `HELPDESK_API_URL`

### "Error de CORS"
- El servidor Helpdesk debe permitir tu dominio
- Contacta al administrador de Helpdesk

### "X-Frame-Options error"
- El servidor Helpdesk necesita permitir iframes
- Esto se configura en el servidor, no en tu proyecto

---

## 📄 Licencia

MIT License - Ver [LICENSE](LICENSE)

---

## 🤝 Soporte

- 📧 Email: lukqs05@gmail.com
- 🐛 Issues: [GitHub Issues](https://github.com/Lukehowland/helpdeskwidget/issues)
