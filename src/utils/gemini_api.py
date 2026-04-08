# src/utils/gemini_api.py
import google.generativeai as genai
from src.utils.config import GEMINI_API_KEY
import logging
import os

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class GeminiAPI:
    def __init__(self, api_key: str):
        if not api_key:
            raise ValueError("GEMINI_API_KEY no está configurada. Asegúrate de tenerla en tu archivo .env")
        genai.configure(api_key=api_key)
        # Modelo por defecto (compatible con generateContent según list_models.py)
        # Se puede sobrescribir con la variable de entorno GEMINI_MODEL.
        model_name = os.getenv("GEMINI_MODEL", "models/gemini-flash-latest")
        self.model = genai.GenerativeModel(model_name)

        self.chat_history = [] # Para mantener el contexto de la conversación
        self.system_instruction = (
            "Eres IngeChat 360°, un asistente virtual especializado en proporcionar información "
            "precisa y detallada sobre las carreras de Ingeniería (Sistemas, Mecánica, "
            "Telecomunicaciones y Eléctrica) de la UNEFA Núcleo Miranda, Sede Los Teques. "
            "Tu objetivo es asistir a estudiantes actuales y futuros con consultas académicas y profesionales "
            "relacionadas exclusivamente con estas carreras. "
            "Si la pregunta no está directamente relacionada con las carreras de ingeniería de la UNEFA, "
            "responde amablemente que tu función es específica y no puedes asistir con ese tema. "
            "Proporciona respuestas concisas pero informativas, y si es posible, sugiere dónde encontrar más detalles."
        )
        self.start_new_chat() # Inicializar la conversación

    def start_new_chat(self):
        """Inicia una nueva sesión de chat con la instrucción del sistema."""
        self.chat_session = self.model.start_chat(history=[])
        logger.info("Nueva sesión de chat iniciada con Gemini.")

    def _format_history_for_prompt(self, history: list[dict] | None) -> str:
        if not history:
            return ""

        lines: list[str] = []
        for item in history:
            if not isinstance(item, dict):
                continue
            role = str(item.get("role", "")).strip().lower()
            content = str(item.get("content", "")).strip()
            if not content:
                continue

            if role in ("assistant", "model", "bot"):
                lines.append(f"Asistente: {content}")
            else:
                lines.append(f"Usuario: {content}")

        return "\n".join(lines)

    def send_message(self, user_message: str, history: list[dict] | None = None) -> str:
        """Envía un mensaje al modelo Gemini y obtiene una respuesta.

        - Si `history` viene (p. ej. desde una UI web), se construye un prompt con contexto y se usa
          `generate_content` para evitar depender de estado en memoria entre requests.
        - Si `history` es None, se usa la sesión `chat_session` (modo escritorio).
        """
        try:
            history_text = self._format_history_for_prompt(history)

            if history is None:
                prompt = f"{self.system_instruction}\n\nUsuario: {user_message}"
                response = self.chat_session.send_message(prompt)
                response_text = ""
                for part in getattr(response, "parts", []) or []:
                    if hasattr(part, "text"):
                        response_text += part.text
            else:
                prompt_parts = [self.system_instruction]
                if history_text:
                    prompt_parts.append("Contexto de la conversación:\n" + history_text)
                prompt_parts.append("Usuario: " + user_message)
                prompt = "\n\n".join(prompt_parts)

                response = self.model.generate_content(prompt)
                response_text = getattr(response, "text", "") or ""
            
            logger.info(f"Usuario: {user_message}")
            logger.info(f"Gemini: {response_text}")
            return response_text
        except Exception as e:
            logger.error(f"Error al comunicarse con Gemini: {e}")
            return "Lo siento, tuve un problema al procesar tu solicitud. Por favor, inténtalo de nuevo más tarde."

