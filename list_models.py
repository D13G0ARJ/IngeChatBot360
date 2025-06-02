import google.generativeai as genai
import os
from dotenv import load_dotenv

load_dotenv() # Carga las variables del archivo .env
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")

if not GEMINI_API_KEY:
        print("Error: GEMINI_API_KEY no está configurada en tu archivo .env")
else:
        genai.configure(api_key=GEMINI_API_KEY)
        print("Modelos disponibles para generateContent:")
        for m in genai.list_models():
            if "generateContent" in m.supported_generation_methods:
                print(f"- {m.name}")
    