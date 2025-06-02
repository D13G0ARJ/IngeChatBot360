# src/utils/gemini_api.py
import google.generativeai as genai
from src.utils.config import GEMINI_API_KEY
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class GeminiAPI:
    def __init__(self, api_key: str):
        if not api_key:
            raise ValueError("GEMINI_API_KEY no está configurada. Asegúrate de tenerla en tu archivo .env")
        genai.configure(api_key=api_key)
        # CORRECCIÓN AQUÍ: Cambiado 'gemini-pro' por 'gemini-1.5-flash'
        # Basado en la lista de modelos disponibles que proporcionaste.
        self.model = genai.GenerativeModel('gemini-1.5-flash') 

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

    def send_message(self, user_message: str) -> str:
        """Envía un mensaje al modelo Gemini y obtiene una respuesta."""
        try:
            prompt = f"{self.system_instruction}\n\nUsuario: {user_message}"
            
            response = self.chat_session.send_message(prompt)
            
            response_text = ""
            for part in response.parts:
                if hasattr(part, 'text'):
                    response_text += part.text
            
            logger.info(f"Usuario: {user_message}")
            logger.info(f"Gemini: {response_text}")
            return response_text
        except Exception as e:
            logger.error(f"Error al comunicarse con Gemini: {e}")
            return "Lo siento, tuve un problema al procesar tu solicitud. Por favor, inténtalo de nuevo más tarde."

