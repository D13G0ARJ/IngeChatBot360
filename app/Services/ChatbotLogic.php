<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ChatbotLogic
{
    protected $dataManager;
    protected $geminiApi;
    protected $chatHistory = []; // Para mantener el historial de la conversación
    protected $systemInstruction; // Añadimos una propiedad para la instrucción del sistema

    // Propiedad para almacenar el estado de la conversación (ej. esperando régimen)
    protected $conversationState = [];

    public function __construct(DataManager $dataManager, GeminiApiService $geminiApi)
    {
        $this->dataManager = $dataManager;
        $this->geminiApi = $geminiApi;
        // Definimos la instrucción del sistema aquí, haciéndola MÁS ESTRICTA
        $this->systemInstruction = "Eres IngeChat 360°, un asistente virtual diseñado para ofrecer información exhaustiva y precisa sobre las carreras de Ingeniería (Sistemas, Mecánica, Telecomunicaciones y Eléctrica) de la UNEFA Núcleo Miranda, Sede Los Teques. Tu misión es responder preguntas académicas y profesionales relacionadas con el contenido, temáticas y aplicaciones de estas cuatro ingenierías, incluyendo pero no limitándose a: planes de estudio, salidas profesionales, conceptos fundamentales, resolución de problemas típicos (ej. ecuaciones, factorización, análisis de circuitos), herramientas comunes, y cualquier otra consulta que surja directamente del estudio o ejercicio de estas disciplinas. También puedes proporcionar información institucional pertinente de la UNEFA relacionada con estas carreras. Si una pregunta no está directamente vinculada con el ámbito académico o profesional de las ingenierías especificadas de la UNEFA Los Teques, o con información institucional relevante, debes responder de manera cortés que tu función es especializada y no puedes asistir con ese tema. Bajo ninguna circunstancia respondas a preguntas de conocimiento general, temas personales, o asuntos ajenos a la UNEFA y sus carreras de ingeniería. Proporciona respuestas claras, concisas y orientadas al detalle, y si es apropiado, sugiere dónde profundizar en el tema dentro de tu área de experticia.";
        $this->startNewChatSession();
    }

    public function startNewChatSession()
    {
        // Al iniciar una nueva sesión, añadimos la instrucción del sistema al historial
        // El historial siempre comenzará con la instrucción del sistema.
        $this->chatHistory = [
            ['role' => 'user', 'parts' => [['text' => $this->systemInstruction]]]
        ];
        $this->conversationState = []; // Reiniciar estado de conversación
        Log::info("ChatbotLogic: Nueva sesión de chat iniciada con instrucción del sistema.");
    }

    /**
     * Establece el historial de chat. Útil para cargar desde la sesión.
     * @param array $history
     */
    public function setChatHistory(array $history)
    {
        // Asegurarse de que la instrucción del sistema sea el primer elemento si no está.
        // Esto previene que se duplique si ya está en la sesión, pero la añade si la sesión está vacía.
        if (empty($history) || !isset($history[0]['parts'][0]['text']) || $history[0]['parts'][0]['text'] !== $this->systemInstruction) {
             $this->chatHistory = [['role' => 'user', 'parts' => [['text' => $this->systemInstruction]]]];
             if (!empty($history)) {
                 // Añadir el resto del historial si no está vacío y no es solo la instrucción.
                 // Filtrar la instrucción del sistema si ya existe en el historial cargado para evitar duplicados.
                 foreach ($history as $item) {
                     if (!($item['role'] === 'user' && isset($item['parts'][0]['text']) && $item['parts'][0]['text'] === $this->systemInstruction)) {
                         $this->chatHistory[] = $item;
                     }
                 }
             }
        } else {
            $this->chatHistory = $history;
        }

        Log::info('ChatbotLogic: Chat history loaded. Count: ' . count($this->chatHistory));
    }

    /**
     * Obtiene el historial de chat actual. Útil para guardar en la sesión.
     * @return array
     */
    public function getChatHistory(): array
    {
        return $this->chatHistory;
    }

    /**
     * Establece el estado de la conversación. Útil para cargar desde la sesión.
     * @param array $state
     */
    public function setConversationState(array $state)
    {
        $this->conversationState = $state;
        Log::info('ChatbotLogic: Conversation state loaded: ' . json_encode($this->conversationState));
    }

    /**
     * Obtiene el estado actual de la conversación. Útil para guardar en la sesión.
     * @return array
     */
    public function getConversationState(): array
    {
        return $this->conversationState;
    }

    /**
     * Procesa el mensaje del usuario y devuelve una respuesta estructurada.
     *
     * @param string $userMessage El mensaje del usuario.
     * @return array Un array con 'response' (texto del bot) y 'quick_replies' (sugerencias de botones).
     */
    public function processMessage(string $userMessage): array
    {
        $userMessageLower = strtolower(trim($userMessage));
        $response = '';
        $quickReplies = []; // Inicializar array de botones

        Log::info('ChatbotLogic: Processing message: "' . $userMessage . '" (Lower: "' . $userMessageLower . '")');
        Log::info('ChatbotLogic: Current conversation state at start of processMessage: ' . json_encode($this->conversationState));

        // --- Manejo de estado de conversación (si se está esperando un régimen) ---
        if (isset($this->conversationState['awaiting_regime_for_career'])) {
            Log::info('ChatbotLogic: Entering awaiting regime state handling.');
            $careerKeyAwaiting = $this->conversationState['awaiting_regime_for_career']; // Clave de la carrera que estamos esperando
            $careerInfoAwaiting = $this->dataManager->getCarreraInfo($careerKeyAwaiting);
            $displayCareerNameAwaiting = $careerInfoAwaiting['carrera'] ?? ucfirst(str_replace('_', ' ', $careerKeyAwaiting));

            // Check if user is specifying regime for the awaited career
            $isDiurno = str_contains($userMessageLower, 'diurno');
            $isNocturno = str_contains($userMessageLower, 'nocturno');

            if ($isDiurno && isset($careerInfoAwaiting['regimenes']['Diurno'])) {
                Log::info('ChatbotLogic: Matched Diurno regime for awaited career. Formatting pensum.');
                $response = $this->formatPlanEstudios($careerInfoAwaiting['regimenes']['Diurno']['plan_estudios'], $displayCareerNameAwaiting, 'Diurno');
                $this->conversationState = []; // Clear state after successful match
                // Ya añadimos el mensaje del usuario más abajo, no lo duplicamos aquí.
                // La respuesta del bot se añade al final de processMessage.
                return ['response' => $response, 'quick_replies' => $quickReplies]; // Return immediately
            } elseif ($isNocturno && isset($careerInfoAwaiting['regimenes']['Nocturno'])) {
                Log::info('ChatbotLogic: Matched Nocturno regime for awaited career. Formatting pensum.');
                $response = $this->formatPlanEstudios($careerInfoAwaiting['regimenes']['Nocturno']['plan_estudios'], $displayCareerNameAwaiting, 'Nocturno');
                $this->conversationState = []; // Clear state after successful match
                // Ya añadimos el mensaje del usuario más abajo, no lo duplicamos aquí.
                // La respuesta del bot se añade al final de processMessage.
                return ['response' => $response, 'quick_replies' => $quickReplies]; // Return immediately
            } else {
                // User did not specify a valid regime for the awaited career.
                // Now, check if the user is asking about ANY career (including the one we were awaiting)
                $mentionedCareerKey = null;
                foreach ($this->dataManager->getAllCarrerasNames() as $currentCareerKey) {
                    $currentCareerInfo = $this->dataManager->getCarreraInfo($currentCareerKey);
                    if ($currentCareerInfo) {
                        $lowerDisplayCurrentCareerName = strtolower($currentCareerInfo['carrera']);
                        $shortCurrentCareerName = str_replace(['ingeniería de ', 'ingeniería en '], '', $lowerDisplayCurrentCareerName);
                        $shortCurrentCareerName = str_replace(' ', '', $lowerDisplayCurrentCareerName);

                        if (str_contains($userMessageLower, $lowerDisplayCurrentCareerName) || str_contains($userMessageLower, $shortCurrentCareerName)) {
                            $mentionedCareerKey = $currentCareerKey;
                            break;
                        }
                    }
                }

                if ($mentionedCareerKey) {
                    // If a career is mentioned, clear the state and re-process the message from scratch.
                    // This handles cases where the user changes their mind or re-asks the pensum question.
                    Log::info('ChatbotLogic: User mentioned a career (' . $mentionedCareerKey . ') while awaiting regime. Clearing state and re-processing message.');
                    $this->conversationState = []; // Clear the state
                    // NOTA IMPORTANTE: Para evitar duplicar el mensaje del usuario en el historial
                    // al re-procesar, no lo añadimos aquí. El sendMessage del controlador
                    // lo añadirá una vez al principio.
                    return $this->processMessage($userMessage);
                } else {
                    // If no career was mentioned, and it wasn't a regime, then re-prompt for the regime.
                    Log::info('ChatbotLogic: User input is unrelated to awaited regime or any career. Re-prompting for regime for the awaited career.');
                    $response = "Para la carrera de {$displayCareerNameAwaiting}, por favor, indica si deseas el pensum 'diurno' o 'nocturno'.";
                    $quickReplies = ["Diurno", "Nocturno"]; // Suggest buttons for the regime
                    // Keep the state, as we are still waiting for a regime for this career
                    // La respuesta del bot se añade al final de processMessage.
                    return ['response' => $response, 'quick_replies' => $quickReplies]; // Return immediately
                }
            }
        }
        // --- Fin del manejo de estado de conversación ---

        // Añadimos el mensaje del usuario al historial para cualquier procesamiento posterior.
        // Esto es crucial para que Gemini tenga el contexto completo.
        $this->chatHistory[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];


        // Prioridad 1: Búsqueda en FAQs
        $faqAnswer = $this->dataManager->getFaqAnswer($userMessageLower);
        if ($faqAnswer) {
            Log::info('ChatbotLogic: Responding with FAQ answer.');
            $response = $faqAnswer;
            // Sugerir botones de carreras al final de una FAQ si es una pregunta general
            if (str_contains($userMessageLower, 'requisitos de inscripción') || str_contains($userMessageLower, 'ubicación') || str_contains($userMessageLower, 'horario')) {
                 $quickReplies = ["Ingeniería de Sistemas", "Ingeniería Mecánica", "Ingeniería Eléctrica", "Ingeniería de Telecomunicaciones", "Requisitos de Inscripción"];
            }
        }
        // Prioridad 2: Búsqueda en información de carreras y UNEFA
        else {
            $foundCareerKey = null;
            $displayCareerName = null;

            foreach ($this->dataManager->getAllCarrerasNames() as $careerKey) {
                $careerInfo = $this->dataManager->getCarreraInfo($careerKey);
                if ($careerInfo) {
                    $currentDisplayCareerName = $careerInfo['carrera'];
                    $lowerDisplayCareerName = strtolower($currentDisplayCareerName);
                    $shortCareerName = str_replace(['ingeniería de ', 'ingeniería en '], '', $lowerDisplayCareerName);
                    $shortCareerName = str_replace(' ', '', $lowerDisplayCareerName);

                    if (str_contains($userMessageLower, $lowerDisplayCareerName) || str_contains($userMessageLower, $shortCareerName)) {
                        $foundCareerKey = $careerKey;
                        $displayCareerName = $currentDisplayCareerName;
                        Log::info('ChatbotLogic: Matched career: ' . $displayCareerName . ' (Key: ' . $foundCareerKey . ')');
                        break;
                    }
                }
            }

            if ($foundCareerKey && $displayCareerName) {
                $careerInfo = $this->dataManager->getCarreraInfo($foundCareerKey);
                if ($careerInfo) {
                    if (str_contains($userMessageLower, 'pensum') || str_contains($userMessageLower, 'plan de estudio')) {
                        Log::info('ChatbotLogic: User asked for pensum/plan de estudio.');
                        $regimes = array_keys($careerInfo['regimenes'] ?? []);

                        $isDiurno = str_contains($userMessageLower, 'diurno');
                        $isNocturno = str_contains($userMessageLower, 'nocturno');

                        if ($isDiurno && isset($careerInfo['regimenes']['Diurno'])) {
                            Log::info('ChatbotLogic: Pensum request with Diurno specified.');
                            $response = $this->formatPlanEstudios($careerInfo['regimenes']['Diurno']['plan_estudios'], $displayCareerName, 'Diurno');
                        } elseif ($isNocturno && isset($careerInfo['regimenes']['Nocturno'])) {
                            Log::info('ChatbotLogic: Pensum request with Nocturno specified.');
                            $response = $this->formatPlanEstudios($careerInfo['regimenes']['Nocturno']['plan_estudios'], $displayCareerName, 'Nocturno');
                        } elseif (count($regimes) > 1 && (!$isDiurno && !$isNocturno)) {
                            Log::info('ChatbotLogic: Pensum request, multiple regimes, no specific regime given. Asking for clarification.');
                            $response = "La carrera de {$displayCareerName} tiene planes de estudio para los regímenes " . implode(' y ', $regimes) . ". ¿Cuál te gustaría consultar?";
                            $this->conversationState['awaiting_regime_for_career'] = $foundCareerKey;
                            $quickReplies = ["Diurno", "Nocturno"]; // Sugerir botones para el turno
                        } else if (count($regimes) == 1) {
                            Log::info('ChatbotLogic: Pensum request, single regime available. Using default.');
                            $defaultRegime = $regimes[0];
                            $response = $this->formatPlanEstudios($careerInfo['regimenes'][$defaultRegime]['plan_estudios'], $displayCareerName, $defaultRegime);
                        } else {
                            Log::info('ChatbotLogic: Pensum information not found or invalid regime for ' . $displayCareerName);
                            $response = "No encontré información del plan de estudios para {$displayCareerName} o no se especificó un régimen válido.";
                        }
                    }
                    else if (str_contains($userMessageLower, 'perfil') || str_contains($userMessageLower, 'egresado')) {
                        Log::info('ChatbotLogic: User asked for profile.');
                        $response = $careerInfo['perfil_egresado_comun'] ?? "Perfil del egresado para {$displayCareerName} no disponible.";
                        $quickReplies = ["Pensum de {$displayCareerName}", "Salidas Profesionales de {$displayCareerName}", "Duración de {$displayCareerName}"];
                    }
                    else if (str_contains($userMessageLower, 'salidas profesionales') || str_contains($userMessageLower, 'campo laboral')) {
                        Log::info('ChatbotLogic: User asked for career paths.');
                        $salidas = implode(', ', $careerInfo['salidas_profesionales'] ?? []);
                        if ($salidas) {
                            $response = "Algunas salidas profesionales para {$displayCareerName} incluyen: {$salidas}.";
                        } else {
                            $response = "Salidas profesionales para {$displayCareerName} no disponibles.";
                        }
                        $quickReplies = ["Pensum de {$displayCareerName}", "Perfil del Egresado de {$displayCareerName}", "Duración de {$displayCareerName}"];
                    }
                    else if (str_contains($userMessageLower, 'descripcion') || str_contains($userMessageLower, 'que es')) {
                        Log::info('ChatbotLogic: User asked for description.');
                        $response = $careerInfo['descripcion_carrera'] ?? "Descripción para {$displayCareerName} no disponible.";
                        $quickReplies = ["Pensum de {$displayCareerName}", "Perfil del Egresado de {$displayCareerName}", "Salidas Profesionales de {$displayCareerName}", "Duración de {$displayCareerName}"];
                    }
                    else if (str_contains($userMessageLower, 'duracion')) {
                        Log::info('ChatbotLogic: User asked for duration.');
                        $durations = [];
                        foreach ($careerInfo['regimenes'] ?? [] as $regimeName => $regimeData) {
                            if (isset($regimeData['duracion'])) {
                                $durations[] = "{$regimeName}: {$regimeData['duracion']}";
                            }
                        }

                        if (!empty($durations)) {
                            if (count($durations) === 1) {
                                $response = "La duración de la carrera de {$displayCareerName} es: {$durations[0]}.";
                            } else {
                                $response = "La duración de la carrera de {$displayCareerName} es la siguiente: " . implode('; ', $durations) . ".";
                            }
                        } else {
                            $response = "Duración para {$displayCareerName} no disponible.";
                        }
                        $quickReplies = ["Pensum de {$displayCareerName}", "Perfil del Egresado de {$displayCareerName}", "Salidas Profesionales de {$displayCareerName}"];
                    }
                    else {
                        Log::info('ChatbotLogic: General career info response.');
                        $response = (
                            "{$displayCareerName}: " .
                            ($careerInfo['descripcion_carrera'] ?? 'Descripción no disponible.') . " " .
                            "Puedes preguntar sobre su perfil de egresado, plan de estudios o salidas profesionales."
                        );
                        $quickReplies = ["Pensum de {$displayCareerName}", "Perfil del Egresado de {$displayCareerName}", "Salidas Profesionales de {$displayCareerName}", "Duración de {$displayCareerName}"];
                    }
                }
            }
            else if (str_contains($userMessageLower, 'mision') || str_contains($userMessageLower, 'misión')) {
                Log::info('ChatbotLogic: User asked for mission.');
                $mision = $this->dataManager->getUnefaInfo('mision');
                $response = $mision ?: "No encontré la misión de la UNEFA.";
                $quickReplies = ["Visión de la UNEFA", "¿Qué carreras ofrecen?", "Ubicación de la UNEFA"];
            }
            else if (str_contains($userMessageLower, 'vision') || str_contains($userMessageLower, 'visión')) {
                Log::info('ChatbotLogic: User asked for vision.');
                $vision = $this->dataManager->getUnefaInfo('vision');
                $response = $vision ?: "No encontré la visión de la UNEFA.";
                $quickReplies = ["Misión de la UNEFA", "¿Qué carreras ofrecen?", "Ubicación de la UNEFA"];
            }
            else if (str_contains($userMessageLower, 'ubicacion') || str_contains($userMessageLower, 'dirección') || str_contains($userMessageLower, 'donde esta')) {
                Log::info('ChatbotLogic: User asked for location.');
                $ubicacion = $this->dataManager->getUnefaInfo('ubicacion');
                $contactoInfo = $this->dataManager->getUnefaInfo('contacto');
                $direccion_fisica = $contactoInfo['direccion_fisica'] ?? null;
                $response = $ubicacion ? "La UNEFA Núcleo Miranda, Sede Los Teques, está ubicada en {$ubicacion}. Dirección física: {$direccion_fisica}." : "No encontré la ubicación de la UNEFA.";
                $quickReplies = ["Requisitos de Inscripción", "Ingeniería de Sistemas", "Misión de la UNEFA"];
            }
            else if (str_contains($userMessageLower, 'contacto') || str_contains($userMessageLower, 'teléfono') || str_contains($userMessageLower, 'correo')) {
                Log::info('ChatbotLogic: User asked for contact info.');
                $contacto = $this->dataManager->getUnefaInfo('contacto');
                $response = $contacto ? "Puedes contactar a la UNEFA Núcleo Miranda, Sede Los Teques. Dirección física: {$contacto['direccion_fisica']}." : "No encontré información de contacto de la UNEFA.";
                $quickReplies = ["Ubicación de la UNEFA", "Requisitos de Inscripción"];
            }
            else if (str_contains($userMessageLower, 'hola') || str_contains($userMessageLower, 'buenas') || str_contains($userMessageLower, 'saludos')) {
                Log::info('ChatbotLogic: Initial greeting.');
                $response = "¡Hola! Soy IngeChat 360°, tu asistente virtual de la UNEFA Núcleo Miranda, Sede Los Teques. Estoy aquí para brindarte información detallada sobre las carreras de Ingeniería: Sistemas, Mecánica, Telecomunicaciones y Eléctrica.\n\n¿En qué carrera estás interesado hoy? O puedes preguntar sobre requisitos de inscripción, perfil del egresado, etc.";
                $quickReplies = ["Ingeniería de Sistemas", "Ingeniería Mecánica", "Ingeniería Eléctrica", "Ingeniería de Telecomunicaciones", "Requisitos de Inscripción"];
            }
            // Si no se encuentra ninguna coincidencia local, se consulta a Gemini.
            // La systemInstruction de Gemini es la encargada de mantener el ámbito.
            else {
                Log::info('ChatbotLogic: No se encontró coincidencia local. Consultando a Gemini API con instrucción de sistema estricta.');
                // En este punto, $this->chatHistory ya contiene el mensaje del usuario actual
                // y la systemInstruction al inicio.
                $geminiResponse = $this->geminiApi->generateContent($this->chatHistory);
                $response = $geminiResponse;
                // No hay quick replies por defecto si la respuesta viene de Gemini y no es contextual
            }
        }

        // Actualiza el historial de chat con la respuesta del bot
        // El mensaje del bot se añade al final para mantener el orden cronológico.
        $this->chatHistory[] = ['role' => 'model', 'parts' => [['text' => $response]]];
        Log::info('ChatbotLogic: Final response generated: ' . $response);

        return ['response' => $response, 'quick_replies' => $quickReplies];
    }

    /**
     * Formatea el plan de estudios de una carrera por semestres.
     * @param array $planEstudios El array del plan de estudios.
     * @param string $careerName El nombre de la carrera para mostrar.
     * @param string $regimeName El nombre del régimen (Diurno/Nocturno).
     * @return string El plan de estudios formateado.
     */
    private function formatPlanEstudios(array $planEstudios, string $careerName, string $regimeName): string
    {
        $formattedPlan = "El plan de estudios de {$careerName} ({$regimeName}) es:\n";
        foreach ($planEstudios as $semester => $courses) {
            $formattedPlan .= "{$semester}: ";
            $courseNames = [];
            // Asegurarse de que $courses es iterable y contiene la estructura esperada
            if (is_array($courses)) {
                foreach ($courses as $course) {
                    // Verificar si la clave 'asignatura' existe y no es nula
                    if (is_array($course) && isset($course['asignatura']) && $course['asignatura'] !== null) {
                        $courseNames[] = $course['asignatura'];
                    } else if (is_string($course)) { // Fallback para entradas de cadena simples si las hay
                        $courseNames[] = $course;
                    }
                }
            }
            $formattedPlan .= implode(', ', $courseNames) . ".\n";
        }
        return $formattedPlan;
    }
}
