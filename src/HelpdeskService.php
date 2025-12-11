<?php

declare(strict_types=1);

namespace Lukehowland\HelpdeskWidget;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio principal para comunicarse con la API de Helpdesk.
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
     * @return array{success: bool, token?: string, error?: string}
     */
    public function getAuthToken(string $email): array
    {
        // Check cache first
        $cacheKey = 'helpdesk_token_' . md5($email);
        $cachedToken = Cache::get($cacheKey);
        
        if ($cachedToken) {
            return [
                'success' => true,
                'token' => $cachedToken,
                'from_cache' => true,
            ];
        }

        try {
            $response = $this->client->post('/api/external/login', [
                'json' => ['email' => $email],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);

            if (!empty($data['accessToken'])) {
                // Cache the token
                $ttl = config('helpdeskwidget.token_cache_ttl', 55);
                Cache::put($cacheKey, $data['accessToken'], now()->addMinutes($ttl));

                return [
                    'success' => true,
                    'token' => $data['accessToken'],
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
        $cacheKey = 'helpdesk_token_' . md5($email);
        Cache::forget($cacheKey);
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
