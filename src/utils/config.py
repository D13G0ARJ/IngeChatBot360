# src/utils/config.py
import os
from dotenv import load_dotenv

load_dotenv() # Carga las variables del archivo .env

GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")