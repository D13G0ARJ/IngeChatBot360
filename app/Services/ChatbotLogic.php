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
        // Definimos la instrucción del sistema aquí, haciéndola más estricta
        $this->systemInstruction = "Eres IngeChat 360°, un asistente virtual especializado en proporcionar información precisa y detallada sobre las carreras de Ingeniería (Sistemas, Mecánica, Telecomunicaciones y Eléctrica) de la UNEFA Núcleo Miranda, Sede Los Teques. Tu objetivo es asistir a estudiantes actuales y futuros con consultas académicas y profesionales relacionadas EXCLUSIVAMENTE con estas carreras y la información institucional de la UNEFA. Si la pregunta no está directamente relacionada con las carreras de ingeniería de la UNEFA o información institucional, responde amablemente que tu función es específica y no puedes asistir con ese tema. NO respondas a preguntas de conocimiento general ni a temas externos a la UNEFA. Proporciona respuestas concisas pero informativas, y si es posible, sugiere dónde encontrar más detalles dentro de tu ámbito de conocimiento.";
        $this->startNewChatSession();
    }

    public function startNewChatSession()
    {
        // Al iniciar una nueva sesión, añadimos la instrucción del sistema al historial
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
        $this->chatHistory = $history;
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
                $this->chatHistory[] = ['role' => 'model', 'parts' => [['text' => $response]]];
                return ['response' => $response, 'quick_replies' => $quickReplies]; // Return immediately
            } elseif ($isNocturno && isset($careerInfoAwaiting['regimenes']['Nocturno'])) {
                Log::info('ChatbotLogic: Matched Nocturno regime for awaited career. Formatting pensum.');
                $response = $this->formatPlanEstudios($careerInfoAwaiting['regimenes']['Nocturno']['plan_estudios'], $displayCareerNameAwaiting, 'Nocturno');
                $this->conversationState = []; // Clear state after successful match
                $this->chatHistory[] = ['role' => 'model', 'parts' => [['text' => $response]]];
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
                        $shortCurrentCareerName = str_replace(' ', '', $shortCurrentCareerName);

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
                    // Recursively call processMessage to re-evaluate the new query from the beginning
                    return $this->processMessage($userMessage);
                } else {
                    // If no career was mentioned, and it wasn't a regime, then re-prompt for the regime.
                    Log::info('ChatbotLogic: User input is unrelated to awaited regime or any career. Re-prompting for regime for the awaited career.');
                    $response = "Para la carrera de {$displayCareerNameAwaiting}, por favor, indica si deseas el pensum 'diurno' o 'nocturno'.";
                    $quickReplies = ["Diurno", "Nocturno"]; // Suggest buttons for the regime
                    // Keep the state, as we are still waiting for a regime for this career
                    $this->chatHistory[] = ['role' => 'model', 'parts' => [['text' => $response]]];
                    return ['response' => $response, 'quick_replies' => $quickReplies]; // Return immediately
                }
            }
        }
        // --- Fin del manejo de estado de conversación ---

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
        // Prioridad 2: Búsqueda en información de carreras
        else {
            $foundCareerKey = null;
            $displayCareerName = null;

            foreach ($this->dataManager->getAllCarrerasNames() as $careerKey) {
                $careerInfo = $this->dataManager->getCarreraInfo($careerKey);
                if ($careerInfo) {
                    $currentDisplayCareerName = $careerInfo['carrera'];
                    $lowerDisplayCareerName = strtolower($currentDisplayCareerName);
                    $shortCareerName = str_replace(['ingeniería de ', 'ingeniería en '], '', $lowerDisplayCareerName);
                    $shortCareerName = str_replace(' ', '', $shortCareerName);

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
            else {
                // Palabras clave para determinar si la pregunta es del contexto UNEFA/carreras
                $contextKeywords = [
                    'unefa', 'ingenier', 'carrera', 'sede', 'núcleo', 'nucleo', 'inscrip', 'inscripción', 'preinscripción', 'preinscrip',
                    'egresado', 'pensum', 'plan de estudio', 'plan curricular', 'perfil', 'salidas profesionales', 'campo laboral',
                    'mision', 'misión', 'vision', 'visión', 'ubicacion', 'ubicación', 'dirección', 'direccion', 'contacto', 'teléfono',
                    'telefono', 'correo', 'email', 'correo institucional', 'los teques', 'miranda', 'universidad', 'estudios', 'materias',
                    'asignaturas', 'semestre', 'trimestre', 'trayecto', 'docente', 'profesor', 'clases', 'horario', 'turno', 'diurno',
                    'nocturno', 'modalidad', 'presencial', 'virtual', 'beca', 'becas', 'arancel', 'aranceles', 'pago', 'pagos',
                    'inscripción en línea', 'registro', 'reingreso', 'traslado', 'equivalencia', 'título', 'titulo', 'graduación',
                    'graduacion', 'nota', 'notas', 'calificación', 'calificacion', 'evaluación', 'evaluacion', 'requisito', 'requisitos',
                    'documento', 'documentos', 'proceso', 'admisión', 'admision', 'postulación', 'postulacion', 'cupos', 'cupos disponibles',
                    'oferta académica', 'oferta academica', 'servicio comunitario', 'pasantía', 'pasantias', 'laboratorio', 'laboratorios',
                    'biblioteca', 'investigación', 'investigacion', 'consejo', 'coordinación', 'coordinacion', 'secretaría', 'secretaria',
                    'estudiante', 'alumno', 'egreso', 'ingreso', 'nuevo ingreso', 'reingreso', 'sistema', 'mecánica', 'mecanica',
                    'telecomunicaciones', 'eléctrica', 'electrica', 'sistemas', 'mecatronica', 'civil', 'industrial', 'computación',
                    'computacion', 'tecnología', 'tecnologia', 'ingeniería', 'ingenieria', 'universitario', 'universitaria', 'postgrado',
                    'maestría', 'maestria', 'doctorado', 'pregrado', 'pre-universitario', 'preuniversitario', 'becario', 'becarios',
                    'asistente académico', 'asistente academico', 'departamento', 'decanato', 'rectorado', 'autoridad', 'autoridades',
                    'reglamento', 'normativa', 'calendario académico', 'calendario academico', 'evento', 'eventos', 'actividad', 'actividades','ecuacion','resolver','ejercicio'
                ];
                $isContextual = false;
                foreach ($contextKeywords as $keyword) {
                    if (str_contains($userMessageLower, $keyword)) {
                        $isContextual = true;
                        break;
                    }
                }
                if ($isContextual) {
                    Log::info('ChatbotLogic: No local match found, but message is contextual. Consulting Gemini API.');
                    $this->chatHistory[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];
                    $geminiResponse = $this->geminiApi->generateContent($this->chatHistory);
                    $response = $geminiResponse;
                } else {
                    Log::info('ChatbotLogic: No local match found and message is out of scope. Responding with out-of-scope message.');
                    $response = "Lo siento, solo puedo responder preguntas relacionadas con las carreras de Ingeniería de la UNEFA Núcleo Miranda, Sede Los Teques, o información institucional de la UNEFA.";
                }
                // No hay quick replies por defecto si la respuesta es fuera de contexto o viene de Gemini
            }
        }

        // Actualiza el historial de chat con la respuesta del bot
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
