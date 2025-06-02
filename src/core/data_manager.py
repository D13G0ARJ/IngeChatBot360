# src/core/data_manager.py
import json
import os
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class DataManager:
    def __init__(self, data_path: str = 'data'):
        self.data_path = data_path
        self.carreras_data = {}
        self.faqs_data = {}
        self.unefa_info = {}
        self.training_data = []
        self._load_all_data()

    def _load_all_data(self):
        """Carga toda la información desde los archivos JSON."""
        # Cargar información de carreras
        carreras_dir = os.path.join(self.data_path, 'carreras')
        if os.path.exists(carreras_dir):
            for filename in os.listdir(carreras_dir):
                if filename.endswith('.json'):
                    career_name = filename.replace('ingenieria_', '').replace('.json', '')
                    filepath = os.path.join(carreras_dir, filename)
                    try:
                        with open(filepath, 'r', encoding='utf-8') as f:
                            self.carreras_data[career_name] = json.load(f)
                        logger.info(f"Cargada información para {career_name}.")
                    except Exception as e:
                        logger.error(f"Error al cargar {filepath}: {e}")
        else:
            logger.warning(f"Directorio de carreras no encontrado: {carreras_dir}")

        # Cargar FAQs generales
        faqs_path = os.path.join(self.data_path, 'faqs.json')
        if os.path.exists(faqs_path):
            try:
                with open(faqs_path, 'r', encoding='utf-8') as f:
                    self.faqs_data = json.load(f)
                logger.info("Cargadas FAQs generales.")
            except Exception as e:
                logger.error(f"Error al cargar {faqs_path}: {e}")
        else:
            logger.warning(f"Archivo de FAQs no encontrado: {faqs_path}")

        # Cargar información general de la UNEFA
        unefa_info_path = os.path.join(self.data_path, 'unefa_info.json')
        if os.path.exists(unefa_info_path):
            try:
                with open(unefa_info_path, 'r', encoding='utf-8') as f:
                    self.unefa_info = json.load(f)
                logger.info("Cargada información general de la UNEFA.")
            except Exception as e:
                logger.error(f"Error al cargar {unefa_info_path}: {e}")
        else:
            logger.warning(f"Archivo de información de UNEFA no encontrado: {unefa_info_path}")

        # Cargar training_data.json
        training_data_path = os.path.join(self.data_path, 'training_data.json')
        if os.path.exists(training_data_path):
            try:
                with open(training_data_path, 'r', encoding='utf-8') as f:
                    self.training_data = json.load(f)
                logger.info("Cargados datos de entrenamiento (training_data.json).")
            except Exception as e:
                logger.error(f"Error al cargar {training_data_path}: {e}")
        else:
            logger.warning(f"Archivo de training_data no encontrado: {training_data_path}")

    def get_career_info(self, career_name: str) -> dict:
        """Obtiene la información de una carrera específica."""
        return self.carreras_data.get(career_name.lower(), {})

    def get_faq_answer(self, question: str) -> str | None:
        """Busca una respuesta a una pregunta frecuente."""
        # Búsqueda simple por coincidencia, se puede mejorar con algoritmos de similitud.
        for qa in self.faqs_data.get("preguntas_frecuentes", []):
            if question.lower() in qa["pregunta"].lower():
                return qa["respuesta"]
        return None

    def get_unefa_general_info(self, topic: str) -> str | None:
        """Obtiene información general de la UNEFA por tema."""
        return self.unefa_info.get(topic.lower(), None)

    def get_training_answer(self, question: str) -> str | None:
        """Busca una respuesta en los datos de entrenamiento por coincidencia exacta o inclusión."""
        question_lower = question.strip().lower()
        for item in self.training_data:
            prompt = item.get("prompt", "").strip().lower()
            if question_lower == prompt or question_lower in prompt or prompt in question_lower:
                return item.get("completion")
        return None