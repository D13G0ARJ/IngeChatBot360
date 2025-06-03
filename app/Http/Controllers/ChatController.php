<?php

namespace App\Http\Controllers;

use App\Services\ChatbotLogic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // Aquí podrías cargar el historial de chat desde la sesión si lo quieres persistente
        // $this->chatbotLogic->setChatHistory(session('chat_history', []));

        $botResponse = $this->chatbotLogic->processMessage($userMessage);

        // Guardar el historial de chat en la sesión (opcional, para persistencia entre requests)
        // session(['chat_history' => $this->chatbotLogic->getChatHistory()]);

        return response()->json([
            'user_message' => $userMessage,
            'bot_response' => $botResponse,
        ]);
    }

    public function restartChat()
    {
        $this->chatbotLogic->startNewChatSession();
        // session()->forget('chat_history'); // Si usas persistencia en sesión
        return response()->json(['message' => 'Chat reiniciado.']);
    }
}
