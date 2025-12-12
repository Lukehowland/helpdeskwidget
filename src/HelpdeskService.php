<?php

declare(strict_types=1);

namespace Lukehowland\HelpdeskWidget;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Servicio principal para comunicarse con la API de Helpdesk.
 * 
 * Maneja:
 * - Validación de API Key
 * - Autenticación de usuarios
 * - Cache de tokens con expiración correcta
 * - Refresh de tokens
 * - Detección de cambio de usuario
 */
class HelpdeskService
{
    private Client $client;
    private string $apiUrl;
    private string $apiKey;

    public function __construct(string $apiUrl, string $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Service-Key' => $this->apiKey,
            ],
        ]);
    }

    /**
     * Valida que la API Key sea válida.
     * 
     * @return array{success: bool, company?: array, error?: string}
     */
    public function validateApiKey(): array
    {
        try {
            $response = $this->client->post('/api/external/validate-key');
            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'company' => $data['company'] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logError('validateApiKey', $e);
            
            return [
                'success' => false,
                'error' => 'API Key inválida o servicio no disponible',
            ];
        }
    }

    /**
     * Verifica si un usuario existe en Helpdesk.
     * 
     * @param string $email
     * @return array{success: bool, exists: bool, user?: array}
     */
    public function checkUserExists(string $email): array
    {
        try {
            $response = $this->client->post('/api/external/check-user', [
                'json' => ['email' => $email],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'exists' => $data['exists'] ?? false,
                'user' => $data['user'] ?? null,
            ];
        } catch (GuzzleException $e) {
            $this->logError('checkUserExists', $e);
            
            return [
                'success' => false,
                'exists' => false,
                'error' => 'Error verificando usuario',
            ];
        }
    }

    /**
     * Obtiene un token JWT para el usuario (login automático).
     * 
     * @param string $email
     * @return array{success: bool, token?: string, expiresAt?: int, error?: string}
     */
    public function getAuthToken(string $email): array
    {
        // Check cache first
        $cacheKey = $this->getTokenCacheKey($email);
        $cachedData = Cache::get($cacheKey);
        
        if ($cachedData && isset($cachedData['token'], $cachedData['expires_at'])) {
            // Verificar si el token sigue válido (con margen del 20%)
            $now = time();
            $expiresAt = $cachedData['expires_at'];
            $ttlTotal = $expiresAt - $cachedData['created_at'];
            $threshold = $cachedData['created_at'] + ($ttlTotal * 0.8); // 80% del tiempo
            
            if ($now < $threshold) {
                return [
                    'success' => true,
                    'token' => $cachedData['token'],
                    'expiresAt' => $expiresAt,
                    'from_cache' => true,
                ];
            }
            
            // Token está por expirar, intentar refresh
            $refreshResult = $this->refreshToken($cachedData['token']);
            if ($refreshResult['success']) {
                return $refreshResult;
            }
            
            // Si refresh falla, obtener token nuevo
            $this->invalidateTokenCache($email);
        }

        try {
            $response = $this->client->post('/api/external/login', [
                'json' => ['email' => $email],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            if (!empty($data['accessToken'])) {
                $now = time();
                $expiresIn = $data['expiresIn'] ?? (15 * 60); // Default 15 minutos en segundos
                $expiresAt = $now + $expiresIn;
                
                // Cache con la expiración REAL del token
                Cache::put($cacheKey, [
                    'token' => $data['accessToken'],
                    'expires_at' => $expiresAt,
                    'created_at' => $now,
                ], $expiresIn);

                return [
                    'success' => true,
                    'token' => $data['accessToken'],
                    'expiresAt' => $expiresAt,
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Error obteniendo token',
            ];
        } catch (GuzzleException $e) {
            $this->logError('getAuthToken', $e);
            
            return [
                'success' => false,
                'error' => 'Error de autenticación',
            ];
        }
    }

    /**
     * Refresca un token existente.
     * 
     * @param string $currentToken
     * @return array{success: bool, token?: string, expiresAt?: int, error?: string}
     */
    public function refreshToken(string $currentToken): array
    {
        try {
            $response = $this->client->post('/api/external/refresh', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $currentToken,
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            if (!empty($data['accessToken'])) {
                $now = time();
                $expiresIn = $data['expiresIn'] ?? (15 * 60);
                $expiresAt = $now + $expiresIn;

                // Actualizar cache con el email del token (decodificar JWT)
                $email = $this->getEmailFromToken($currentToken);
                if ($email) {
                    $cacheKey = $this->getTokenCacheKey($email);
                    Cache::put($cacheKey, [
                        'token' => $data['accessToken'],
                        'expires_at' => $expiresAt,
                        'created_at' => $now,
                    ], $expiresIn);
                }

                return [
                    'success' => true,
                    'token' => $data['accessToken'],
                    'expiresAt' => $expiresAt,
                ];
            }

            return [
                'success' => false,
                'error' => 'Error refrescando token',
            ];
        } catch (GuzzleException $e) {
            $this->logError('refreshToken', $e);
            
            return [
                'success' => false,
                'error' => 'Error de refresh',
            ];
        }
    }

    /**
     * Genera la URL del widget con parámetros.
     * 
     * @param array $userData {email, first_name, last_name}
     * @param string|null $token Token JWT si ya se obtuvo
     * @return string
     */
    public function getWidgetUrl(array $userData, ?string $token = null): string
    {
        $baseUrl = $this->apiUrl . '/widget';
        
        $params = [
            'api_key' => $this->apiKey,
            'email' => $userData['email'] ?? '',
            'first_name' => $userData['first_name'] ?? '',
            'last_name' => $userData['last_name'] ?? '',
        ];
        
        if ($token) {
            $baseUrl .= '/tickets';
            $params = ['token' => $token];
        }
        
        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Invalida el cache del token para un usuario.
     * 
     * @param string $email
     * @return void
     */
    public function invalidateTokenCache(string $email): void
    {
        $cacheKey = $this->getTokenCacheKey($email);
        Cache::forget($cacheKey);
        
        // También limpiar la sesión
        Session::forget('helpdesk_widget_email');
    }

    /**
     * Verifica si el usuario actual es diferente al último autenticado.
     * Útil para detectar cambio de usuario y limpiar sesión anterior.
     * 
     * @param string $currentEmail
     * @return bool True si el usuario cambió
     */
    public function hasUserChanged(string $currentEmail): bool
    {
        $lastEmail = Session::get('helpdesk_widget_email');
        
        if ($lastEmail && $lastEmail !== $currentEmail) {
            // Usuario cambió - invalidar token anterior
            $this->invalidateTokenCache($lastEmail);
            return true;
        }
        
        // Guardar email actual
        Session::put('helpdesk_widget_email', $currentEmail);
        
        return false;
    }

    /**
     * Extrae el email del payload de un JWT.
     * 
     * @param string $token
     * @return string|null
     */
    private function getEmailFromToken(string $token): ?string
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            $payload = json_decode(base64_decode($parts[1]), true);
            return $payload['email'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Genera la key de cache para un token.
     */
    private function getTokenCacheKey(string $email): string
    {
        return 'helpdesk_token_' . md5($email);
    }

    /**
     * Log de errores.
     */
    private function logError(string $method, GuzzleException $e): void
    {
        if (config('helpdeskwidget.debug', false)) {
            Log::error("[HelpdeskWidget::{$method}] Error", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }
    }
}
