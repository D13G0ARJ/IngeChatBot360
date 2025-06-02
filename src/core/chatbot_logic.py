# src/core/chatbot_logic.py
from src.utils.gemini_api import GeminiAPI
from src.core.data_manager import DataManager
from src.utils.config import GEMINI_API_KEY
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class ChatbotLogic:
    def __init__(self):
        self.data_manager = DataManager()
        self.gemini_api = GeminiAPI(GEMINI_API_KEY)
        self.career_keywords = ["sistemas", "mecanica", "telecomunicaciones", "electrica"]

    def process_message(self, message: str) -> str:
        """
        Procesa el mensaje del usuario y devuelve una respuesta.
        Prioriza la información local antes de consultar a Gemini.
        """
        lower_message = message.lower()

        # 0. Buscar primero en los datos de entrenamiento
        training_answer = self.data_manager.get_training_answer(lower_message)
        if training_answer:
            logger.info("Respuesta obtenida de training_data.json.")
            return training_answer

        # 1. Intentar responder con datos locales (FAQs, información de carreras)
        
        # Búsqueda por FAQs directas
        faq_answer = self.data_manager.get_faq_answer(lower_message)
        if faq_answer:
            logger.info("Respuesta obtenida de FAQs locales.")
            return faq_answer

        # Búsqueda de información de carreras
        for keyword in self.career_keywords:
            if keyword in lower_message:
                career_info = self.data_manager.get_career_info(keyword)
                if career_info:
                    if "plan de estudio" in lower_message or "pensum" in lower_message:
                        plan = career_info.get("plan_estudios", {}) # Asegurarse de que sea un diccionario
                        if plan:
                            plan_lines = []
                            for semester, courses in plan.items():
                                # Manejar la diferencia en la estructura de 'courses'
                                # Si 'courses' es una lista de diccionarios (como en sistemas)
                                if isinstance(courses, list) and all(isinstance(c, dict) for c in courses):
                                    # Extraer solo el nombre de la asignatura
                                    course_names = [c.get("asignatura", "N/A") for c in courses]
                                    plan_lines.append(f"Semestre {semester}: {', '.join(course_names)}")
                                # Si 'courses' es una lista de cadenas (menos probable con tu estructura actual, pero por seguridad)
                                elif isinstance(courses, list) and all(isinstance(c, str) for c in courses):
                                    plan_lines.append(f"Semestre {semester}: {', '.join(courses)}")
                                # Si 'courses' es una cadena (como en los ejemplos simplificados)
                                elif isinstance(courses, str):
                                    plan_lines.append(f"Semestre {semester}: {courses}")
                                # Si es un diccionario con nombres de semestres como claves (ej. "semestres_intermedios")
                                elif isinstance(courses, list): # Esto cubre los casos de ejemplo con listas de diccionarios simples
                                    course_names = [c.get("asignatura", "N/A") for c in courses]
                                    plan_lines.append(f"{semester.replace('_', ' ').title()}: {', '.join(course_names)}")
                                else:
                                    plan_lines.append(f"Semestre {semester}: Información no formateada.")
                            
                            plan_str = "\n".join(plan_lines)
                            logger.info(f"Respuesta obtenida del plan de estudios de {keyword}.")
                            return f"El plan de estudios de Ingeniería de {keyword.capitalize()} incluye:\n{plan_str}\nPara más detalles, consulta la sección de la carrera en el portal de la UNEFA."
                        else:
                            return f"Información del plan de estudios para Ingeniería de {keyword.capitalize()} no disponible."
                    elif "perfil" in lower_message or "egresado" in lower_message:
                        logger.info(f"Respuesta obtenida del perfil de {keyword}.")
                        return career_info.get("perfil_egresado", f"Perfil del egresado para Ingeniería de {keyword.capitalize()} no disponible.")
                    elif "salidas profesionales" in lower_message or "campo laboral" in lower_message:
                        salidas = ", ".join(career_info.get("salidas_profesionales", []))
                        if salidas:
                            logger.info(f"Respuesta obtenida de salidas profesionales de {keyword}.")
                            return f"Algunas salidas profesionales para Ingeniería de {keyword.capitalize()} incluyen: {salidas}."
                    elif "descripcion" in lower_message or "que es" in lower_message:
                        logger.info(f"Respuesta obtenida de descripción de {keyword}.")
                        return career_info.get("descripcion", f"Descripción para Ingeniería de {keyword.capitalize()} no disponible.")
                    elif "duracion" in lower_message:
                        logger.info(f"Respuesta obtenida de duración de {keyword}.")
                        return f"La duración de la carrera de Ingeniería de {keyword.capitalize()} es de {career_info.get('duracion', 'N/A')}."
                    
                    # Si solo mencionan la carrera, dar un resumen general
                    logger.info(f"Respuesta general sobre la carrera {keyword}.")
                    return (f"Ingeniería de {keyword.capitalize()}: {career_info.get('descripcion', 'Descripción no disponible.')} "
                            f"Duración: {career_info.get('duracion', 'N/A')}. "
                            f"Puedes preguntar sobre su perfil de egresado, plan de estudios o salidas profesionales.")

        # Búsqueda de información general de UNEFA
        if "contacto" in lower_message or "telefono" in lower_message or "ubicacion" in lower_message:
            info = self.data_manager.get_unefa_general_info("contacto")
            if info:
                logger.info("Respuesta obtenida de info general UNEFA.")
                return info
        elif "mision" in lower_message:
            info = self.data_manager.get_unefa_general_info("mision")
            if info:
                logger.info("Respuesta obtenida de la misión de UNEFA.")
                return info
        elif "vision" in lower_message:
            info = self.data_manager.get_unefa_general_info("vision")
            if info:
                logger.info("Respuesta obtenida de la visión de UNEFA.")
                return info
        elif "nombre de la institucion" in lower_message or "nombre de la universidad" in lower_message:
            info = self.data_manager.get_unefa_general_info("nombre_institucion")
            if info:
                logger.info("Respuesta obtenida del nombre de la institución.")
                return info


        # 2. Si no se encuentra una respuesta local, consultar a Gemini
        logger.info("Consultando a Gemini API.")
        return self.gemini_api.send_message(message)

    def start_new_chat_session(self):
        """Reinicia la sesión de chat de Gemini."""
        self.gemini_api.start_new_chat()

