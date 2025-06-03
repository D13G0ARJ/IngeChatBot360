<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log; // Asegúrate de que esta línea esté presente

class GeminiApiService
{
    protected $client;
    protected $apiKey;
    protected $model = 'gemini-2.0-flash'; // O el modelo que estés usando

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->client = new Client([
            // La base_uri se mantiene, pero construiremos la URL completa en la llamada post
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/models/',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
        Log::info('GeminiApiService: Inicializado. API Key cargada: ' . (!empty($this->apiKey) ? 'Sí' : 'No'));
    }

    public function generateContent(array $chatHistory): string
    {
        Log::info('GeminiApiService: Iniciando generateContent.');

        if (empty($this->apiKey)) {
            Log::error('GEMINI_API_KEY no está configurada en el archivo .env');
            return 'Lo siento, la API de Gemini no está configurada correctamente.';
        }

        $payload = [
            'contents' => $chatHistory,
            // Puedes añadir generationConfig aquí si necesitas un esquema JSON específico
            // 'generationConfig' => [
            //     'responseMimeType' => 'application/json',
            //     'responseSchema' => [ ... ]
            // ]
        ];

        Log::info('GeminiApiService: Payload a enviar a Gemini: ' . json_encode($payload));

        try {
            // CORRECCIÓN CLAVE AQUÍ: Construir la URL completa directamente
            $fullUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
            Log::info('GeminiApiService: Realizando solicitud POST a la URL completa: ' . $fullUrl);

            // Pasar la URL completa a la función post
            $response = $this->client->post($fullUrl, [
                'json' => $payload,
            ]);

            $contents = $response->getBody()->getContents();
            Log::info('GeminiApiService: Respuesta cruda de Gemini: ' . $contents);
            $result = json_decode($contents, true);

            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $responseText = $result['candidates'][0]['content']['parts'][0]['text'];
                Log::info('GeminiApiService: Respuesta de texto de Gemini obtenida exitosamente.');
                return $responseText;
            } else {
                Log::error('GeminiApiService: Respuesta inesperada de Gemini API: ' . json_encode($result));
                // Añadir un log más detallado si hay un campo 'error' en la respuesta
                if (isset($result['error'])) {
                    Log::error('GeminiApiService: Error detallado de la API de Gemini: ' . json_encode($result['error']));
                }
                return 'Lo siento, no pude obtener una respuesta de la IA en este momento o la respuesta fue inesperada.';
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Manejo de excepciones de Guzzle HTTP (ej. errores de red, 4xx, 5xx)
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $errorMessage .= ' - Respuesta del servidor: ' . $e->getResponse()->getBody()->getContents();
            }
            Log::error('GeminiApiService: Error de solicitud Guzzle al llamar a Gemini API: ' . $errorMessage);
            return 'Hubo un problema de conexión con la IA. Por favor, inténtalo de nuevo más tarde.';
        } catch (\Exception $e) {
            // Manejo de otras excepciones generales
            Log::error('GeminiApiService: Error general al llamar a Gemini API: ' . $e->getMessage());
            return 'Hubo un problema inesperado al conectar con la IA. Por favor, inténtalo de nuevo más tarde.';
        }
    }
}
