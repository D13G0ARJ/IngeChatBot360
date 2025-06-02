# src/core/data_manager.py
import json
import os
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class DataManager:
    def __init__(self, data_path='data'):
        self.data_path = data_path
        self.carreras_data = {}
        self.faqs_data = {}
        self.unefa_info = {}
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

    def get_career_info(self, career_name: str) -> dict:
        """Obtiene la información de una carrera específica."""
        return self.carreras_data.get(career_name.lower(), {})

    def get_faq_answer(self, question: str) -> str | None:
        """Busca una respuesta a una pregunta frecuente."""
        # Simple búsqueda por coincidencias, se podría mejorar con algoritmos de similitud.
        for qa in self.faqs_data.get("preguntas_frecuentes", []):
            if question.lower() in qa["pregunta"].lower():
                return qa["respuesta"]
        return None

    def get_unefa_general_info(self, topic: str) -> str | None:
        """Obtiene información general de la UNEFA por tema."""
        return self.unefa_info.get(topic.lower(), None)