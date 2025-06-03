<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ChatbotLogic
{
    protected $dataManager;
    protected $geminiApi;
    protected $chatHistory = []; // Para mantener el historial de la conversación
    protected $systemInstruction; // Añadimos una propiedad para la instrucción del sistema

    public function __construct(DataManager $dataManager, GeminiApiService $geminiApi)
    {
        $this->dataManager = $dataManager;
        $this->geminiApi = $geminiApi;
        // Definimos la instrucción del sistema aquí
        $this->systemInstruction = "Eres IngeChat 360°, un asistente virtual especializado en proporcionar información precisa y detallada sobre las carreras de Ingeniería (Sistemas, Mecánica, Telecomunicaciones y Eléctrica) de la UNEFA Núcleo Miranda, Sede Los Teques. Tu objetivo es asistir a estudiantes actuales y futuros con consultas académicas y profesionales relacionadas exclusivamente con estas carreras. Si la pregunta no está directamente relacionada con las carreras de ingeniería de la UNEFA, responde amablemente que tu función es específica y no puedes asistir con ese tema. Proporciona respuestas concisas pero informativas, y si es posible, sugiere dónde encontrar más detalles.";
        $this->startNewChatSession();
    }

    public function startNewChatSession()
    {
        // Al iniciar una nueva sesión, añadimos la instrucción del sistema al historial
        $this->chatHistory = [
            ['role' => 'user', 'parts' => [['text' => $this->systemInstruction]]]
        ];
        Log::info("Nueva sesión de chat iniciada con instrucción del sistema.");
    }

    public function processMessage(string $userMessage): string
    {
        $userMessageLower = strtolower($userMessage);
        $response = '';

        // Prioridad 1: Búsqueda en FAQs
        $faqAnswer = $this->dataManager->getFaqAnswer($userMessageLower);
        if ($faqAnswer) {
            $response = $faqAnswer;
        }
        // Prioridad 2: Búsqueda en información de carreras
        else if (str_contains($userMessageLower, 'pensum') && str_contains($userMessageLower, 'sistemas')) {
            $carrera = $this->dataManager->getCarreraInfo('sistemas');
            $response = $carrera ? "El pensum de Ingeniería de Sistemas es: " . json_encode($carrera['plan_estudios']) : "No encontré información del pensum de Sistemas.";
        }
        // ... (Añade más condiciones para otras carreras y sus detalles)
        else if (str_contains($userMessageLower, 'mision') || str_contains($userMessageLower, 'misión')) {
            $mision = $this->dataManager->getUnefaInfo('mision');
            $response = $mision ?: "No encontré la misión de la UNEFA.";
        }
        // ... (Añade más condiciones para unefa_info)
        // Prioridad 3: Si no hay respuesta local, usar Gemini
        else {
            // Añadimos el mensaje del usuario al historial para Gemini
            $this->chatHistory[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];
            $geminiResponse = $this->geminiApi->generateContent($this->chatHistory);
            $response = $geminiResponse;
        }

        // Actualiza el historial de chat con la respuesta del bot
        $this->chatHistory[] = ['role' => 'model', 'parts' => [['text' => $response]]];

        return $response;
    }

    public function getChatHistory(): array
    {
        return $this->chatHistory;
    }
}
