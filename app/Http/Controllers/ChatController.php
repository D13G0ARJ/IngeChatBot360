<?php

namespace App\Http\Controllers;

use App\Services\ChatbotLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session; // Importar el facade Session

class ChatController extends Controller
{
    protected $chatbotLogic;

    public function __construct(ChatbotLogic $chatbotLogic)
    {
        $this->chatbotLogic = $chatbotLogic;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = $request->input('message');

        Log::info('ChatController: Incoming message: ' . $userMessage);
        Log::info('ChatController: Session ID before loading: ' . Session::getId());
        Log::info('ChatController: Session data before loading: ' . json_encode(Session::all()));


        // Cargar el historial de chat y el estado de la conversación desde la sesión
        $this->chatbotLogic->setChatHistory(session('chat_history', []));
        $this->chatbotLogic->setConversationState(session('conversation_state', []));

        Log::info('ChatController: Chat history loaded from session. Count: ' . count($this->chatbotLogic->getChatHistory()));
        Log::info('ChatController: Conversation state loaded from session: ' . json_encode($this->chatbotLogic->getConversationState()));


        // processMessage ahora devuelve un array con 'response' y 'quick_replies'
        $botResponseData = $this->chatbotLogic->processMessage($userMessage);

        // Guardar el historial de chat y el estado de la conversación en la sesión
        session(['chat_history' => $this->chatbotLogic->getChatHistory()]);
        session(['conversation_state' => $this->chatbotLogic->getConversationState()]);

        Log::info('ChatController: Chat history saved to session. Count: ' . count(session('chat_history')));
        Log::info('ChatController: Conversation state saved to session: ' . json_encode(session('conversation_state')));
        Log::info('ChatController: Session ID after saving: ' . Session::getId());
        Log::info('ChatController: Session data after saving: ' . json_encode(Session::all()));


        return response()->json([
            'user_message' => $userMessage,
            'bot_response' => $botResponseData['response'], // Acceder al texto de la respuesta
            'quick_replies' => $botResponseData['quick_replies'] ?? [] // Acceder a los botones, por defecto un array vacío
        ]);
    }

    public function restartChat()
    {
        Log::info('ChatController: Restart chat requested.');
        Log::info('ChatController: Session ID before restart: ' . Session::getId());
        Log::info('ChatController: Session data before restart: ' . json_encode(Session::all()));

        $this->chatbotLogic->startNewChatSession();
        // Limpiar el historial de chat y el estado de la conversación de la sesión al reiniciar
        session()->forget('chat_history');
        session()->forget('conversation_state');

        Log::info('ChatController: Session ID after restart: ' . Session::getId());
        Log::info('ChatController: Session data after restart: ' . json_encode(Session::all()));

        // Al reiniciar, también enviamos un mensaje inicial con quick replies
        // Pasamos "hola" para que el ChatbotLogic genere el mensaje de bienvenida y los botones iniciales
        $initialResponseData = $this->chatbotLogic->processMessage("hola");

        return response()->json([
            'message' => 'Chat reiniciado.',
            'bot_response' => $initialResponseData['response'],
            'quick_replies' => $initialResponseData['quick_replies'] ?? []
        ]);
    }
}
