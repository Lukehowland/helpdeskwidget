<?php

declare(strict_types=1);

namespace Lukehowland\HelpdeskWidget\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Lukehowland\HelpdeskWidget\HelpdeskService;

/**
 * Componente Blade para renderizar el widget de Helpdesk.
 * 
 * Uso:
 *   <x-helpdesk-widget />
 *   <x-helpdesk-widget height="800px" />
 * 
 * Características:
 * - Detecta cambio de usuario automáticamente
 * - Maneja expiración de tokens
 * - Auto-refresh de tokens al 80% del TTL
 */
class HelpdeskWidget extends Component
{
    public string $iframeSrc;
    public string $height;
    public string $width;
    public bool $border;
    public bool $isReady;
    public ?string $error;

    public function __construct(
        ?string $height = null,
        ?string $width = null,
        ?bool $border = null
    ) {
        $this->height = $height ?? config('helpdeskwidget.iframe_height', '600px');
        $this->width = $width ?? config('helpdeskwidget.iframe_width', '100%');
        $this->border = $border ?? config('helpdeskwidget.iframe_border', false);
        $this->error = null;
        $this->isReady = false;

        $this->initialize();
    }

    /**
     * Inicializa el componente obteniendo los datos necesarios.
     */
    private function initialize(): void
    {
        // Verificar que hay un usuario autenticado
        $user = auth()->user();
        
        if (!$user) {
            $this->error = 'Debes iniciar sesión para usar el Centro de Soporte.';
            $this->iframeSrc = '';
            return;
        }

        // Verificar configuración
        $apiKey = config('helpdeskwidget.api_key');
        
        if (empty($apiKey)) {
            $this->error = 'El widget de Helpdesk no está configurado correctamente.';
            $this->iframeSrc = '';
            return;
        }

        /** @var HelpdeskService $service */
        $service = app(HelpdeskService::class);

        // Preparar datos del usuario
        $email = $user->email;
        $userData = [
            'email' => $email,
            'first_name' => $this->getUserFirstName($user),
            'last_name' => $this->getUserLastName($user),
        ];

        // ================================================================
        // DETECCIÓN DE CAMBIO DE USUARIO
        // Si el usuario actual es diferente al último, limpiar sesión
        // ================================================================
        if ($service->hasUserChanged($email)) {
            // El servicio ya invalidó el cache del usuario anterior
            // Continuamos con el flujo normal para el nuevo usuario
        }

        // Intentar obtener token (login automático)
        $tokenResult = $service->getAuthToken($email);
        
        if ($tokenResult['success'] && !empty($tokenResult['token'])) {
            // Usuario ya tiene cuenta, usar token
            $this->iframeSrc = $service->getWidgetUrl($userData, $tokenResult['token']);
        } else {
            // Usuario nuevo o error, enviar al flujo de autenticación
            $this->iframeSrc = $service->getWidgetUrl($userData);
        }

        $this->isReady = true;
    }

    /**
     * Obtiene el primer nombre del usuario.
     */
    private function getUserFirstName($user): string
    {
        // Intentar varios atributos comunes
        if (isset($user->first_name)) {
            return $user->first_name;
        }
        
        if (isset($user->name)) {
            $parts = explode(' ', $user->name);
            return $parts[0] ?? '';
        }

        if (method_exists($user, 'profile') && $user->profile) {
            return $user->profile->first_name ?? '';
        }

        return '';
    }

    /**
     * Obtiene el apellido del usuario.
     */
    private function getUserLastName($user): string
    {
        if (isset($user->last_name)) {
            return $user->last_name;
        }
        
        if (isset($user->name)) {
            $parts = explode(' ', $user->name);
            array_shift($parts);
            return implode(' ', $parts);
        }

        if (method_exists($user, 'profile') && $user->profile) {
            return $user->profile->last_name ?? '';
        }

        return '';
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('helpdeskwidget::components.helpdesk-widget');
    }
}
