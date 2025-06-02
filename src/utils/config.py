import os
from dotenv import load_dotenv

# Carga las variables de entorno desde el archivo .env
load_dotenv()

# Obtiene la clave de API de Gemini desde las variables de entorno
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")