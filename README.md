# Helpdesk Widget para Laravel

Widget embebible para integrar el sistema de tickets de Helpdesk en proyectos Laravel externos.

## Requisitos

- PHP 8.2+
- Laravel 11.x o 12.x
- Guzzle HTTP Client

## Instalación

### 1. Instalar el paquete

```bash
composer require lukehowland/helpdeskwidget
```

### 2. Publicar la configuración (opcional)

```bash
php artisan vendor:publish --tag=helpdeskwidget-config
```

### 3. Configurar variables de entorno

Añade estas líneas a tu archivo `.env`:

```env
HELPDESK_API_URL=https://proyecto-de-ultimo-minuto.online
HELPDESK_API_KEY=tu-api-key-aqui
```

> **Nota**: El API Key es proporcionado por el administrador de Helpdesk cuando registra tu empresa.

## Uso Básico

### En una vista Blade

```blade
{{-- En cualquier vista donde el usuario esté autenticado --}}
<x-helpdesk-widget />
```

### Con parámetros personalizados

```blade
<x-helpdesk-widget 
    height="800px" 
    width="100%" 
    :border="true" 
/>
```

## Ejemplo de Integración

### En tu sidebar (AdminLTE)

```blade
{{-- resources/views/layouts/sidebar.blade.php --}}
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="sidebar">
        {{-- ... tu menú ... --}}
        
        {{-- Widget de Helpdesk --}}
        <div class="mt-3 px-3">
            <h6 class="text-muted text-uppercase font-weight-bold mb-2">
                <i class="fas fa-headset mr-2"></i> Centro de Soporte
            </h6>
            <x-helpdesk-widget height="400px" />
        </div>
    </div>
</aside>
```

### En una página dedicada

```blade
{{-- resources/views/soporte.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Centro de Soporte</h1>
            <x-helpdesk-widget height="calc(100vh - 200px)" />
        </div>
    </div>
</div>
@endsection
```

## Configuración Avanzada

### Archivo de configuración

Si publicaste la configuración, puedes modificar `config/helpdeskwidget.php`:

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

## Flujo de Autenticación

El widget maneja automáticamente la autenticación:

1. **Usuario detectado**: Lee `auth()->user()` para obtener email y nombre
2. **Verificación**: Consulta a Helpdesk si el usuario ya tiene cuenta
3. **Login automático**: Si existe, obtiene un token JWT y muestra los tickets
4. **Registro**: Si no existe, muestra un formulario para crear contraseña

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Tu Proyecto    │────▶│  Widget Package  │────▶│    Helpdesk     │
│  (auth user)    │     │  (API calls)     │     │   (API + View)  │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

## Personalización

### Atributos del usuario

El componente busca automáticamente estos atributos en tu modelo User:

```php
// Intenta en orden:
$user->first_name
$user->name        // Separa por espacios
$user->profile->first_name  // Si existe relación
```

Si tu modelo tiene atributos diferentes, puedes extender el componente:

```php
// app/View/Components/CustomHelpdeskWidget.php
class CustomHelpdeskWidget extends \Lukehowland\HelpdeskWidget\View\Components\HelpdeskWidget
{
    protected function getUserFirstName($user): string
    {
        return $user->primer_nombre; // Tu atributo personalizado
    }
}
```

## Solución de Problemas

### "API Key inválida"

- Verifica que `HELPDESK_API_KEY` esté configurado correctamente
- Confirma que tu empresa esté registrada en Helpdesk
- El API Key no debe tener espacios ni caracteres extra

### "Widget no carga"

- Revisa la consola del navegador para errores CORS
- Verifica que `HELPDESK_API_URL` sea accesible desde tu servidor
- Habilita `HELPDESK_DEBUG=true` para ver logs detallados

### "Usuario no autenticado"

- El widget requiere que `auth()->user()` retorne un usuario
- Asegúrate de usar el middleware `auth` en la ruta donde uses el widget

## Licencia

MIT License - Lucas De La Quintana Montenegro
